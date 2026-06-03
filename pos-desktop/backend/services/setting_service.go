package services

import (
	"pos-desktop/backend/models"
	"pos-desktop/backend/repository"
)

type SettingService struct {
	Repo *repository.SettingRepository
}

func NewSettingService(repo *repository.SettingRepository) *SettingService {
	return &SettingService{Repo: repo}
}

func (s *SettingService) GetSettings() (*models.Setting, error) {
	return s.Repo.Get()
}

func (s *SettingService) UpdateSettings(setting models.Setting) error {
	return s.Repo.Update(setting)
}
