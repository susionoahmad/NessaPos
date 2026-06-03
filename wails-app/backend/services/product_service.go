package services

import (
	"context"
	"fmt"
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

func (s *ProductService) ImportExcel(ctx context.Context, filePath string) error {
	f, err := excelize.OpenFile(filePath)
	if err != nil {
		return fmt.Errorf("gagal membuka file: %v", err)
	}
	defer f.Close()

	sheets := f.GetSheetList()
	if len(sheets) == 0 {
		return fmt.Errorf("file Excel tidak memiliki sheet")
	}

	imported := 0
	var lastDbErr error

	// Coba cari data di semua sheet yang ada
	for _, sheetName := range sheets {
		rows, err := f.GetRows(sheetName)
		if err != nil || len(rows) <= 1 {
			continue // Sheet kosong atau gagal baca, lanjut ke sheet berikutnya
		}

		totalRows := len(rows) - 1
		for i, row := range rows {
			if i == 0 {
				continue // Skip header row
			}

			// Emit progress every 5 rows or last row
			if i%5 == 0 || i == len(rows)-1 {
				percentage := int((float64(i) / float64(totalRows)) * 100)
				runtime.EventsEmit(ctx, "import-progress", percentage)
			}

			// Check if row is effectively empty (all cells whitespace)
			isRowEmpty := true
			for _, cell := range row {
				if strings.TrimSpace(cell) != "" {
					isRowEmpty = false
					break
				}
			}
			if isRowEmpty {
				continue
			}

			// Nama produk harus ada (Kolom A)
			if len(row) < 1 || strings.TrimSpace(row[0]) == "" {
				continue 
			}

			name := strings.TrimSpace(row[0])

			// Barcode: Kolom B
			barcode := ""
			if len(row) > 1 && strings.TrimSpace(row[1]) != "" {
				raw := strings.TrimSpace(row[1])
				if fv, err2 := strconv.ParseFloat(raw, 64); err2 == nil {
					barcode = fmt.Sprintf("%.0f", fv)
				} else {
					barcode = raw
				}
			} else {
				barcode = fmt.Sprintf("ID-%d-%d", i, imported+1)
			}

			var cost, sell float64
			var shelf, wh int

			if len(row) > 2 {
				cost, _ = strconv.ParseFloat(strings.TrimSpace(row[2]), 64)
			}
			if len(row) > 3 {
				sell, _ = strconv.ParseFloat(strings.TrimSpace(row[3]), 64)
			}
			if len(row) > 4 {
				if fv, err2 := strconv.ParseFloat(strings.TrimSpace(row[4]), 64); err2 == nil {
					shelf = int(fv)
				}
			}
			if len(row) > 5 {
				if fv, err2 := strconv.ParseFloat(strings.TrimSpace(row[5]), 64); err2 == nil {
					wh = int(fv)
				}
			}

			p := models.Product{
				Name:           name,
				Barcode:        barcode,
				CostPrice:      cost,
				SellingPrice:   sell,
				ShelfStock:     shelf,
				WarehouseStock: wh,
			}

			// Fetch old product to calculate mutation
			old, _ := s.ProductRepo.GetByBarcode(barcode)
			
			if err := s.ProductRepo.Upsert(p); err != nil {
				lastDbErr = err
			} else {
				imported++
				
				// Get the ID of the product (needed for mutation)
				// If new, fetch it again to get the assigned ID
				current, _ := s.ProductRepo.GetByBarcode(barcode)
				if current != nil {
					// Record mutation for Shelf Stock
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
							Reference:    "Import Excel",
						})
					}

					// Record mutation for Warehouse Stock
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
							Reference:    "Import Excel",
						})
					}
				}
			}
		}
		
		// Jika sudah ada data yang masuk dari sheet ini, berhenti mencari di sheet lain
		if imported > 0 {
			break
		}
	}

	if imported == 0 {
		if lastDbErr != nil {
			return fmt.Errorf("database error: %v", lastDbErr)
		}
		return fmt.Errorf("tidak ada data produk valid yang ditemukan. Pastikan Nama Produk ada di kolom A baris ke-2 dst")
	}

	return nil
}
