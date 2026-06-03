package main

import (
	"crypto/ed25519"
	"encoding/base64"
	"encoding/json"
	"flag"
	"fmt"
	"os"
	"strings"
)

type payload struct {
	IssuedTo string `json:"issued_to"`
	DeviceID string `json:"device_id"`
	IssuedAt string `json:"issued_at"`
	Expiry   string `json:"expiry"`
}

type licenseFile struct {
	Payload   payload `json:"payload"`
	Signature string  `json:"signature"`
}

func main() {
	genKey := flag.Bool("gen-key", false, "Generate ed25519 keypair (base64)")
	issuedTo := flag.String("issued-to", "", "License issued to")
	deviceID := flag.String("device-id", "", "Device ID")
	issuedAt := flag.String("issued-at", "", "Issued date YYYY-MM-DD")
	expiry := flag.String("expiry", "", "Expiry date YYYY-MM-DD (optional)")
	privKeyB64 := flag.String("priv-key", "", "Base64 ed25519 private key")
	flag.Parse()

	if *genKey {
		pub, priv, err := ed25519.GenerateKey(nil)
		if err != nil {
			fmt.Fprintln(os.Stderr, "generate key failed:", err)
			os.Exit(1)
		}
		fmt.Println("PUBLIC_KEY_BASE64:", base64.StdEncoding.EncodeToString(pub))
		fmt.Println("PRIVATE_KEY_BASE64:", base64.StdEncoding.EncodeToString(priv))
		return
	}

	if strings.TrimSpace(*issuedTo) == "" ||
		strings.TrimSpace(*deviceID) == "" ||
		strings.TrimSpace(*issuedAt) == "" ||
		strings.TrimSpace(*privKeyB64) == "" {
		fmt.Fprintln(os.Stderr, "missing required flags. Use -issued-to, -device-id, -issued-at, -priv-key")
		os.Exit(1)
	}

	privBytes, err := base64.StdEncoding.DecodeString(*privKeyB64)
	if err != nil || len(privBytes) != ed25519.PrivateKeySize {
		fmt.Fprintln(os.Stderr, "invalid private key")
		os.Exit(1)
	}
	priv := ed25519.PrivateKey(privBytes)

	p := payload{
		IssuedTo: *issuedTo,
		DeviceID: *deviceID,
		IssuedAt: *issuedAt,
		Expiry:   *expiry,
	}
	msg := []byte(formatPayload(p))
	sig := ed25519.Sign(priv, msg)

	lf := licenseFile{
		Payload:   p,
		Signature: base64.StdEncoding.EncodeToString(sig),
	}

	out, err := json.MarshalIndent(lf, "", "  ")
	if err != nil {
		fmt.Fprintln(os.Stderr, "marshal failed:", err)
		os.Exit(1)
	}
	fmt.Println(string(out))
}

func formatPayload(p payload) string {
	return fmt.Sprintf("%s|%s|%s|%s", p.IssuedTo, p.DeviceID, p.IssuedAt, p.Expiry)
}
