package services

import (
	"math/rand"
	"pos-desktop/backend/models"
	"pos-desktop/backend/repository"
	"time"
)

type SettingService struct {
	Repo *repository.SettingRepository
}

func NewSettingService(repo *repository.SettingRepository) *SettingService {
	return &SettingService{Repo: repo}
}

func (s *SettingService) GetSettings() (*models.Setting, error) {
	settings, err := s.Repo.Get()
	if err != nil {
		return nil, err
	}

	// Generate random BridgeToken if empty
	if settings.BridgeToken == "" {
		settings.BridgeToken = s.generateRandomToken(16)
		s.Repo.Update(*settings)
	}

	return settings, nil
}

func (s *SettingService) UpdateSettings(setting models.Setting) error {
	return s.Repo.Update(setting)
}

func (s *SettingService) generateRandomToken(n int) string {
	rand.Seed(time.Now().UnixNano())
	const letters = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"
	b := make([]byte, n)
	for i := range b {
		b[i] = letters[rand.Intn(len(letters))]
	}
	return string(b)
}
