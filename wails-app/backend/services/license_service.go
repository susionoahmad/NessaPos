package services

import (
	"bytes"
	"crypto/ed25519"
	"crypto/sha256"
	"encoding/base64"
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"net/http"
	"os"
	"os/exec"
	"runtime"
	"strings"
	"time"

	"pos-desktop/backend/models"
	"pos-desktop/backend/repository"

	"golang.org/x/sys/windows/registry"
)

const (
	trialDays              = 0 // Set ke 0 untuk menonaktifkan trial
	clockSkewTolerance     = 5 * time.Minute
	licensePublicKeyBase64 = "a1L4QmEhDlumadDKuCbcu0x1BOdbDjtF01S45G0MZYY="
	licenseStatusOK        = "ok"
	licenseStatusTrial     = "trial"
	licenseStatusExpired   = "expired"
	licenseStatusInvalid   = "invalid"
	licenseStatusTampered  = "tampered"
	licenseStatusMissing   = "missing"

	// URL server aktivasi Vercel
	// URL Production Vercel (Ganti jika nama project Anda berbeda)
	activationServerURL = "https://nessapos-api.vercel.app/api/activate"
)

type LicenseService struct {
	repo *repository.SettingRepository
}

type LicenseStatus struct {
	Status        string `json:"status"`
	Reason        string `json:"reason"`
	TrialDaysLeft int    `json:"trial_days_left"`
	TrialEndsAt   string `json:"trial_ends_at"`
	DeviceID      string `json:"device_id"`
	Licensee      string `json:"licensee"`
	LicenseExpiry string `json:"license_expiry"`
}

type LicensePayload struct {
	IssuedTo string `json:"issued_to"`
	DeviceID string `json:"device_id"`
	IssuedAt string `json:"issued_at"`
	Expiry   string `json:"expiry"`
}

type LicenseFile struct {
	Payload   LicensePayload `json:"payload"`
	Signature string         `json:"signature"`
}

func NewLicenseService(repo *repository.SettingRepository) *LicenseService {
	return &LicenseService{repo: repo}
}

func (s *LicenseService) GetStatus() (*LicenseStatus, error) {
	settings, err := s.repo.Get()
	if err != nil {
		return nil, err
	}

	deviceID := getDeviceID()
	now := time.Now()

	if settings.LastRun != "" {
		if last, err := time.Parse(time.RFC3339, settings.LastRun); err == nil {
			if now.Before(last.Add(-clockSkewTolerance)) {
				return &LicenseStatus{
					Status:   licenseStatusTampered,
					Reason:   "Waktu sistem mundur terdeteksi",
					DeviceID: deviceID,
				}, nil
			}
		}
	}

	if strings.TrimSpace(settings.LicenseBlob) != "" {
		ok, payload, reason := verifyLicense(settings.LicenseBlob, deviceID)
		if ok {
			s.touchLastRun(settings, now)
			return &LicenseStatus{
				Status:        licenseStatusOK,
				DeviceID:      deviceID,
				Licensee:      payload.IssuedTo,
				LicenseExpiry: payload.Expiry,
			}, nil
		}
		return &LicenseStatus{
			Status:   licenseStatusInvalid,
			Reason:   reason,
			DeviceID: deviceID,
		}, nil
	}

	trialStart, updated := ensureTrialStart(settings, now)
	if updated {
		_ = s.repo.Update(*settings)
	}
	daysLeft, endsAt := trialDaysLeft(trialStart, now)
	if daysLeft > 0 { // Hanya jika masih ada sisa hari
		s.touchLastRun(settings, now)
		return &LicenseStatus{
			Status:        licenseStatusTrial,
			TrialDaysLeft: daysLeft,
			TrialEndsAt:   endsAt,
			DeviceID:      deviceID,
		}, nil
	}

	return &LicenseStatus{
		Status:   licenseStatusExpired,
		Reason:   "Aplikasi membutuhkan lisensi untuk digunakan (Trial tidak aktif/habis).",
		DeviceID: deviceID,
	}, nil
}

func (s *LicenseService) ActivateLicense(licenseText string) error {
	settings, err := s.repo.Get()
	if err != nil {
		return err
	}
	deviceID := getDeviceID()
	ok, _, reason := verifyLicense(licenseText, deviceID)
	if !ok {
		return errors.New(reason)
	}
	settings.LicenseBlob = licenseText
	settings.LastRun = time.Now().Format(time.RFC3339)
	return s.repo.Update(*settings)
}

