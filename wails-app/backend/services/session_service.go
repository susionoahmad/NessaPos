package services

import (
	"errors"
	"pos-desktop/backend/models"
	"pos-desktop/backend/repository"
)

type SessionService struct {
	repo         *repository.SessionRepository
	vaultService *VaultService
	cashierRepo  *repository.CashierRepository
}

func NewSessionService(repo *repository.SessionRepository, vaultService *VaultService, cashierRepo *repository.CashierRepository) *SessionService {
	return &SessionService{repo: repo, vaultService: vaultService, cashierRepo: cashierRepo}
}

// GetOpenSession gets current session
func (s *SessionService) GetOpenSession(userID int) (*models.CashierSession, error) {
	session, err := s.repo.GetOpenSession(userID)
	if err != nil {
		// return nil if no session found, sql.ErrNoRows
		if err.Error() == "sql: no rows in result set" {
			return nil, nil // No open session
		}
		return nil, err
	}
	// Calculate current end_amount based on sales so far
	sales, _ := s.GetSessionSales(session.ID, session.UserID, session.StartTime)
	session.EndAmountCalculated = session.StartAmount + sales
	return session, nil
}

// OpenSession creates a new session and reduces vaulted cash
func (s *SessionService) OpenSession(userID int, startAmount float64, startDenoms string) (*models.CashierSession, error) {
	existing, _ := s.GetOpenSession(userID)
	if existing != nil {
		return nil, errors.New("kasir masih memiliki sesi yang terbuka")
	}

	// Reduce vault balance
	err := s.vaultService.ProcessSessionOpen(startAmount, userID)
	if err != nil {
		return nil, err
	}

	session := &models.CashierSession{
		UserID:      userID,
		StartAmount: startAmount,
		StartDenoms: startDenoms,
	}

	err = s.repo.OpenSession(session)
	if err != nil {
		return nil, err
	}

	// Record cashier mutation
	_ = s.cashierRepo.RecordTransaction(nil, &models.CashierTransaction{
		SessionID:    session.ID,
		UserID:       userID,
		Type:         "SESSION_START",
		Amount:       startAmount,
		BalanceAfter: startAmount,
		Description:  "Buka Sesi (Modal Awal)",
	})

	return session, nil
}

// CloseSession closes session and transfers cash to vault
func (s *SessionService) CloseSession(userID int, endAmountPhysical float64, endDenoms string) (*models.CashierSession, error) {
	session, err := s.repo.GetOpenSession(userID)
	if err != nil || session == nil {
		return nil, errors.New("tidak ada sesi terbuka untuk pengguna ini")
	}

	sales, err := s.GetSessionSales(session.ID, session.UserID, session.StartTime)
	if err != nil {
		return nil, err
	}

	calculated := session.StartAmount + sales
	difference := endAmountPhysical - calculated

	session.EndAmountCalculated = calculated
	session.EndAmountPhysical = endAmountPhysical
	session.Difference = difference
	session.EndDenoms = endDenoms

	err = s.repo.CloseSession(session)
	if err != nil {
		return nil, err
	}

	// Record cashier mutation
	_ = s.cashierRepo.RecordTransaction(nil, &models.CashierTransaction{
		SessionID:    session.ID,
		UserID:       userID,
		Type:         "SESSION_END",
		Amount:       -endAmountPhysical,
		BalanceAfter: 0, // Drawer emptied
		Description:  "Tutup Sesi (Setor ke Brankas)",
	})

	// Add physical cash back to vault
	err = s.vaultService.ProcessSessionClose(endAmountPhysical, userID)
	if err != nil {
		return nil, err
	}

	return session, nil
}

// GetAllSessions gets all sessions history
func (s *SessionService) GetAllSessions() ([]models.CashierSession, error) {
	return s.repo.GetAllSessions()
}

// GetSessionSales gets the total cash sales for this session
func (s *SessionService) GetSessionSales(sessionID int, userID int, startTime string) (float64, error) {
	return s.repo.GetSessionSales(sessionID)
}

func (s *SessionService) GetCashierTransactions() ([]models.CashierTransaction, error) {
	return s.cashierRepo.GetTransactions()
}

// GetCurrentBalance returns latest cashier drawer balance for open session
func (s *SessionService) GetCurrentBalance(userID int) (float64, error) {
	session, err := s.repo.GetOpenSession(userID)
	if err != nil || session == nil {
		return 0, err
	}
	return s.cashierRepo.GetLatestBalance(session.ID)
}
