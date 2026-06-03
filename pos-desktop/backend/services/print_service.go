package services

import (
	"encoding/json"
	"fmt"
	"pos-desktop/backend/models"
	"runtime"
	"sort"
	"strconv"
	"strings"
	"time"

	"github.com/alexbrainman/printer" // Perlu install: go get github.com/alexbrainman/printer
)

type PrintService struct{}

type PrintHeader struct {
	StoreName    string
	StoreAddress string
	StorePhone   string
}

type SessionOpenPrintRequest struct {
	UserID   int     `json:"user_id"`
	Username string  `json:"username"`
	Amount   float64 `json:"amount"`
	Denoms   string  `json:"denoms"`
}

type SessionClosePrintRequest struct {
	UserID      int     `json:"user_id"`
	Username    string  `json:"username"`
	StartAmount float64 `json:"start_amount"`
	CashSales   float64 `json:"cash_sales"`
	Expected    float64 `json:"expected"`
	Physical    float64 `json:"physical"`
	Difference  float64 `json:"difference"`
	Denoms      string  `json:"denoms"`
}

func NewPrintService() *PrintService {
	return &PrintService{}
}

// PrintReceiptThermal mencoba mendeteksi printer thermal secara otomatis dan mencetak
func (s *PrintService) PrintReceiptThermal(order models.Order, header PrintHeader, footer string) error {
	return s.PrintReceiptThermalWithPrinter(order, "", header, footer)
}

// PrintReceiptThermalWithPrinter mencetak dengan printer yang ditentukan (jika kosong, auto-detect)
func (s *PrintService) PrintReceiptThermalWithPrinter(order models.Order, printerName string, header PrintHeader, footer string) error {
	if runtime.GOOS != "windows" {
		return fmt.Errorf("fitur ini saat ini hanya didukung di Windows")
	}

	// 1. Cari printer secara otomatis (Coding Pintar)
	if strings.TrimSpace(printerName) == "" {
		autoName, err := s.autoDetectThermalPrinter()
		if err != nil {
			return err
		}
		printerName = autoName
	}

	// 2. Buka koneksi ke printer
	printerName = strings.TrimSpace(printerName)
	p, err := printer.Open(printerName)
	if err != nil {
		// Fuzzy match if exact fails
		names, _ := printer.ReadNames()
		found := false
		for _, n := range names {
			if strings.EqualFold(strings.TrimSpace(n), printerName) {
				p, err = printer.Open(n)
				if err == nil {
					found = true
					break
				}
			}
		}
		if !found {
			return fmt.Errorf("gagal membuka printer '%s': %v (Pastikan printer ONLINE)", printerName, err)
		}
	}
	defer p.Close()

	// 3. Format ESC/POS (Data mentah untuk printer thermal)
	now := time.Now().Format("02/01/06 15:04")
	receiptData := "\x1b\x40"     // Initialize
	receiptData += "\x1b\x61\x01" // Align Center

	// Header Toko
	receiptData += fmt.Sprintf("\x1b\x21\x20%s\x1b\x21\x00\n", header.StoreName) // Double Width
	if header.StoreAddress != "" {
		receiptData += fmt.Sprintf("%s\n", header.StoreAddress)
	}
	if header.StorePhone != "" {
		receiptData += fmt.Sprintf("Tel: %s\n", header.StorePhone)
	}

	receiptData += "--------------------------------\n"
	receiptData += fmt.Sprintf("No: #%d  %s\n", order.ID, now)
	receiptData += fmt.Sprintf("Kasir: %d\n", order.UserID)
	receiptData += "--------------------------------\n"

	// Items
	receiptData += "\x1b\x61\x00" // Align Left
	for _, item := range order.Items {
		receiptData += fmt.Sprintf("%-32s\n", item.ProductName)
		qtyPrice := fmt.Sprintf("%d x %.0f", item.Quantity, item.Price)
		receiptData += fmt.Sprintf("%-20s %11.0f\n", qtyPrice, item.Total)
	}

	receiptData += "--------------------------------\n"

	// Totals
	receiptData += "\x1b\x61\x02" // Align Right
	receiptData += fmt.Sprintf("Subtotal:  Rp %10.0f\n", order.TotalAmount)
	if order.TaxAmount > 0 {
		receiptData += fmt.Sprintf("Pajak:     Rp %10.0f\n", order.TaxAmount)
	}
	if order.Discount > 0 {
		receiptData += fmt.Sprintf("Diskon:    Rp %10.0f\n", order.Discount)
	}
	receiptData += fmt.Sprintf("\x1b\x21\x01TOTAL:     Rp %10.0f\x1b\x21\x00\n", order.FinalAmount)
	receiptData += "--------------------------------\n"
	receiptData += fmt.Sprintf("Bayar:     Rp %10.0f\n", order.AmountPaid)
	receiptData += fmt.Sprintf("Kembali:   Rp %10.0f\n", order.ChangeAmount)

	// Footer
	receiptData += "\n\x1b\x61\x01" // Center
	if footer != "" {
		receiptData += fmt.Sprintf("%s\n", footer)
	} else {
		receiptData += "Terima Kasih Atas\nKunjungan Anda\n"
	}
	receiptData += "\n\n\n\x1d\x56\x42\x00" // Cut Paper

	// 4. Kirim data mentah ke printer
	err = p.StartDocument("Receipt", "RAW")
	if err != nil {
		return fmt.Errorf("gagal memulai dokumen: %v", err)
	}
	defer p.EndDocument()

	err = p.StartPage()
	if err != nil {
		return fmt.Errorf("gagal memulai halaman: %v", err)
	}
	defer p.EndPage()

	_, err = fmt.Fprint(p, receiptData)
	return err
}

