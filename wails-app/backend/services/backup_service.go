package services

import (
	"fmt"
	"io"
	"os"
	"path/filepath"
	"sort"
	"time"
)

type BackupService struct {
	dbPath     string
	backupDir  string
	maxBackups int
}

func NewBackupService(dbPath string) *BackupService {
	// Gunakan working directory agar lebih konsisten saat dev maupun produksi
	baseDir, _ := os.Getwd()
	backupDir := filepath.Join(baseDir, "backups")

	return &BackupService{
		dbPath:     dbPath,
		backupDir:  backupDir,
		maxBackups: 5, // Simpan 5 backup terakhir
	}
}

func (s *BackupService) PerformBackup() error {
	// 1. Pastikan folder backup ada
	if _, err := os.Stat(s.backupDir); os.IsNotExist(err) {
		err := os.MkdirAll(s.backupDir, 0755)
		if err != nil {
			return fmt.Errorf("gagal membuat folder backup: %v", err)
		}
	}

	// 2. Cek apakah file DB ada
	if _, err := os.Stat(s.dbPath); os.IsNotExist(err) {
		return fmt.Errorf("file database tidak ditemukan: %s", s.dbPath)
	}

	// 3. Buat nama file backup (pos_backup_20230501_120000.db)
	timestamp := time.Now().Format("20060102_150405")
	destPath := filepath.Join(s.backupDir, fmt.Sprintf("pos_backup_%s.db", timestamp))

	// 4. Proses Copy
	err := s.copyFile(s.dbPath, destPath)
	if err != nil {
		return fmt.Errorf("gagal menyalin file database: %v", err)
	}

	// 5. Bersihkan backup lama
	s.cleanupOldBackups()

	fmt.Printf("Backup berhasil dibuat: %s\n", destPath)
	return nil
}

func (s *BackupService) copyFile(src, dst string) error {
	sourceFile, err := os.Open(src)
	if err != nil {
		return err
	}
	defer sourceFile.Close()

	destFile, err := os.Create(dst)
	if err != nil {
		return err
	}
	defer destFile.Close()

	_, err = io.Copy(destFile, sourceFile)
	if err != nil {
		return err
	}

	return destFile.Sync()
}

func (s *BackupService) cleanupOldBackups() {
	files, err := os.ReadDir(s.backupDir)
	if err != nil {
		return
	}

	var backupFiles []os.DirEntry
	for _, f := range files {
		if !f.IsDir() && filepath.Ext(f.Name()) == ".db" {
			backupFiles = append(backupFiles, f)
		}
	}

	if len(backupFiles) <= s.maxBackups {
		return
	}

	// Urutkan berdasarkan waktu modifikasi (tertua di atas)
	sort.Slice(backupFiles, func(i, j int) bool {
		infoI, _ := backupFiles[i].Info()
		infoJ, _ := backupFiles[j].Info()
		return infoI.ModTime().Before(infoJ.ModTime())
	})

	// Hapus file tertua hingga sisa maxBackups
	toDelete := len(backupFiles) - s.maxBackups
	for i := 0; i < toDelete; i++ {
		os.Remove(filepath.Join(s.backupDir, backupFiles[i].Name()))
	}
}