func (s *LicenseService) ActivateOnline(serialKey string) error {
	deviceID := getDeviceID()

	reqBody, _ := json.Marshal(map[string]string{
		"serial_key": serialKey,
		"device_id":  deviceID,
		"app_name":   "NessaDesktop",
	})

	client := &http.Client{Timeout: 15 * time.Second}
	resp, err := client.Post(activationServerURL, "application/json", bytes.NewBuffer(reqBody))
	if err != nil {
		return errors.New("Gagal menghubungi server aktivasi. Pastikan koneksi internet tersedia.")
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		body, _ := io.ReadAll(resp.Body)
		var errResp struct {
			Message string `json:"message"`
		}
		json.Unmarshal(body, &errResp)
		
		if errResp.Message != "" {
			return errors.New(errResp.Message)
		}
		// Jika tidak ada pesan JSON, tampilkan mentahnya
		return fmt.Errorf("Server Error (%d): %s", resp.StatusCode, string(body))
	}

	body, err := io.ReadAll(resp.Body)
	if err != nil {
		return errors.New("Gagal membaca respon dari server")
	}

	// Simpan blob dan verifikasi
	return s.ActivateLicense(string(body))
}

func (s *LicenseService) touchLastRun(settings *models.Setting, now time.Time) {
	settings.LastRun = now.Format(time.RFC3339)
	_ = s.repo.Update(*settings)
}

func ensureTrialStart(settings *models.Setting, now time.Time) (time.Time, bool) {
	if settings.TrialStart == "" {
		settings.TrialStart = now.Format(time.RFC3339)
		return now, true
	}
	if t, err := time.Parse(time.RFC3339, settings.TrialStart); err == nil {
		return t, false
	}
	settings.TrialStart = now.Format(time.RFC3339)
	return now, true
}

func trialDaysLeft(start time.Time, now time.Time) (int, string) {
	ends := start.AddDate(0, 0, trialDays)
	diff := ends.Sub(now)
	days := int(diff.Hours() / 24)
	return days, ends.Format("2006-01-02")
}

func verifyLicense(licenseText, deviceID string) (bool, LicensePayload, string) {
	var lf LicenseFile
	if err := json.Unmarshal([]byte(licenseText), &lf); err != nil {
		return false, LicensePayload{}, "Format license tidak valid"
	}
	if lf.Signature == "" {
		return false, LicensePayload{}, "Signature license kosong"
	}
	if strings.TrimSpace(lf.Payload.DeviceID) != strings.TrimSpace(deviceID) {
		return false, LicensePayload{}, "License tidak cocok untuk perangkat ini"
	}
	if lf.Payload.Expiry != "" {
		if exp, err := time.Parse("2006-01-02", lf.Payload.Expiry); err == nil {
			if time.Now().After(exp.Add(24 * time.Hour)) {
				return false, LicensePayload{}, "License sudah expired"
			}
		}
	}

	pubKeyBytes, err := base64.StdEncoding.DecodeString(licensePublicKeyBase64)
	if err != nil || len(pubKeyBytes) != ed25519.PublicKeySize {
		return false, LicensePayload{}, "Public key invalid"
	}
	pubKey := ed25519.PublicKey(pubKeyBytes)

	sig, err := base64.StdEncoding.DecodeString(lf.Signature)
	if err != nil {
		return false, LicensePayload{}, "Signature base64 invalid"
	}
	msg := []byte(formatLicensePayload(lf.Payload))
	if !ed25519.Verify(pubKey, msg, sig) {
		return false, LicensePayload{}, "Signature tidak valid"
	}
	return true, lf.Payload, ""
}

func formatLicensePayload(p LicensePayload) string {
	return fmt.Sprintf("%s|%s|%s|%s", p.IssuedTo, p.DeviceID, p.IssuedAt, p.Expiry)
}

func getDeviceID() string {
	base := getMachineGUID()
	mb := getBaseboardID()
	if base == "" && mb == "" {
		base, _ = os.Hostname()
	}
	// Menggabungkan MachineGuid dan Motherboard Serial agar lebih unik & aman
	combined := fmt.Sprintf("%s|%s|nessa-pos", strings.TrimSpace(base), strings.TrimSpace(mb))
	sum := sha256.Sum256([]byte(strings.ToLower(combined)))
	return base64.StdEncoding.EncodeToString(sum[:])
}

func getBaseboardID() string {
	if runtime.GOOS != "windows" {
		return ""
	}
	// Menggunakan powershell untuk mendapatkan serial number motherboard
	cmd := exec.Command("powershell", "-Command", "Get-CimInstance Win32_BaseBoard | Select-Object -ExpandProperty SerialNumber")
	out, err := cmd.Output()
	if err != nil {
		return ""
	}
	return strings.TrimSpace(string(out))
}

func getMachineGUID() string {
	if runtime.GOOS != "windows" {
		return ""
	}
	key, err := registry.OpenKey(registry.LOCAL_MACHINE, `SOFTWARE\Microsoft\Cryptography`, registry.QUERY_VALUE)
	if err != nil {
		return ""
	}
	defer key.Close()
	guid, _, err := key.GetStringValue("MachineGuid")
	if err != nil {
		return ""
	}
	return guid
}
