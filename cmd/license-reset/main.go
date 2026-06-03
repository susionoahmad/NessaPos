package main

import (
	"database/sql"
	"fmt"
	"log"
	"os"
	"path/filepath"

	_ "modernc.org/sqlite"
)

func main() {
	fmt.Println("======================================")
	fmt.Println("   RESET LISENSI NESSA POS (via Go)   ")
	fmt.Println("======================================")

	dbPath := filepath.Join("database", "pos.db")
	
	// Cek apakah file DB ada
	if _, err := os.Stat(dbPath); os.IsNotExist(err) {
		fmt.Println("Database tidak ditemukan di:", dbPath)
		return
	}

	db, err := sql.Open("sqlite", dbPath)
	if err != nil {
		log.Fatal(err)
	}
	defer db.Close()

	// Reset kolom lisensi di tabel settings
	query := "UPDATE settings SET license_blob = '', last_run = '', trial_start = '' WHERE id = 1"
	_, err = db.Exec(query)
	if err != nil {
		fmt.Printf("Error saat meriset database: %v\n", err)
		fmt.Println("Pastikan aplikasi NessaDesktop sudah ditutup!")
		return
	}

	fmt.Println("[SUKSES] Data lisensi telah dibersihkan.")
	fmt.Println("Silakan buka kembali aplikasi NessaDesktop.")
}
