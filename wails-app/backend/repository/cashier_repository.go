package repository

import (
	"database/sql"
	"pos-desktop/backend/models"
)

type CashierRepository struct {
	db *sql.DB
}

func NewCashierRepository(db *sql.DB) *CashierRepository {
	return &CashierRepository{db: db}
}

func (r *CashierRepository) RecordTransaction(tx *sql.Tx, ctx *models.CashierTransaction) error {
	query := `
		INSERT INTO cashier_transactions (session_id, user_id, type, amount, balance_after, description)
		VALUES (?, ?, ?, ?, ?, ?)
	`
	var err error
	if tx != nil {
		_, err = tx.Exec(query, ctx.SessionID, ctx.UserID, ctx.Type, ctx.Amount, ctx.BalanceAfter, ctx.Description)
	} else {
		_, err = r.db.Exec(query, ctx.SessionID, ctx.UserID, ctx.Type, ctx.Amount, ctx.BalanceAfter, ctx.Description)
	}
	return err
}

func (r *CashierRepository) GetTransactions() ([]models.CashierTransaction, error) {
	query := `
		SELECT c.id, c.session_id, c.user_id, u.username, c.type, c.amount, c.balance_after, c.description, c.created_at
		FROM cashier_transactions c
		JOIN users u ON c.user_id = u.id
		ORDER BY c.id DESC
	`
	rows, err := r.db.Query(query)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var txs []models.CashierTransaction
	for rows.Next() {
		var tx models.CashierTransaction
		err := rows.Scan(&tx.ID, &tx.SessionID, &tx.UserID, &tx.Username, &tx.Type, &tx.Amount, &tx.BalanceAfter, &tx.Description, &tx.CreatedAt)
		if err != nil {
			return nil, err
		}
		txs = append(txs, tx)
	}
	return txs, nil
}

func (r *CashierRepository) GetLatestBalance(sessionID int) (float64, error) {
	var balance float64
	query := `SELECT balance_after FROM cashier_transactions WHERE session_id = ? ORDER BY id DESC LIMIT 1`
	err := r.db.QueryRow(query, sessionID).Scan(&balance)
	if err == sql.ErrNoRows {
		return 0, nil
	}
	return balance, err
}
