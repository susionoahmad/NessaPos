package api

import (
	"context"
	"errors"
	"pos-desktop/backend/models"
	"pos-desktop/backend/services"

	"github.com/wailsapp/wails/v2/pkg/runtime"
)

type API struct {
	ctx            context.Context
	userService    *services.UserService
	productService *services.ProductService
	orderService   *services.OrderService
	printService   *services.PrintService
	settingService *services.SettingService
	vaultService   *services.VaultService
	sessionService *services.SessionService
	licenseService *services.LicenseService
	backupService  *services.BackupService
}

func (a *API) Startup(ctx context.Context) {
	a.ctx = ctx
}

func NewAPI(u *services.UserService, p *services.ProductService, o *services.OrderService, pr *services.PrintService, s *services.SettingService, v *services.VaultService, sess *services.SessionService, lic *services.LicenseService, b *services.BackupService) *API {
	return &API{
		userService:    u,
		productService: p,
		orderService:   o,
		printService:   pr,
		settingService: s,
		vaultService:   v,
		sessionService: sess,
		licenseService: lic,
		backupService:  b,
	}
}

// User Methods
func (a *API) Login(username, password string) (*models.User, error) {
	if status, _ := a.licenseService.GetStatus(); status != nil {
		if status.Status != "ok" && status.Status != "trial" {
			return nil, errors.New("LICENSE_REQUIRED")
		}
	}
	return a.userService.Login(username, password)
}
func (a *API) GetUsers() ([]models.User, error) {
	return a.userService.GetUsers()
}
func (a *API) CreateUser(u models.User) error {
	return a.userService.CreateUser(u)
}
func (a *API) UpdateUser(u models.User) error {
	return a.userService.UpdateUser(u)
}
func (a *API) DeleteUser(id int) error {
	return a.userService.DeleteUser(id)
}

func (a *API) ChangePassword(userID int, oldPassword, newPassword string) error {
	return a.userService.ChangePassword(userID, oldPassword, newPassword)
}

// License Methods
func (a *API) GetLicenseStatus() (*services.LicenseStatus, error) {
	return a.licenseService.GetStatus()
}

func (a *API) ActivateLicense(licenseText string) error {
	return a.licenseService.ActivateLicense(licenseText)
}

func (a *API) ActivateOnline(serialKey string) error {
	return a.licenseService.ActivateOnline(serialKey)
}

// Product Methods
func (a *API) GetProducts() ([]models.Product, error) {
	return a.productService.GetProducts()
}
func (a *API) CreateProduct(p models.Product) error {
	return a.productService.CreateProduct(p)
}
func (a *API) UpdateProduct(p models.Product) error {
	return a.productService.UpdateProduct(p)
}
func (a *API) UpdateProductStock(p models.Product) error {
	return a.productService.UpdateProductStock(p)
}
func (a *API) DeleteProduct(id int) error {
	return a.productService.DeleteProduct(id)
}
func (a *API) GetCategories() ([]models.Category, error) {
	return a.productService.GetCategories()
}
func (a *API) GetStockMutations() ([]models.StockMutation, error) {
	return a.productService.GetMutations()
}
func (a *API) RecordStockMutation(m models.StockMutation) error {
	return a.productService.RecordMutation(m)
}
func (a *API) BulkImportProducts(products []models.Product) error {
	return a.productService.BulkImport(products)
}

func (a *API) ImportExcel() (int, error) {
	filePath, err := runtime.OpenFileDialog(a.ctx, runtime.OpenDialogOptions{
		Title: "Pilih File Produk (Excel / CSV)",
		Filters: []runtime.FileFilter{
			{DisplayName: "Semua Format yang Didukung (*.xlsx;*.xls;*.csv)", Pattern: "*.xlsx;*.xls;*.csv"},
			{DisplayName: "Excel 2007+ (*.xlsx)", Pattern: "*.xlsx"},
			{DisplayName: "Excel Legacy (*.xls)", Pattern: "*.xls"},
			{DisplayName: "CSV (*.csv)", Pattern: "*.csv"},
		},
	})
	if err != nil {
		return 0, err
	}
	if filePath == "" {
		// User cancelled — return distinct error so frontend can skip success message
		return 0, errors.New("CANCELLED")
	}
	return a.productService.ImportExcel(a.ctx, filePath)
}

func (a *API) PerformBackup() error {
	return a.backupService.PerformBackup()
}

