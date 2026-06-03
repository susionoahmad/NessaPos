package services

import (
	"errors"
	"strings"
	"pos-desktop/backend/models"
	"pos-desktop/backend/repository"

	"golang.org/x/crypto/bcrypt"
)

type UserService struct {
	Repo *repository.UserRepository
}

func NewUserService(repo *repository.UserRepository) *UserService {
	return &UserService{Repo: repo}
}

func (s *UserService) Login(username, password string) (*models.User, error) {
	if username == "" || password == "" {
		return nil, errors.New("username and password are required")
	}

	user, err := s.Repo.FindByUsername(username)
	if err != nil {
		return nil, errors.New("user not found")
	}

	ok, needsUpgrade, err := verifyPassword(user.Password, password)
	if err != nil || !ok {
		return nil, errors.New("invalid password")
	}
	if needsUpgrade {
		if hash, err := hashPassword(password); err == nil {
			_ = s.Repo.UpdatePassword(user.ID, hash)
		}
	}

	// don't send back password
	user.Password = ""

	return user, nil
}

func (s *UserService) ChangePassword(userID int, oldPassword, newPassword string) error {
	if oldPassword == "" || newPassword == "" {
		return errors.New("password lama dan baru wajib diisi")
	}
	if len(newPassword) < 6 {
		return errors.New("password baru minimal 6 karakter")
	}

	user, err := s.Repo.FindByID(userID)
	if err != nil {
		return err
	}
	ok, _, err := verifyPassword(user.Password, oldPassword)
	if err != nil || !ok {
		return errors.New("password lama salah")
	}

	hashed, err := hashPassword(newPassword)
	if err != nil {
		return err
	}
	return s.Repo.UpdatePassword(userID, hashed)
}

func (s *UserService) GetUsers() ([]models.User, error) {
	return s.Repo.GetAllUsers()
}

func (s *UserService) CreateUser(u models.User) error {
	if u.Password != "" {
		hashed, err := hashPassword(u.Password)
		if err != nil {
			return err
		}
		u.Password = hashed
	}
	return s.Repo.Create(u)
}

func (s *UserService) UpdateUser(u models.User) error {
	if u.Password != "" {
		hashed, err := hashPassword(u.Password)
		if err != nil {
			return err
		}
		u.Password = hashed
	}
	return s.Repo.Update(u)
}

func (s *UserService) DeleteUser(id int) error {
	return s.Repo.Delete(id)
}

func hashPassword(password string) (string, error) {
	hashed, err := bcrypt.GenerateFromPassword([]byte(password), bcrypt.DefaultCost)
	if err != nil {
		return "", err
	}
	return string(hashed), nil
}

func verifyPassword(storedHashOrPlain, password string) (bool, bool, error) {
	if strings.HasPrefix(storedHashOrPlain, "$2") {
		err := bcrypt.CompareHashAndPassword([]byte(storedHashOrPlain), []byte(password))
		if err != nil {
			return false, false, err
		}
		return true, false, nil
	}
	if storedHashOrPlain != password {
		return false, false, nil
	}
	return true, true, nil
}
