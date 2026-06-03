package services

import (
	"context"
	"encoding/csv"
	"fmt"
	"os"
	"path/filepath"
	"pos-desktop/backend/models"
	"pos-desktop/backend/repository"
	"strconv"
	"strings"

	"github.com/wailsapp/wails/v2/pkg/runtime"
	"github.com/xuri/excelize/v2"
)

type ProductService struct {
	ProductRepo  *repository.ProductRepository
	CategoryRepo *repository.CategoryRepository
}

func NewProductService(pr *repository.ProductRepository, cr *repository.CategoryRepository) *ProductService {
	return &ProductService{ProductRepo: pr, CategoryRepo: cr}
}

func (s *ProductService) GetProducts() ([]models.Product, error) {
	return s.ProductRepo.GetAll()
}

func (s *ProductService) CreateProduct(p models.Product) error {
	return s.ProductRepo.Create(p)
}

func (s *ProductService) UpdateProduct(p models.Product) error {
	// Get old product to check stock changes for EDIT FORM adjustments
	old, err := s.ProductRepo.GetByID(p.ID)
	if err == nil {
		if old.ShelfStock != p.ShelfStock {
			_ = s.ProductRepo.RecordMutation(models.StockMutation{
				ProductID:    p.ID,
				Type:         "ADJUSTMENT",
				FromLocation: "SYSTEM",
				ToLocation:   "RAK",
				Quantity:     p.ShelfStock - old.ShelfStock,
				Reference:    "Koreksi Manual Rak",
			})
		}
		if old.WarehouseStock != p.WarehouseStock {
			_ = s.ProductRepo.RecordMutation(models.StockMutation{
				ProductID:    p.ID,
				Type:         "ADJUSTMENT",
				FromLocation: "SYSTEM",
				ToLocation:   "GUDANG",
				Quantity:     p.WarehouseStock - old.WarehouseStock,
				Reference:    "Koreksi Manual Gudang",
			})
		}
	}
	return s.ProductRepo.Update(p)
}

// UpdateProductStock hanya update stok tanpa mencatat mutasi otomatis
// Digunakan saat proses mutasi manual (sudah ada RecordMutation terpisah)
func (s *ProductService) UpdateProductStock(p models.Product) error {
	return s.ProductRepo.Update(p)
}

func (s *ProductService) DeleteProduct(id int) error {
	return s.ProductRepo.Delete(id)
}

func (s *ProductService) GetCategories() ([]models.Category, error) {
	return s.CategoryRepo.GetAll()
}

func (s *ProductService) RecordMutation(m models.StockMutation) error {
	return s.ProductRepo.RecordMutation(m)
}

func (s *ProductService) GetMutations() ([]models.StockMutation, error) {
	return s.ProductRepo.GetMutations()
}

func (s *ProductService) BulkImport(products []models.Product) error {
	for _, p := range products {
		if err := s.ProductRepo.Create(p); err != nil {
			return err
		}
	}
	return nil
}

// colAliases maps lower-cased header values to canonical column keys.
var colAliases = map[string]string{
	"barcode":          "barcode",
	"kode":             "barcode",
	"nama_barang":      "name",
	"nama barang":      "name",
	"nama":             "name",
	"name":             "name",
	"harga_beli_modal": "cost_price",
	"harga beli":       "cost_price",
	"modal":            "cost_price",
	"harga_beli":       "cost_price",
	"cost_price":       "cost_price",
	"harga_jual":       "selling_price",
	"harga jual":       "selling_price",
	"harga":            "selling_price",
	"selling_price":    "selling_price",
	"stok_rak":         "shelf_stock",
	"stok rak":         "shelf_stock",
	"rak":              "shelf_stock",
	"shelf_stock":      "shelf_stock",
	"stok_gudang":      "warehouse_stock",
	"stok gudang":      "warehouse_stock",
	"gudang":           "warehouse_stock",
	"warehouse_stock":  "warehouse_stock",
}

func (s *ProductService) ImportExcel(ctx context.Context, filePath string) (int, error) {
	ext := strings.ToLower(filepath.Ext(filePath))

	var rows [][]string

	if ext == ".csv" {
		// --- Parse CSV ---
		file, err := os.Open(filePath)
		if err != nil {
			return 0, fmt.Errorf("gagal membuka file CSV: %v", err)
		}
		defer file.Close()

		reader := csv.NewReader(file)
		reader.LazyQuotes = true
		reader.TrimLeadingSpace = true
		allRows, err := reader.ReadAll()
		if err != nil {
			return 0, fmt.Errorf("gagal membaca file CSV: %v", err)
		}
		rows = allRows
	} else {
		// --- Parse Excel (xlsx / xls) ---
		f, err := excelize.OpenFile(filePath)
		if err != nil {
			return 0, fmt.Errorf("gagal membuka file Excel: %v", err)
		}
		defer f.Close()

		sheets := f.GetSheetList()
		if len(sheets) == 0 {
			return 0, fmt.Errorf("file Excel tidak memiliki sheet")
		}

		for _, sheetName := range sheets {
			sheetRows, err := f.GetRows(sheetName)
			if err == nil && len(sheetRows) > 1 {
				rows = sheetRows
				break
			}
		}
	}

	if len(rows) <= 1 {
		return 0, fmt.Errorf("file tidak mengandung data produk")
	}

	return s.processImportRows(ctx, rows)
}

