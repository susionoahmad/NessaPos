package repository

import (
	"database/sql"
	"pos-desktop/backend/models"
)

type SettingRepository struct {
	DB *sql.DB
}

func NewSettingRepository(db *sql.DB) *SettingRepository {
	return &SettingRepository{DB: db}
}

func (r *SettingRepository) Get() (*models.Setting, error) {
	var s models.Setting
	err := r.DB.QueryRow("SELECT id, store_name, store_address, store_phone, tax_rate, tax_type, receipt_text, printer_name, refresh_interval_sec, print_session_slip, trial_start, last_run, license_blob FROM settings LIMIT 1").
		Scan(&s.ID, &s.StoreName, &s.StoreAddress, &s.StorePhone, &s.TaxRate, &s.TaxType, &s.ReceiptText, &s.PrinterName, &s.RefreshIntervalSec, &s.PrintSessionSlip, &s.TrialStart, &s.LastRun, &s.LicenseBlob)
	if err != nil {
		return nil, err
	}
	return &s, nil
}

func (r *SettingRepository) Update(s models.Setting) error {
	_, err := r.DB.Exec("UPDATE settings SET store_name=?, store_address=?, store_phone=?, tax_rate=?, tax_type=?, receipt_text=?, printer_name=?, refresh_interval_sec=?, print_session_slip=?, trial_start=?, last_run=?, license_blob=? WHERE id=?",
		s.StoreName, s.StoreAddress, s.StorePhone, s.TaxRate, s.TaxType, s.ReceiptText, s.PrinterName, s.RefreshIntervalSec, s.PrintSessionSlip, s.TrialStart, s.LastRun, s.LicenseBlob, s.ID)
	return err
}
