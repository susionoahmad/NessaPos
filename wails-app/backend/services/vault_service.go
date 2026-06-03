package services

import (
	"errors"
	"pos-desktop/backend/models"
	"pos-desktop/backend/repository"
)

type VaultService struct {
	repo *repository.VaultRepository
}

func NewVaultService(repo *repository.VaultRepository) *VaultService {
	return &VaultService{repo: repo}
}

func (s *VaultService) GetVault() (*models.Vault, error) {
	return s.repo.GetVault()
}

func (s *VaultService) GetTransactions() ([]models.VaultTransaction, error) {
	return s.repo.GetTransactions()
}

// AddCapital adds capital to the vault (by Admin)
func (s *VaultService) AddCapital(amount float64, adminID int, description string) error {
	if amount <= 0 {
		return errors.New("jumlah modal harus lebih dari 0")
	}

	db := s.repo.GetDB()
	tx, err := db.Begin()
	if err != nil {
		return err
	}
	defer tx.Rollback()

	vault, err := s.repo.GetVault()
	if err != nil {
		return err
	}

	newBalance := vault.Balance + amount

	err = s.repo.UpdateVault(tx, newBalance)
	if err != nil {
		return err
	}

	vtx := &models.VaultTransaction{
		Type:         "ADD_CAPITAL",
		Amount:       amount,
		BalanceAfter: newBalance,
		Description:  description,
		CreatedBy:    adminID,
	}

	err = s.repo.RecordTransaction(tx, vtx)
	if err != nil {
		return err
	}

	return tx.Commit()
}

// ProcessSessionOpen reduces vault balance and records transaction
func (s *VaultService) ProcessSessionOpen(amount float64, userID int) error {
	db := s.repo.GetDB()
	tx, err := db.Begin()
	if err != nil {
		return err
	}
	defer tx.Rollback()

	vault, err := s.repo.GetVault()
	if err != nil {
		return err
	}

	if vault.Balance < amount {
		return errors.New("saldo brankas tidak mencukupi untuk modal awal")
	}

	newBalance := vault.Balance - amount
	err = s.repo.UpdateVault(tx, newBalance)
	if err != nil {
		return err
	}

	vtx := &models.VaultTransaction{
		Type:         "SESSION_START",
		Amount:       -amount,
		BalanceAfter: newBalance,
		Description:  "Buka Sesi Kasir",
		CreatedBy:    userID,
	}
	err = s.repo.RecordTransaction(tx, vtx)
	if err != nil {
		return err
	}

	return tx.Commit()
}

// ProcessSessionClose increases vault balance and records transaction
func (s *VaultService) ProcessSessionClose(amount float64, userID int) error {
	db := s.repo.GetDB()
	tx, err := db.Begin()
	if err != nil {
		return err
	}
	defer tx.Rollback()

	vault, err := s.repo.GetVault()
	if err != nil {
		return err
	}

	newBalance := vault.Balance + amount
	err = s.repo.UpdateVault(tx, newBalance)
	if err != nil {
		return err
	}

	vtx := &models.VaultTransaction{
		Type:         "SESSION_END",
		Amount:       amount,
		BalanceAfter: newBalance,
		Description:  "Tutup Sesi Kasir",
		CreatedBy:    userID,
	}
	err = s.repo.RecordTransaction(tx, vtx)
	if err != nil {
		return err
	}

	return tx.Commit()
}