// processImportRows processes a 2D slice of strings (header row + data rows) and upserts products.
func (s *ProductService) processImportRows(ctx context.Context, rows [][]string) (int, error) {
	// Build column index map from header row
	colMap := map[string]int{}
	for colIdx, cell := range rows[0] {
		key := strings.ToLower(strings.TrimSpace(cell))
		if canonical, ok := colAliases[key]; ok {
			colMap[canonical] = colIdx
		}
	}

	// Fallback: if no recognized headers found, assume positional order
	// Template order: barcode(0), name(1), cost(2), sell(3), shelf(4), warehouse(5)
	if _, hasName := colMap["name"]; !hasName {
		colMap["barcode"] = 0
		colMap["name"] = 1
		colMap["cost_price"] = 2
		colMap["selling_price"] = 3
		colMap["shelf_stock"] = 4
		colMap["warehouse_stock"] = 5
	}

	imported := 0
	var lastDbErr error
	totalRows := len(rows) - 1

	for i, row := range rows {
		if i == 0 {
			continue // skip header
		}

		// Emit progress
		if i%5 == 0 || i == len(rows)-1 {
			percentage := int((float64(i) / float64(totalRows)) * 100)
			runtime.EventsEmit(ctx, "import-progress", percentage)
		}

		// Helper to get cell value by canonical key
		getCell := func(key string) string {
			idx, ok := colMap[key]
			if !ok || idx >= len(row) {
				return ""
			}
			return strings.TrimSpace(row[idx])
		}

		name := getCell("name")
		if name == "" {
			continue // skip empty rows
		}

		// Parse barcode — handle scientific notation from Excel (e.g. 8.99988E+12)
		rawBarcode := getCell("barcode")
		barcode := ""
		if rawBarcode != "" {
			if fv, err2 := strconv.ParseFloat(rawBarcode, 64); err2 == nil {
				barcode = fmt.Sprintf("%.0f", fv)
			} else {
				barcode = rawBarcode
			}
		}
		if barcode == "" {
			barcode = fmt.Sprintf("AUTO-%d-%d", i, imported+1)
		}

		cost := parseNumericString(getCell("cost_price"))
		sell := parseNumericString(getCell("selling_price"))
		shelf := int(parseNumericString(getCell("shelf_stock")))
		wh := int(parseNumericString(getCell("warehouse_stock")))

		p := models.Product{
			Name:           name,
			Barcode:        barcode,
			CostPrice:      cost,
			SellingPrice:   sell,
			ShelfStock:     shelf,
			WarehouseStock: wh,
		}

		old, _ := s.ProductRepo.GetByBarcode(barcode)

		if err := s.ProductRepo.Upsert(p); err != nil {
			lastDbErr = err
			continue
		}
		imported++

		current, _ := s.ProductRepo.GetByBarcode(barcode)
		if current != nil {
			diffShelf := shelf
			if old != nil {
				diffShelf = shelf - old.ShelfStock
			}
			if diffShelf != 0 {
				_ = s.ProductRepo.RecordMutation(models.StockMutation{
					ProductID:    current.ID,
					Type:         "PURCHASE",
					FromLocation: "SUPPLIER",
					ToLocation:   "RAK",
					Quantity:     diffShelf,
					Reference:    "Import",
				})
			}

			diffWH := wh
			if old != nil {
				diffWH = wh - old.WarehouseStock
			}
			if diffWH != 0 {
				_ = s.ProductRepo.RecordMutation(models.StockMutation{
					ProductID:    current.ID,
					Type:         "PURCHASE",
					FromLocation: "SUPPLIER",
					ToLocation:   "GUDANG",
					Quantity:     diffWH,
					Reference:    "Import",
				})
			}
		}
	}

	if imported == 0 {
		if lastDbErr != nil {
			return 0, fmt.Errorf("database error: %v", lastDbErr)
		}
		return 0, fmt.Errorf("tidak ada data produk valid. Pastikan kolom 'nama_barang' atau 'name' tersedia di baris pertama")
	}

	return imported, nil
}

func parseNumericString(val string) float64 {
	val = strings.TrimSpace(val)
	if val == "" {
		return 0
	}

	// Remove currency symbols and formatting characters
	val = strings.ReplaceAll(val, "Rp", "")
	val = strings.ReplaceAll(val, "rp", "")
	val = strings.ReplaceAll(val, "RP", "")
	val = strings.TrimSpace(val)

	// Handle dot/comma format
	if strings.Contains(val, ".") && strings.Contains(val, ",") {
		val = strings.ReplaceAll(val, ".", "")
		val = strings.ReplaceAll(val, ",", ".")
	} else if strings.Contains(val, ",") {
		// If only comma: check if it has exactly 3 digits after the comma (thousand separator)
		parts := strings.Split(val, ",")
		lastPart := parts[len(parts)-1]
		if len(lastPart) == 3 {
			val = strings.ReplaceAll(val, ",", "")
		} else {
			val = strings.ReplaceAll(val, ",", ".")
		}
	} else if strings.Contains(val, ".") {
		// If only dot: check if it has exactly 3 digits after the dot (thousand separator)
		parts := strings.Split(val, ".")
		lastPart := parts[len(parts)-1]
		if len(lastPart) == 3 && len(parts) > 1 {
			val = strings.ReplaceAll(val, ".", "")
		}
	}

	// Clean up any other non-numeric and non-dot characters
	var cleanStr strings.Builder
	hasDot := false
	for _, char := range val {
		if char >= '0' && char <= '9' {
			cleanStr.WriteRune(char)
		} else if char == '.' && !hasDot {
			cleanStr.WriteRune(char)
			hasDot = true
		}
	}

	fv, _ := strconv.ParseFloat(cleanStr.String(), 64)
	return fv
}