// PrintSessionOpenReceipt mencetak slip buka sesi kasir
func (s *PrintService) PrintSessionOpenReceipt(req SessionOpenPrintRequest, header PrintHeader, printerName string) error {
	if runtime.GOOS != "windows" {
		return fmt.Errorf("fitur ini saat ini hanya didukung di Windows")
	}

	if strings.TrimSpace(printerName) == "" {
		autoName, err := s.autoDetectThermalPrinter()
		if err != nil {
			return err
		}
		printerName = autoName
	}

	printerName = strings.TrimSpace(printerName)
	p, err := printer.Open(printerName)
	if err != nil {
		names, _ := printer.ReadNames()
		found := false
		for _, n := range names {
			if strings.EqualFold(strings.TrimSpace(n), printerName) {
				p, err = printer.Open(n)
				if err == nil {
					found = true
					break
				}
			}
		}
		if !found {
			return fmt.Errorf("gagal membuka printer '%s': %v", printerName, err)
		}
	}
	defer p.Close()

	now := time.Now().Format("02/01/2006 15:04:05")
	receiptData := "\x1b\x40"
	receiptData += "\x1b\x61\x01"
	receiptData += fmt.Sprintf("%s\n", header.StoreName)
	if header.StoreAddress != "" {
		receiptData += fmt.Sprintf("%s\n", header.StoreAddress)
	}
	if header.StorePhone != "" {
		receiptData += fmt.Sprintf("Tel: %s\n", header.StorePhone)
	}
	receiptData += "-------------------------------\n"
	receiptData += "BUKA SESI KASIR\n"
	receiptData += "-------------------------------\n"
	receiptData += "\x1b\x61\x00"
	receiptData += fmt.Sprintf("Waktu: %s\n", now)
	receiptData += fmt.Sprintf("Kasir: %s (ID %d)\n", req.Username, req.UserID)
	receiptData += "-------------------------------\n"
	receiptData += "Ringkasan Uang Diterima\n"
	for _, line := range formatDenoms(req.Denoms) {
		receiptData += line + "\n"
	}
	receiptData += "-------------------------------\n"
	receiptData += fmt.Sprintf("Total Modal: Rp %.0f\n", req.Amount)
	receiptData += "-------------------------------\n"
	receiptData += "Tanda Tangan:\n"
	receiptData += "Kasir      : ________________\n"
	receiptData += "Supervisor : ________________\n"
	receiptData += "\n\x1d\x56\x42\x00"

	err = p.StartDocument("SessionOpen", "RAW")
	if err != nil {
		return fmt.Errorf("gagal memulai dokumen: %v", err)
	}
	defer p.EndDocument()

	err = p.StartPage()
	if err != nil {
		return fmt.Errorf("gagal memulai halaman: %v", err)
	}
	defer p.EndPage()

	_, err = fmt.Fprint(p, receiptData)
	return err
}

