package repository

import (
	"database/sql"
	"pos-desktop/backend/models"
)

type UserRepository struct {
	DB *sql.DB
}

func NewUserRepository(db *sql.DB) *UserRepository {
	return &UserRepository{DB: db}
}

func (r *UserRepository) FindByUsername(username string) (*models.User, error) {
	var user models.User
	err := r.DB.QueryRow("SELECT id, username, password, role FROM users WHERE username = ?", username).
		Scan(&user.ID, &user.Username, &user.Password, &user.Role)
	if err != nil {
		return nil, err
	}
	return &user, nil
}

func (r *UserRepository) FindByID(id int) (*models.User, error) {
	var user models.User
	err := r.DB.QueryRow("SELECT id, username, password, role FROM users WHERE id = ?", id).
		Scan(&user.ID, &user.Username, &user.Password, &user.Role)
	if err != nil {
		return nil, err
	}
	return &user, nil
}

func (r *UserRepository) GetAllUsers() ([]models.User, error) {
	rows, err := r.DB.Query("SELECT id, username, password, role FROM users ORDER BY username")
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var users []models.User
	for rows.Next() {
		var u models.User
		if err := rows.Scan(&u.ID, &u.Username, &u.Password, &u.Role); err != nil {
			return nil, err
		}
		users = append(users, u)
	}
	return users, nil
}

func (r *UserRepository) Create(u models.User) error {
	_, err := r.DB.Exec("INSERT INTO users (username, password, role) VALUES (?, ?, ?)", u.Username, u.Password, u.Role)
	return err
}

func (r *UserRepository) Update(u models.User) error {
	if u.Password != "" {
		_, err := r.DB.Exec("UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?", u.Username, u.Password, u.Role, u.ID)
		return err
	}
	_, err := r.DB.Exec("UPDATE users SET username = ?, role = ? WHERE id = ?", u.Username, u.Role, u.ID)
	return err
}

func (r *UserRepository) UpdatePassword(id int, newPassword string) error {
	_, err := r.DB.Exec("UPDATE users SET password = ? WHERE id = ?", newPassword, id)
	return err
}

func (r *UserRepository) Delete(id int) error {
	_, err := r.DB.Exec("DELETE FROM users WHERE id = ?", id)
	return err
}
