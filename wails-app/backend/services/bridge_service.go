package services

import (
	"encoding/json"
	"fmt"
	"log"
	"net/http"
	"pos-desktop/backend/models"
	"strings"
)

type BridgeService struct {
	printService   *PrintService
	settingService *SettingService
}

func NewBridgeService(p *PrintService, s *SettingService) *BridgeService {
	return &BridgeService{
		printService:   p,
		settingService: s,
	}
}

func (s *BridgeService) Start() {
	port := 12348
	settings, err := s.settingService.GetSettings()
	if err != nil {
		log.Printf("Bridge: pengaturan belum ada, memakai port default %d: %v", port, err)
	} else if settings.BridgePort != 0 {
		port = settings.BridgePort
	}

	mux := http.NewServeMux()

	// Endpoints
	mux.HandleFunc("/status", s.handleStatus)
	mux.HandleFunc("/print/receipt", s.handlePrintReceipt)
	mux.HandleFunc("/print/session-open", s.handlePrintSessionOpen)
	mux.HandleFunc("/print/session-close", s.handlePrintSessionClose)
	mux.HandleFunc("/drawer/kick", s.handleKickDrawer)
	mux.HandleFunc("/printers", s.handleListPrinters)

	// Middleware: Auth + CORS + Localhost only
	handler := s.corsMiddleware(mux)

	go func() {
		addr := fmt.Sprintf(":%d", port)
		log.Printf("[Bridge] Server attempting to start on %s...", addr)
		// Success log message must be BEFORE ListenAndServe because it blocks
		log.Printf("[Bridge] Server is now listening on port %d", port)
		if err := http.ListenAndServe(addr, handler); err != nil {
			log.Printf("[Bridge] FATAL ERROR: %v. Port %d is already occupied by another process.", err, port)
			return
		}
	}()
}

func (s *BridgeService) corsMiddleware(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		origin := r.Header.Get("Origin")
		
		// Logging untuk diagnosa (sangat membantu saat dideploy)
		log.Printf("[Bridge Request] %s %s from %s (Origin: %s)", r.Method, r.URL.Path, r.RemoteAddr, origin)

		// Set standard CORS headers
		if origin != "" {
			w.Header().Set("Access-Control-Allow-Origin", origin)
		} else {
			w.Header().Set("Access-Control-Allow-Origin", "*")
		}

		w.Header().Set("Access-Control-Allow-Methods", "POST, GET, OPTIONS, PUT, DELETE")
		w.Header().Set("Access-Control-Allow-Headers", "X-Bridge-Token, Content-Type, Access-Control-Allow-Private-Network")
		
		// Support for Private Network Access (Chrome 94+)
		w.Header().Set("Access-Control-Allow-Private-Network", "true")
		
		// Credential support
		w.Header().Set("Access-Control-Allow-Credentials", "true")
		w.Header().Set("Access-Control-Max-Age", "1728000") // 20 days
		
		// Agar browser tidak bingung saat origin berpindah-pindah
		w.Header().Set("Vary", "Origin, Access-Control-Request-Private-Network")

		// Handle Preflight
		if r.Method == "OPTIONS" {
			w.WriteHeader(http.StatusNoContent)
			return
		}

		// Security: Localhost Check
		remoteAddr := r.RemoteAddr
		if idx := strings.LastIndex(remoteAddr, ":"); idx != -1 {
			remoteAddr = remoteAddr[:idx]
		}
		remoteAddr = strings.Trim(remoteAddr, "[]")

		isLocalhost := remoteAddr == "127.0.0.1" ||
			remoteAddr == "::1" ||
			strings.HasPrefix(remoteAddr, "::ffff:127.0.0.1")

		// Status check is public if accessed from localhost
		if r.URL.Path == "/status" && isLocalhost {
			next.ServeHTTP(w, r)
			return
		}

		// Token check for everything else
		settings, _ := s.settingService.GetSettings()
		
		// Jika token di database lokal kosong, dan akses dari localhost, izinkan saja (memudahkan setup awal)
		if settings.BridgeToken == "" {
			if isLocalhost {
				next.ServeHTTP(w, r)
				return
			}
			// Jika bukan localhost dan token kosong, tetap tolak demi keamanan
			log.Printf("[Bridge Auth] DENIED: No token configured in local bridge and request from non-localhost: %s", r.RemoteAddr)
			http.Error(w, "Bridge unauthorized: No token set", http.StatusUnauthorized)
			return
		}

		// Token verification
		token := r.Header.Get("X-Bridge-Token")
		if token == "" {
			token = r.URL.Query().Get("token")
		}

		// LOGIC PERMISIF KHUSUS LOCALHOST + TRUSTED ORIGIN
		// Jika dari localhost dan berasal dari domain Vercel Anda, izinkan cetak meskipun token salah.
		isTrustedOrigin := strings.Contains(origin, "vercel.app") || strings.Contains(origin, "nessapos.app") || origin == ""
		
		if isLocalhost && isTrustedOrigin {
			if token != settings.BridgeToken {
				log.Printf("[Bridge Bypass] Token mismatch but ALLOWED for localhost + trusted origin (%s)", origin)
			}
			next.ServeHTTP(w, r)
			return
		}

		if token != settings.BridgeToken {
			expectedPreview := ""
			if len(settings.BridgeToken) > 3 {
				expectedPreview = settings.BridgeToken[:3] + "..."
			} else {
				expectedPreview = settings.BridgeToken
			}
			
			receivedPreview := ""
			if len(token) > 3 {
				receivedPreview = token[:3] + "..."
			} else {
				receivedPreview = token
			}

			log.Printf("[Bridge Auth DENIED] Token mismatch from %s. Expected: [%s], Received: [%s]. Path: %s", 
				r.RemoteAddr, expectedPreview, receivedPreview, r.URL.Path)
			
			// Jika dari localhost, berikan pesan diagnosis di body agar mudah diperbaiki oleh dev
			if isLocalhost {
				w.Header().Set("Content-Type", "application/json")
				w.WriteHeader(http.StatusUnauthorized)
				json.NewEncoder(w).Encode(map[string]string{
					"error": "Unauthorized: Bridge token mismatch",
					"reason": fmt.Sprintf("Aplikasi Wails mengharapkan token [%s], tetapi browser mengirim [%s]", expectedPreview, receivedPreview),
					"solution": "Pastikan 'Bridge Token' di Pengaturan Browser sama dengan yang ada di Pengaturan Aplikasi Desktop Wails.",
				})
				return
			}

			http.Error(w, "Unauthorized: Bridge token mismatch", http.StatusUnauthorized)
			return
		}

		next.ServeHTTP(w, r)
	})
}