// PrintSessionCloseReceipt mencetak slip tutup sesi kasir
func (s *PrintService) PrintSessionCloseReceipt(req SessionClosePrintRequest, header PrintHeader, printerName string) error {
	if runtime.GOOS != "windows" {
		return fmt.Errorf("fitur ini saat ini hanya didukung di Windows")
	}

	if strings.TrimSpace(printerName) == "" {
		autoName, err := s.autoDetectThermalPrinter()
		if err != nil {
			return err
		}
		printerName = autoName
	}

	printerName = strings.TrimSpace(printerName)
	p, err := printer.Open(printerName)
	if err != nil {
		names, _ := printer.ReadNames()
		found := false
		for _, n := range names {
			if strings.EqualFold(strings.TrimSpace(n), printerName) {
				p, err = printer.Open(n)
				if err == nil {
					found = true
					break
				}
			}
		}
		if !found {
			return fmt.Errorf("gagal membuka printer '%s': %v", printerName, err)
		}
	}
	defer p.Close()

	now := time.Now().Format("02/01/2006 15:04:05")
	receiptData := "\x1b\x40"
	receiptData += "\x1b\x61\x01"
	receiptData += fmt.Sprintf("%s\n", header.StoreName)
	if header.StoreAddress != "" {
		receiptData += fmt.Sprintf("%s\n", header.StoreAddress)
	}
	if header.StorePhone != "" {
		receiptData += fmt.Sprintf("Tel: %s\n", header.StorePhone)
	}
	receiptData += "-------------------------------\n"
	receiptData += "TUTUP SESI KASIR\n"
	receiptData += "-------------------------------\n"
	receiptData += "\x1b\x61\x00"
	receiptData += fmt.Sprintf("Waktu: %s\n", now)
	receiptData += fmt.Sprintf("Kasir: %s (ID %d)\n", req.Username, req.UserID)
	receiptData += "-------------------------------\n"
	receiptData += fmt.Sprintf("Modal Awal: Rp %.0f\n", req.StartAmount)
	receiptData += fmt.Sprintf("Penjualan Tunai: Rp %.0f\n", req.CashSales)
	receiptData += fmt.Sprintf("Saldo Seharusnya: Rp %.0f\n", req.Expected)
	receiptData += fmt.Sprintf("Uang Fisik: Rp %.0f\n", req.Physical)
	if req.Difference != 0 {
		receiptData += fmt.Sprintf("Selisih: Rp %.0f\n", req.Difference)
	}
	receiptData += "-------------------------------\n"
	receiptData += "Rincian Uang Fisik\n"
	for _, line := range formatDenoms(req.Denoms) {
		receiptData += line + "\n"
	}
	receiptData += "-------------------------------\n"
	receiptData += "Tanda Tangan:\n"
	receiptData += "Kasir      : ________________\n"
	receiptData += "Supervisor : ________________\n"
	receiptData += "\n\x1d\x56\x42\x00"

	err = p.StartDocument("SessionClose", "RAW")
	if err != nil {
		return fmt.Errorf("gagal memulai dokumen: %v", err)
	}
	defer p.EndDocument()

	err = p.StartPage()
	if err != nil {
		return fmt.Errorf("gagal memulai halaman: %v", err)
	}
	defer p.EndPage()

	_, err = fmt.Fprint(p, receiptData)
	return err
}

func formatDenoms(denoms string) []string {
	if strings.TrimSpace(denoms) == "" {
		return []string{"(Tidak diisi)"}
	}
	var m map[string]int
	if err := json.Unmarshal([]byte(denoms), &m); err != nil {
		return []string{"(Format tidak valid)"}
	}
	if len(m) == 0 {
		return []string{"(Tidak diisi)"}
	}

	keys := make([]int, 0, len(m))
	for k := range m {
		if v, err := strconv.Atoi(k); err == nil {
			keys = append(keys, v)
		}
	}
	sort.Sort(sort.Reverse(sort.IntSlice(keys)))

	var lines []string
	for _, k := range keys {
		count := m[strconv.Itoa(k)]
		if count <= 0 {
			continue
		}
		total := k * count
		lines = append(lines, fmt.Sprintf("Rp %d x %d = %d", k, count, total))
	}
	if len(lines) == 0 {
		return []string{"(Tidak diisi)"}
	}
	return lines
}

