package main

import (
	"bufio"
	"crypto/ed25519"
	"encoding/base64"
	"encoding/json"
	"fmt"
	"os"
	"strings"
	"time"
)

// Konfigurasi: Samakan dengan license_service.go
const privateKeyFile = "backend/services/PRIVATE_KEY"

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

func main() {
	reader := bufio.NewReader(os.Stdin)

	fmt.Println("======================================")
	fmt.Println("   NESSA POS LICENSE GENERATOR (v2)  ")
	fmt.Println("======================================")

	// 1. Load Private Key
	privKeyB64, err := os.ReadFile(privateKeyFile)
	if err != nil {
		fmt.Printf("Error: Gagal membaca file PRIVATE_KEY di %s\n", privateKeyFile)
		fmt.Println("Pastikan file tersebut ada.")
		return
	}

	privKeyBytes, err := base64.StdEncoding.DecodeString(strings.TrimSpace(string(privKeyB64)))
	if err != nil {
		fmt.Println("Error: Format PRIVATE_KEY di file tidak valid (bukan base64)")
		return
	}
	privKey := ed25519.PrivateKey(privKeyBytes)

	// 2. Input Data Pembeli
	fmt.Print("1. Nama Pembeli/Toko: ")
	issuedTo, _ := reader.ReadString('\n')
	issuedTo = strings.TrimSpace(issuedTo)

	fmt.Print("2. Device ID Pembeli: ")
	deviceID, _ := reader.ReadString('\n')
	deviceID = strings.TrimSpace(deviceID)

	fmt.Print("3. Masa Berlaku (jumlah hari, misal: 3 untuk demo, 365 untuk 1 tahun): ")
	durationStr, _ := reader.ReadString('\n')
	durationStr = strings.TrimSpace(durationStr)
	
	days := 365 // default
	if durationStr != "" {
		fmt.Sscanf(durationStr, "%d", &days)
	}

	// 3. Hitung Tanggal
	now := time.Now()
	issuedAt := now.Format(time.RFC3339)
	expiry := now.AddDate(0, 0, days).Format("2006-01-02")

	if durationStr == "0" {
		expiry = "" // Permanen
	}

	// 4. Buat Payload & Signature
	payload := LicensePayload{
		IssuedTo: issuedTo,
		DeviceID: deviceID,
		IssuedAt: issuedAt,
		Expiry:   expiry,
	}

	msg := fmt.Sprintf("%s|%s|%s|%s", payload.IssuedTo, payload.DeviceID, payload.IssuedAt, payload.Expiry)
	sig := ed25519.Sign(privKey, []byte(msg))

	// 5. Output JSON
	license := LicenseFile{
		Payload:   payload,
		Signature: base64.StdEncoding.EncodeToString(sig),
	}

	jsonBytes, _ := json.MarshalIndent(license, "", "  ")

	fmt.Println("\n======================================")
	fmt.Println("BERHASIL! COPY JSON DI BAWAH INI:")
	fmt.Println("======================================")
	fmt.Println(string(jsonBytes))
	fmt.Println("======================================")
	fmt.Println("Kirim teks JSON di atas ke pembeli.")
	fmt.Println("Tekan Enter untuk keluar...")
	reader.ReadString('\n')
}