// Order Methods
func (a *API) CreateOrder(req services.OrderRequest) error {
	return a.orderService.CreateOrder(req)
}
func (a *API) GetDailySales() (float64, error) {
	return a.orderService.GetDailySales()
}
func (a *API) GetDailyCashSales() (float64, error) {
	return a.orderService.GetDailyCashSales()
}
func (a *API) GetDailyPaymentCounts() (map[string]int, error) {
	cashCount, nonCashCount, err := a.orderService.GetDailyPaymentCounts()
	if err != nil {
		return nil, err
	}
	return map[string]int{
		"cash":     cashCount,
		"non_cash": nonCashCount,
	}, nil
}
func (a *API) GetPaymentCounts(userID int, sessionID int) (map[string]int, error) {
	cashCount, nonCashCount, err := a.orderService.GetPaymentCounts(userID, sessionID)
	if err != nil {
		return nil, err
	}
	return map[string]int{
		"cash":     cashCount,
		"non_cash": nonCashCount,
	}, nil
}
func (a *API) GetMonthlySales() (float64, error) {
	return a.orderService.GetMonthlySales()
}
func (a *API) GetDetailedSales() ([]map[string]interface{}, error) {
	return a.orderService.GetDetailedSales()
}

// Print Methods
func (a *API) PrintReceiptThermal(req services.OrderRequest) error {
	order := models.Order{
		UserID:       uint(req.UserID),
		TotalAmount:  req.TotalAmount,
		TaxAmount:    req.TaxAmount,
		Discount:     req.Discount,
		FinalAmount:  req.FinalAmount,
		AmountPaid:   req.AmountPaid,
		ChangeAmount: req.ChangeAmount,
		Status:       "Paid",
		Items:        req.Items,
	}

	printerName := ""
	header := services.PrintHeader{}
	footer := ""
	if s, err := a.settingService.GetSettings(); err == nil {
		printerName = s.PrinterName
		header.StoreName = s.StoreName
		header.StoreAddress = s.StoreAddress
		header.StorePhone = s.StorePhone
		footer = s.ReceiptText
	}
	return a.printService.PrintReceiptThermalWithPrinter(order, printerName, header, footer)
}

func (a *API) PrintSessionOpenReceipt(req services.SessionOpenPrintRequest) error {
	printerName := ""
	header := services.PrintHeader{}
	if s, err := a.settingService.GetSettings(); err == nil {
		if !s.PrintSessionSlip {
			return nil
		}
		printerName = s.PrinterName
		header.StoreName = s.StoreName
		header.StoreAddress = s.StoreAddress
		header.StorePhone = s.StorePhone
	}
	return a.printService.PrintSessionOpenReceipt(req, header, printerName)
}

func (a *API) PrintSessionCloseReceipt(req services.SessionClosePrintRequest) error {
	printerName := ""
	header := services.PrintHeader{}
	if s, err := a.settingService.GetSettings(); err == nil {
		if !s.PrintSessionSlip {
			return nil
		}
		printerName = s.PrinterName
		header.StoreName = s.StoreName
		header.StoreAddress = s.StoreAddress
		header.StorePhone = s.StorePhone
	}
	return a.printService.PrintSessionCloseReceipt(req, header, printerName)
}

func (a *API) ListPrinters() ([]string, error) {
	return a.printService.ListPrinters()
}

func (a *API) TestPrint(printerName string) error {
	header := services.PrintHeader{}
	if s, err := a.settingService.GetSettings(); err == nil {
		header.StoreName = s.StoreName
		if printerName == "" {
			printerName = s.PrinterName
		}
	}
	return a.printService.TestPrint(header, printerName)
}

func (a *API) KickDrawer() error {
	printerName := ""
	if s, err := a.settingService.GetSettings(); err == nil {
		printerName = s.PrinterName
	}
	return a.printService.KickDrawer(printerName)
}

// Settings Methods
func (a *API) GetSettings() (*models.Setting, error) {
	return a.settingService.GetSettings()
}
func (a *API) UpdateSettings(s models.Setting) error {
	return a.settingService.UpdateSettings(s)
}

// Vault Methods
func (a *API) GetVault() (*models.Vault, error) {
	return a.vaultService.GetVault()
}
func (a *API) GetVaultTransactions() ([]models.VaultTransaction, error) {
	return a.vaultService.GetTransactions()
}
func (a *API) AddCapital(amount float64, adminID int, description string) error {
	return a.vaultService.AddCapital(amount, adminID, description)
}

// Session Methods
func (a *API) GetOpenSession(userID int) (*models.CashierSession, error) {
	return a.sessionService.GetOpenSession(userID)
}
func (a *API) OpenSession(userID int, startAmount float64, startDenoms string) (*models.CashierSession, error) {
	return a.sessionService.OpenSession(userID, startAmount, startDenoms)
}
func (a *API) CloseSession(userID int, endAmountPhysical float64, endDenoms string) (*models.CashierSession, error) {
	return a.sessionService.CloseSession(userID, endAmountPhysical, endDenoms)
}
func (a *API) GetAllSessions() ([]models.CashierSession, error) {
	return a.sessionService.GetAllSessions()
}
func (a *API) GetSessionSales(sessionID int, userID int, startTime string) (float64, error) {
	return a.sessionService.GetSessionSales(sessionID, userID, startTime)
}
func (a *API) GetCashierTransactions() ([]models.CashierTransaction, error) {
	return a.sessionService.GetCashierTransactions()
}

func (a *API) GetCashierBalance(userID int) (float64, error) {
	return a.sessionService.GetCurrentBalance(userID)
}