// ListPrinters mengembalikan daftar printer yang terpasang di Windows
func (s *PrintService) ListPrinters() ([]string, error) {
	names, err := printer.ReadNames()
	if err != nil {
		return nil, err
	}
	return names, nil
}

func (s *PrintService) TestPrint(header PrintHeader, printerName string) error {
	if runtime.GOOS != "windows" {
		return fmt.Errorf("fitur ini hanya didukung di Windows")
	}

	if strings.TrimSpace(printerName) == "" {
		autoName, err := s.autoDetectThermalPrinter()
		if err != nil {
			return err
		}
		printerName = autoName
	}

	printerName = strings.TrimSpace(printerName)
	p, err := printer.Open(printerName)
	if err != nil {
		names, _ := printer.ReadNames()
		for _, n := range names {
			if strings.EqualFold(strings.TrimSpace(n), printerName) {
				p, err = printer.Open(n)
				if err == nil {
					goto TestOpened
				}
			}
		}
		return fmt.Errorf("gagal membuka printer '%s': %v", printerName, err)
	}
TestOpened:
	defer p.Close()

	receiptData := "\x1b\x40\x1b\x61\x01"
	receiptData += "TEST PRINT BERHASIL\n"
	receiptData += header.StoreName + "\n"
	receiptData += "-------------------------------\n"
	receiptData += "Jika Anda melihat struk ini,\n"
	receiptData += "berarti printer sudah terhubung.\n"
	receiptData += "-------------------------------\n"
	receiptData += time.Now().Format("02/01/2006 15:04:05") + "\n\n\n\n\x1d\x56\x42\x00"

	err = p.StartDocument("TestPrint", "RAW")
	if err != nil {
		return fmt.Errorf("gagal memulai dokumen: %v", err)
	}
	defer p.EndDocument()

	err = p.StartPage()
	if err != nil {
		return fmt.Errorf("gagal memulai halaman: %v", err)
	}
	defer p.EndPage()

	_, err = fmt.Fprint(p, receiptData)
	return err
}

func (s *PrintService) autoDetectThermalPrinter() (string, error) {
	names, err := printer.ReadNames()
	if err != nil {
		return "", err
	}
	if len(names) == 0 {
		return "", fmt.Errorf("tidak ada printer terpasang di Windows")
	}

	// Cari printer yang mengandung kata kunci umum printer thermal
	keywords := []string{
		"thermal", "pos-", "pos58", "pos80", "receipt", "printer",
		"epson", "xprinter", "gprinter", "bixolon", "citizen", "star",
		"rongta", "zjiang", "beiyang", "winpal", "58mm", "80mm",
	}
	for _, name := range names {
		lowerName := strings.ToLower(name)
		for _, kw := range keywords {
			if strings.Contains(lowerName, kw) {
				return name, nil
			}
		}
	}

	// Fallback 1: gunakan default printer jika ada
	if def, err := printer.Default(); err == nil && def != "" {
		return def, nil
	}

	// Fallback 2: jika hanya ada 1 printer, gunakan itu
	if len(names) == 1 {
		return names[0], nil
	}

	return "", fmt.Errorf("printer thermal tidak ditemukan. Terpasang: %s", strings.Join(names, ", "))
}

// KickDrawer mengirimkan perintah ESC/POS untuk membuka laci kasir (Kick)
func (s *PrintService) KickDrawer(printerName string) error {
	if runtime.GOOS != "windows" {
		return fmt.Errorf("fitur ini saat ini hanya didukung di Windows")
	}

	if strings.TrimSpace(printerName) == "" {
		autoName, err := s.autoDetectThermalPrinter()
		if err != nil {
			return err
		}
		printerName = autoName
	}

	p, err := printer.Open(printerName)
	if err != nil {
		return fmt.Errorf("gagal membuka printer untuk kick drawer: %v", err)
	}
	defer p.Close()

	// ESC p m t1 t2
	// \x1b\x70\x00\x19\xfa adalah perintah standar ESC/POS untuk Kick Drawer Pin 2
	kickCommand := "\x1b\x70\x00\x19\xfa"

	err = p.StartDocument("KickDrawer", "RAW")
	if err != nil {
		return err
	}
	defer p.EndDocument()

	err = p.StartPage()
	if err != nil {
		return err
	}
	defer p.EndPage()

	_, err = fmt.Fprint(p, kickCommand)
	return err
}
