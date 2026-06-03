package services

import (
	"pos-desktop/backend/models"
	"pos-desktop/backend/repository"
	"strings"
	"time"
)

type OrderService struct {
	Repo        *repository.OrderRepository
	sessionRepo *repository.SessionRepository
	cashierRepo *repository.CashierRepository
	settingRepo *repository.SettingRepository
}

func NewOrderService(repo *repository.OrderRepository, sessionRepo *repository.SessionRepository, cashierRepo *repository.CashierRepository) *OrderService {
	return &OrderService{Repo: repo, sessionRepo: sessionRepo, cashierRepo: cashierRepo}
}

func (s *OrderService) SetSettingRepository(settingRepo *repository.SettingRepository) {
	s.settingRepo = settingRepo
}

type OrderRequest struct {
	UserID        int                `json:"user_id"`
	TotalAmount   float64            `json:"total_amount"`
	TaxAmount     float64            `json:"tax_amount"`
	Discount      float64            `json:"discount"`
	FinalAmount   float64            `json:"final_amount"`
	Items         []models.OrderItem `json:"items"`
	PaymentMethod string             `json:"payment_method"`
	AmountPaid    float64            `json:"amount_paid"`
	ChangeAmount  float64            `json:"change_amount"`
}

func (s *OrderService) CreateOrder(req OrderRequest) error {
	order := models.Order{
		UserID:       uint(req.UserID),
		TotalAmount:  req.TotalAmount,
		TaxAmount:    req.TaxAmount,
		Discount:     req.Discount,
		FinalAmount:  req.FinalAmount,
		AmountPaid:   req.AmountPaid,
		ChangeAmount: req.ChangeAmount,
		Status:       "Paid",
	}

	payment := models.Payment{
		PaymentMethod: req.PaymentMethod,
		AmountPaid:    req.AmountPaid,
		ChangeAmount:  req.ChangeAmount,
		Status:        "Paid",
	}

	err := s.Repo.CreateTransaction(order, req.Items, payment)
	if err != nil {
		return err
	}

	// Record cashier mutation if cash payment and a cashier session is open.
	if strings.EqualFold(strings.TrimSpace(req.PaymentMethod), "Cash") {
		session, _ := s.sessionRepo.GetOpenSession(req.UserID)
		if session != nil {
			balance, err := s.cashierRepo.GetLatestBalance(session.ID)
			if err != nil {
				balance = session.StartAmount
			}
			_ = s.cashierRepo.RecordTransaction(nil, &models.CashierTransaction{
				SessionID:    session.ID,
				UserID:       req.UserID,
				Type:         "SALE",
				Amount:       req.FinalAmount,
				BalanceAfter: balance + req.FinalAmount,
				Description:  "Penjualan POS #Cash",
			})
		}
	}

	return nil
}

func (s *OrderService) GetDailySales() (float64, error) {
	today := time.Now().Format("2006-01-02")
	return s.Repo.GetDailySales(today)
}

func (s *OrderService) GetDailyCashSales() (float64, error) {
	today := time.Now().Format("2006-01-02")
	return s.Repo.GetDailyCashSales(today)
}

func (s *OrderService) GetDailyPaymentCounts() (int, int, error) {
	today := time.Now().Format("2006-01-02")
	return s.Repo.GetDailyPaymentCounts(today)
}

func (s *OrderService) GetPaymentCounts(userID int, sessionID int) (int, int, error) {
	today := time.Now().Format("2006-01-02")
	if sessionID <= 0 {
		return s.Repo.GetFilteredPaymentCounts(today, userID, "", "")
	}

	session, err := s.sessionRepo.GetSessionByID(sessionID)
	if err != nil {
		return 0, 0, err
	}

	_, nonCashCount, err := s.Repo.GetFilteredPaymentCounts(today, session.UserID, session.StartTime, session.EndTime)
	if err != nil {
		return 0, 0, err
	}

	cashCount, err := s.cashierRepo.CountSales(session.ID)
	if err != nil {
		return 0, 0, err
	}

	return cashCount, nonCashCount, nil
}

func (s *OrderService) GetMonthlySales() (float64, error) {
	month := time.Now().Format("2006-01")
	return s.Repo.GetMonthlySales(month)
}

func (s *OrderService) GetDetailedSales() ([]map[string]interface{}, error) {
	return s.Repo.GetOrdersWithItems()
}
