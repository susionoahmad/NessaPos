package repository

import (
	"database/sql"
	"pos-desktop/backend/models"
)

type SessionRepository struct {
	db *sql.DB
}

func NewSessionRepository(db *sql.DB) *SessionRepository {
	return &SessionRepository{db: db}
}

// GetOpenSession gets the current open session for a given user
func (r *SessionRepository) GetOpenSession(userID int) (*models.CashierSession, error) {
	query := `
		SELECT s.id, s.user_id, u.username, s.start_time, s.status, s.start_amount, s.start_denoms
		FROM cashier_sessions s
		JOIN users u ON s.user_id = u.id
		WHERE s.user_id = ? AND s.status = 'OPEN'
		ORDER BY s.id DESC
		LIMIT 1
	`
	row := r.db.QueryRow(query, userID)
	var session models.CashierSession
	err := row.Scan(&session.ID, &session.UserID, &session.Username, &session.StartTime, &session.Status, &session.StartAmount, &session.StartDenoms)
	if err != nil {
		return nil, err
	}
	return &session, nil
}

func (r *SessionRepository) GetSessionByID(sessionID int) (*models.CashierSession, error) {
	query := `
		SELECT s.id, s.user_id, u.username, s.start_time, IFNULL(s.end_time, ''), s.status, s.start_amount, s.start_denoms
		FROM cashier_sessions s
		JOIN users u ON s.user_id = u.id
		WHERE s.id = ?
	`
	row := r.db.QueryRow(query, sessionID)
	var session models.CashierSession
	err := row.Scan(&session.ID, &session.UserID, &session.Username, &session.StartTime, &session.EndTime, &session.Status, &session.StartAmount, &session.StartDenoms)
	if err != nil {
		return nil, err
	}
	return &session, nil
}

// OpenSession creates a new session
func (r *SessionRepository) OpenSession(session *models.CashierSession) error {
	query := `
		INSERT INTO cashier_sessions (user_id, status, start_amount, start_denoms)
		VALUES (?, 'OPEN', ?, ?)
	`
	result, err := r.db.Exec(query, session.UserID, session.StartAmount, session.StartDenoms)
	if err != nil {
		return err
	}
	id, err := result.LastInsertId()
	if err != nil {
		return err
	}
	session.ID = int(id)
	return nil
}

// CloseSession closes an active session
func (r *SessionRepository) CloseSession(session *models.CashierSession) error {
	query := `
		UPDATE cashier_sessions
		SET end_time = CURRENT_TIMESTAMP, status = 'CLOSED', 
			end_amount_calculated = ?, end_amount_physical = ?, 
			difference = ?, end_denoms = ?
		WHERE id = ?
	`
	_, err := r.db.Exec(query, session.EndAmountCalculated, session.EndAmountPhysical, session.Difference, session.EndDenoms, session.ID)
	return err
}

func (r *SessionRepository) GetAllSessions() ([]models.CashierSession, error) {
	query := `
		SELECT s.id, s.user_id, u.username, s.start_time, IFNULL(s.end_time, ''), s.status, s.start_amount, 
			IFNULL(s.end_amount_calculated, 0), IFNULL(s.end_amount_physical, 0), IFNULL(s.difference, 0)
		FROM cashier_sessions s
		JOIN users u ON s.user_id = u.id
		ORDER BY s.id DESC
	`
	rows, err := r.db.Query(query)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var sessions []models.CashierSession
	for rows.Next() {
		var s models.CashierSession
		err := rows.Scan(&s.ID, &s.UserID, &s.Username, &s.StartTime, &s.EndTime, &s.Status, &s.StartAmount, &s.EndAmountCalculated, &s.EndAmountPhysical, &s.Difference)
		if err != nil {
			return nil, err
		}
		sessions = append(sessions, s)
	}
	return sessions, nil
}

// GetSessionSales gets the total cash sales for this session (from cashier transactions)
func (r *SessionRepository) GetSessionSales(sessionID int) (float64, error) {
	query := `
		SELECT COALESCE(SUM(amount), 0)
		FROM cashier_transactions
		WHERE session_id = ? AND type = 'SALE'
	`
	var total float64
	err := r.db.QueryRow(query, sessionID).Scan(&total)
	return total, err
}
