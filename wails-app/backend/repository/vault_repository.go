package repository

import (
	"database/sql"
	"pos-desktop/backend/models"
)

type VaultRepository struct {
	db *sql.DB
}

func NewVaultRepository(db *sql.DB) *VaultRepository {
	return &VaultRepository{db: db}
}

// GetVault gets the current vault balance
func (r *VaultRepository) GetVault() (*models.Vault, error) {
	query := `SELECT id, balance, updated_at FROM vault WHERE id = 1`
	var v models.Vault
	var updateStr string
	err := r.db.QueryRow(query).Scan(&v.ID, &v.Balance, &updateStr)
	if err != nil {
		return nil, err
	}
	return &v, nil
}

// UpdateVault updates the vault balance directly
func (r *VaultRepository) UpdateVault(tx *sql.Tx, newBalance float64) error {
	query := `UPDATE vault SET balance = ?, updated_at = CURRENT_TIMESTAMP WHERE id = 1`
	var err error
	if tx != nil {
		_, err = tx.Exec(query, newBalance)
	} else {
		_, err = r.db.Exec(query, newBalance)
	}
	return err
}

// RecordTransaction creates a new mutation record
func (r *VaultRepository) RecordTransaction(tx *sql.Tx, vtx *models.VaultTransaction) error {
	query := `
		INSERT INTO vault_transactions (type, amount, balance_after, description, created_by)
		VALUES (?, ?, ?, ?, ?)
	`
	var err error
	if tx != nil {
		_, err = tx.Exec(query, vtx.Type, vtx.Amount, vtx.BalanceAfter, vtx.Description, vtx.CreatedBy)
	} else {
		_, err = r.db.Exec(query, vtx.Type, vtx.Amount, vtx.BalanceAfter, vtx.Description, vtx.CreatedBy)
	}
	return err
}

func (r *VaultRepository) GetTransactions() ([]models.VaultTransaction, error) {
	query := `
		SELECT v.id, v.type, v.amount, v.balance_after, v.description, v.created_at, v.created_by, u.username
		FROM vault_transactions v
		JOIN users u ON v.created_by = u.id
		ORDER BY v.id DESC
	`
	rows, err := r.db.Query(query)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var txs []models.VaultTransaction
	for rows.Next() {
		var tx models.VaultTransaction
		err := rows.Scan(&tx.ID, &tx.Type, &tx.Amount, &tx.BalanceAfter, &tx.Description, &tx.CreatedAt, &tx.CreatedBy, &tx.Username)
		if err != nil {
			return nil, err
		}
		txs = append(txs, tx)
	}
	return txs, nil
}

func (r *VaultRepository) GetDB() *sql.DB {
	return r.db
}