func min(a, b int) int {
	if a < b {
		return a
	}
	return b
}

func (s *BridgeService) handleStatus(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]string{
		"status":  "ok",
		"message": "NessaPOS Local Bridge is running",
	})
}

func (s *BridgeService) handleListPrinters(w http.ResponseWriter, r *http.Request) {
	printers, err := s.printService.ListPrinters()
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(printers)
}

func (s *BridgeService) handlePrintReceipt(w http.ResponseWriter, r *http.Request) {
	if r.Method != "POST" {
		http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}

	var req OrderRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		http.Error(w, "Invalid request body", http.StatusBadRequest)
		return
	}

	order := models.Order{
		UserID:       uint(req.UserID),
		TotalAmount:  req.TotalAmount,
		TaxAmount:    req.TaxAmount,
		Discount:     req.Discount,
		FinalAmount:  req.FinalAmount,
		AmountPaid:   req.AmountPaid,
		ChangeAmount: req.ChangeAmount,
		Status:       "Paid",
		Items:        req.Items,
	}

	settings, _ := s.settingService.GetSettings()
	header := PrintHeader{
		StoreName:    settings.StoreName,
		StoreAddress: settings.StoreAddress,
		StorePhone:   settings.StorePhone,
	}

	err := s.printService.PrintReceiptThermalWithPrinter(order, settings.PrinterName, header, settings.ReceiptText)
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}

	w.WriteHeader(http.StatusOK)
	fmt.Fprint(w, "Success")
}

func (s *BridgeService) handlePrintSessionOpen(w http.ResponseWriter, r *http.Request) {
	if r.Method != "POST" {
		http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}

	var req SessionOpenPrintRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		http.Error(w, "Invalid request body", http.StatusBadRequest)
		return
	}

	settings, _ := s.settingService.GetSettings()
	header := PrintHeader{
		StoreName:    settings.StoreName,
		StoreAddress: settings.StoreAddress,
		StorePhone:   settings.StorePhone,
	}

	err := s.printService.PrintSessionOpenReceipt(req, header, settings.PrinterName)
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}

	w.WriteHeader(http.StatusOK)
	fmt.Fprint(w, "Success")
}

func (s *BridgeService) handlePrintSessionClose(w http.ResponseWriter, r *http.Request) {
	if r.Method != "POST" {
		http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}

	var req SessionClosePrintRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		http.Error(w, "Invalid request body", http.StatusBadRequest)
		return
	}

	settings, _ := s.settingService.GetSettings()
	header := PrintHeader{
		StoreName:    settings.StoreName,
		StoreAddress: settings.StoreAddress,
		StorePhone:   settings.StorePhone,
	}

	err := s.printService.PrintSessionCloseReceipt(req, header, settings.PrinterName)
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}

	w.WriteHeader(http.StatusOK)
	fmt.Fprint(w, "Success")
}

func (s *BridgeService) handleKickDrawer(w http.ResponseWriter, r *http.Request) {
	if r.Method != "POST" {
		http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}

	settings, _ := s.settingService.GetSettings()
	if err := s.printService.KickDrawer(settings.PrinterName); err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}

	w.WriteHeader(http.StatusOK)
	fmt.Fprint(w, "Success")
}
