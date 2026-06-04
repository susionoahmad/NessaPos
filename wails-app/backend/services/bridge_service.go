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
		log.Printf("[Bridge] Attempting to start server on %s", addr)
		if err := http.ListenAndServe(addr, handler); err != nil {
			log.Printf("[Bridge] Critical Error: %v (Check if port %d is already in use)", err, port)
		}
	}()
}

func (s *BridgeService) corsMiddleware(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		// Gunakan origin dari request agar browser lebih percaya (penting untuk PNA)
		origin := r.Header.Get("Origin")
		if origin != "" {
			w.Header().Set("Access-Control-Allow-Origin", origin)
		} else {
			w.Header().Set("Access-Control-Allow-Origin", "*")
		}

		w.Header().Set("Access-Control-Allow-Methods", "POST, GET, OPTIONS")
		w.Header().Set("Access-Control-Allow-Headers", "X-Bridge-Token, Content-Type, Access-Control-Allow-Private-Network")

		// Header krusial untuk Private Network Access
		w.Header().Set("Access-Control-Allow-Private-Network", "true")
		// Beri tahu browser untuk mengingat izin ini selama 20 hari (cache preflight)
		w.Header().Set("Access-Control-Max-Age", "1728000")

		if r.Method == "OPTIONS" {
			w.WriteHeader(http.StatusNoContent)
			return
		}

		// Deteksi localhost yang lebih akurat (menghapus port dan bracket IPv6)
		remoteAddr := r.RemoteAddr
		if idx := strings.LastIndex(remoteAddr, ":"); idx != -1 {
			remoteAddr = remoteAddr[:idx]
		}
		remoteAddr = strings.Trim(remoteAddr, "[]")

		isLocalhost := remoteAddr == "127.0.0.1" || remoteAddr == "::1" || remoteAddr == "localhost" || strings.HasPrefix(remoteAddr, "::ffff:127.0.0.1")

		if isLocalhost {
			next.ServeHTTP(w, r)
			return
		}

		// Token check for non-localhost requests
		settings, _ := s.settingService.GetSettings()
		if settings.BridgeToken != "" {
			token := r.Header.Get("X-Bridge-Token")
			if token == "" {
				token = r.URL.Query().Get("token")
			}

			log.Printf("[Bridge Auth] From %s - Expected: %s..., Received: %s...",
				r.RemoteAddr,
				settings.BridgeToken[:min(5, len(settings.BridgeToken))],
				token[:min(5, len(token))])

			if token != settings.BridgeToken {
				log.Printf("[Bridge Auth DENIED] Token mismatch from %s", r.RemoteAddr)
				http.Error(w, "Unauthorized", http.StatusUnauthorized)
				return
			}
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
