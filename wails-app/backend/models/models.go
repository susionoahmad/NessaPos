package models

type User struct {
	ID        int    `json:"id"`
	Username  string `json:"username"`
	Password  string `json:"password"`
	Role      string `json:"role"` // Admin / Kasir
	CreatedAt string `json:"created_at"`
	UpdatedAt string `json:"updated_at"`
}

type Category struct {
	ID        int    `json:"id"`
	Name      string `json:"name"`
	CreatedAt string `json:"created_at"`
	UpdatedAt string `json:"updated_at"`
}

type Product struct {
	ID             int     `json:"id"`
	Name           string  `json:"name"`
	Barcode        string  `json:"barcode"`
	CostPrice      float64 `json:"cost_price"` // HPP
	SellingPrice   float64 `json:"selling_price"`
	ShelfStock     int     `json:"shelf_stock"`     // Stok Rak
	WarehouseStock int     `json:"warehouse_stock"` // Stok Gudang
	CreatedAt      string  `json:"created_at"`
	UpdatedAt      string  `json:"updated_at"`
}

type StockMutation struct {
	ID           int    `json:"id"`
	ProductID    int    `json:"product_id"`
	ProductName  string `json:"product_name"`
	Type         string `json:"type"`          // PURCHASE, SALE, MUTATION, ADJUSTMENT
	FromLocation string `json:"from_location"` // RAK, GUDANG, SUPPLIER, CUSTOMER
	ToLocation   string `json:"to_location"`
	Quantity     int    `json:"quantity"`
	Reference    string `json:"reference"`
	CreatedAt    string `json:"created_at"`
	CreatedBy    int    `json:"created_by"`
}

type Payment struct {
	ID            int     `json:"id"`
	OrderID       int     `json:"order_id"`
	PaymentMethod string  `json:"payment_method"`
	AmountPaid    float64 `json:"amount_paid"`
	ChangeAmount  float64 `json:"change_amount"`
	Status        string  `json:"status"`
	CreatedAt     string  `json:"created_at"`
	UpdatedAt     string  `json:"updated_at"`
}

type Setting struct {
	ID           int     `json:"id"`
	StoreName    string  `json:"store_name"`
	StoreAddress string  `json:"store_address"`
	StorePhone   string  `json:"store_phone"`
	TaxRate      float64 `json:"tax_rate"`
	TaxType      string  `json:"tax_type"` // exclusive / inclusive
	ReceiptText  string  `json:"receipt_text"`
	PrinterName        string  `json:"printer_name"`
	RefreshIntervalSec int     `json:"refresh_interval_sec"`
	PrintSessionSlip   bool    `json:"print_session_slip"`
	BridgeToken        string  `json:"bridge_token"`
	BridgePort         int     `json:"bridge_port"`
	AllowedOrigins     string  `json:"allowed_origins"` // Comma separated, e.g. "http://localhost:5173,https://pos-online.com"
	TrialStart         string  `json:"trial_start"`
	LastRun            string  `json:"last_run"`
	LicenseBlob        string  `json:"license_blob"`
	UpdatedAt          string  `json:"updated_at"`
}

type CashierSession struct {
	ID                  int     `json:"id"`
	UserID              int     `json:"user_id"`
	Username            string  `json:"username"`
	StartTime           string  `json:"start_time"`
	EndTime             string  `json:"end_time"`
	Status              string  `json:"status"` // OPEN or CLOSED
	StartAmount         float64 `json:"start_amount"`
	EndAmountCalculated float64 `json:"end_amount_calculated"`
	EndAmountPhysical   float64 `json:"end_amount_physical"`
	Difference          float64 `json:"difference"`
	StartDenoms         string  `json:"start_denoms"`
	EndDenoms           string  `json:"end_denoms"`
}

type Vault struct {
	ID        int     `json:"id"`
	Balance   float64 `json:"balance"`
	UpdatedAt string  `json:"updated_at"`
}

type VaultTransaction struct {
	ID           int     `json:"id"`
	Type         string  `json:"type"` // ADD_CAPITAL, SESSION_START, SESSION_END
	Amount       float64 `json:"amount"`
	BalanceAfter float64 `json:"balance_after"`
	Description  string  `json:"description"`
	CreatedAt    string  `json:"created_at"`
	CreatedBy    int     `json:"created_by"`
	Username     string  `json:"username"`
}

type CashierTransaction struct {
	ID           int     `json:"id"`
	SessionID    int     `json:"session_id"`
	UserID       int     `json:"user_id"`
	Username     string  `json:"username"`
	Type         string  `json:"type"` // SESSION_START, SALE, SESSION_END
	Amount       float64 `json:"amount"`
	BalanceAfter float64 `json:"balance_after"`
	Description  string  `json:"description"`
	CreatedAt    string  `json:"created_at"`
}
