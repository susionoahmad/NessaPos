package database

import (
	"database/sql"
	"log"
	"os"
	"path/filepath"

	_ "modernc.org/sqlite"
)

var DB *sql.DB

func InitDB() {
	var err error

	// Create database directory if it doesn't exist
	dbDir := "database"
	if _, err := os.Stat(dbDir); os.IsNotExist(err) {
		os.Mkdir(dbDir, 0755)
	}

	dbPath := filepath.Join(dbDir, "pos.db")

	DB, err = sql.Open("sqlite", dbPath)
	if err != nil {
		log.Fatalf("Failed to connect to database: %v", err)
	}

	createTables()
	migrate()
	seedData()
}

func migrate() {
	// Add balance_after to vault_transactions if it doesn't exist
	_, _ = DB.Exec("ALTER TABLE vault_transactions ADD COLUMN balance_after REAL DEFAULT 0")

	_, _ = DB.Exec(`
		CREATE TABLE IF NOT EXISTS cashier_transactions (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			session_id INTEGER NOT NULL,
			user_id INTEGER NOT NULL,
			type TEXT NOT NULL,
			amount REAL NOT NULL,
			balance_after REAL DEFAULT 0,
			description TEXT,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			FOREIGN KEY (session_id) REFERENCES cashier_sessions(id),
			FOREIGN KEY (user_id) REFERENCES users(id)
		)
	`)

	_, _ = DB.Exec("ALTER TABLE order_items ADD COLUMN discount REAL DEFAULT 0")

	// Retail Upgrades
	_, _ = DB.Exec("ALTER TABLE products ADD COLUMN cost_price REAL DEFAULT 0")
	_, _ = DB.Exec("ALTER TABLE products ADD COLUMN selling_price REAL DEFAULT 0")
	_, _ = DB.Exec("ALTER TABLE products ADD COLUMN shelf_stock INTEGER DEFAULT 0")
	_, _ = DB.Exec("ALTER TABLE products ADD COLUMN warehouse_stock INTEGER DEFAULT 0")

	_, _ = DB.Exec(`
		CREATE TABLE IF NOT EXISTS stock_mutations (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			product_id INTEGER NOT NULL,
			type TEXT NOT NULL,
			from_location TEXT,
			to_location TEXT,
			quantity INTEGER NOT NULL,
			reference TEXT,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			created_by INTEGER,
			FOREIGN KEY (product_id) REFERENCES products(id)
		)
	`)

	_, _ = DB.Exec("ALTER TABLE settings ADD COLUMN tax_type TEXT DEFAULT 'exclusive'")
	_, _ = DB.Exec("ALTER TABLE settings ADD COLUMN printer_name TEXT DEFAULT ''")
	_, _ = DB.Exec("ALTER TABLE settings ADD COLUMN refresh_interval_sec INTEGER DEFAULT 30")
	_, _ = DB.Exec("ALTER TABLE settings ADD COLUMN print_session_slip INTEGER DEFAULT 1")
	_, _ = DB.Exec("ALTER TABLE settings ADD COLUMN cash_drawer_enabled INTEGER DEFAULT 1")
	_, _ = DB.Exec("ALTER TABLE settings ADD COLUMN trial_start TEXT DEFAULT ''")
	_, _ = DB.Exec("ALTER TABLE settings ADD COLUMN last_run TEXT DEFAULT ''")
	_, _ = DB.Exec("ALTER TABLE settings ADD COLUMN license_blob TEXT DEFAULT ''")
}

func createTables() {
	query := `
	CREATE TABLE IF NOT EXISTS users (
		id INTEGER PRIMARY KEY AUTOINCREMENT,
		username TEXT UNIQUE NOT NULL,
		password TEXT NOT NULL,
		role TEXT NOT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
	);

	CREATE TABLE IF NOT EXISTS categories (
		id INTEGER PRIMARY KEY AUTOINCREMENT,
		name TEXT UNIQUE NOT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
	);

	CREATE TABLE IF NOT EXISTS products (
		id INTEGER PRIMARY KEY AUTOINCREMENT,
		name TEXT NOT NULL,
		barcode TEXT UNIQUE,
		cost_price REAL DEFAULT 0,
		selling_price REAL DEFAULT 0,
		shelf_stock INTEGER DEFAULT 0,
		warehouse_stock INTEGER DEFAULT 0,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
	);

	CREATE TABLE IF NOT EXISTS orders (
		id INTEGER PRIMARY KEY AUTOINCREMENT,
		user_id INTEGER NOT NULL,
		total_amount REAL NOT NULL,
		tax_amount REAL NOT NULL,
		discount REAL NOT NULL,
		final_amount REAL NOT NULL,
		status TEXT NOT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		FOREIGN KEY (user_id) REFERENCES users(id)
	);

	CREATE TABLE IF NOT EXISTS order_items (
		id INTEGER PRIMARY KEY AUTOINCREMENT,
		order_id INTEGER NOT NULL,
		product_id INTEGER NOT NULL,
		product_name TEXT NOT NULL,
		quantity INTEGER NOT NULL,
		price REAL NOT NULL,
		discount REAL DEFAULT 0,
		total REAL NOT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		FOREIGN KEY (order_id) REFERENCES orders(id),
		FOREIGN KEY (product_id) REFERENCES products(id)
	);

	CREATE TABLE IF NOT EXISTS payments (
		id INTEGER PRIMARY KEY AUTOINCREMENT,
		order_id INTEGER NOT NULL,
		payment_method TEXT NOT NULL,
		amount_paid REAL NOT NULL,
		change_amount REAL NOT NULL,
		status TEXT NOT NULL,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		FOREIGN KEY (order_id) REFERENCES orders(id)
	);

	CREATE TABLE IF NOT EXISTS settings (
		id INTEGER PRIMARY KEY AUTOINCREMENT,
		store_name TEXT NOT NULL,
		store_address TEXT NOT NULL,
		store_phone TEXT NOT NULL,
		tax_rate REAL NOT NULL,
		tax_type TEXT DEFAULT 'exclusive',
		receipt_text TEXT NOT NULL,
		printer_name TEXT DEFAULT '',
		refresh_interval_sec INTEGER DEFAULT 30,
		print_session_slip INTEGER DEFAULT 1,
		cash_drawer_enabled INTEGER DEFAULT 1,
		trial_start TEXT DEFAULT '',
		last_run TEXT DEFAULT '',
		license_blob TEXT DEFAULT '',
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
	);

	CREATE TABLE IF NOT EXISTS cashier_sessions (
		id INTEGER PRIMARY KEY AUTOINCREMENT,
		user_id INTEGER NOT NULL,
		start_time DATETIME DEFAULT CURRENT_TIMESTAMP,
		end_time DATETIME,
		status TEXT NOT NULL,
		start_amount REAL NOT NULL,
		end_amount_calculated REAL,
		end_amount_physical REAL,
		difference REAL,
		start_denoms TEXT,
		end_denoms TEXT,
		FOREIGN KEY (user_id) REFERENCES users(id)
	);

	CREATE TABLE IF NOT EXISTS vault (
		id INTEGER PRIMARY KEY AUTOINCREMENT,
		balance REAL DEFAULT 0,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
	);

	CREATE TABLE IF NOT EXISTS vault_transactions (
		id INTEGER PRIMARY KEY AUTOINCREMENT,
		type TEXT NOT NULL,
		amount REAL NOT NULL,
		balance_after REAL DEFAULT 0,
		description TEXT,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		created_by INTEGER NOT NULL,
		FOREIGN KEY (created_by) REFERENCES users(id)
	);
	`

	_, err := DB.Exec(query)
	if err != nil {
		log.Fatalf("Failed to create tables: %v", err)
	}

	// Migration: Ensure products.category_id is nullable if it exists
	// This handles cases where old database files have a NOT NULL constraint
	// but the current app doesn't use categories.
	rows, err := DB.Query("PRAGMA table_info(products)")
	if err == nil {
		hasCategoryId := false
		isNotNull := false
		for rows.Next() {
			var cid int
			var name, dtype string
			var notnull, pk int
			var dflt_value interface{}
			rows.Scan(&cid, &name, &dtype, &notnull, &dflt_value, &pk)
			if name == "category_id" {
				hasCategoryId = true
				if notnull == 1 {
					isNotNull = true
				}
				break
			}
		}
		rows.Close()

		if hasCategoryId && isNotNull {
			// If it's NOT NULL, we try to set a default value or just ignore it by making it nullable
			// Simplest fix without recreating table: try to ADD a default if possible (not easy in SQLite)
			// Better: Just make it nullable by recreating (standard SQLite pattern)
			log.Println("Migrating products table to remove category_id constraint...")
			_, _ = DB.Exec("ALTER TABLE products RENAME TO products_old")
			_, _ = DB.Exec(query) // Re-create all tables including current products schema
			_, _ = DB.Exec(`
				INSERT INTO products (id, name, barcode, cost_price, selling_price, shelf_stock, warehouse_stock, created_at, updated_at)
				SELECT id, name, barcode, cost_price, selling_price, shelf_stock, warehouse_stock, created_at, updated_at 
				FROM products_old`)
			_, _ = DB.Exec("DROP TABLE products_old")
		}
	}
}

func seedData() {
	// Seed Admin
	var count int
	DB.QueryRow("SELECT COUNT(*) FROM users").Scan(&count)
	if count == 0 {
		DB.Exec("INSERT INTO users (username, password, role) VALUES ('admin', 'admin123', 'admin')")
		DB.Exec("INSERT INTO users (username, password, role) VALUES ('kasir', 'kasir123', 'kasir')")
	}

	// Seed Settings
	DB.QueryRow("SELECT COUNT(*) FROM settings").Scan(&count)
	if count == 0 {
		DB.Exec("INSERT INTO settings (store_name, store_address, store_phone, tax_rate, tax_type, receipt_text, printer_name, refresh_interval_sec, print_session_slip, cash_drawer_enabled, trial_start, last_run, license_blob) VALUES ('SmartPOS Store', 'Jl. Merdeka No 1', '08123456789', 10, 'exclusive', 'Terima kasih atas kunjungan Anda', '', 30, 1, 1, '', '', '')")
	}

	// Seed Categories
	DB.QueryRow("SELECT COUNT(*) FROM categories").Scan(&count)
	if count == 0 {
		DB.Exec("INSERT INTO categories (name) VALUES ('Makanan')")
		DB.Exec("INSERT INTO categories (name) VALUES ('Minuman')")
	}

	// Seed Products
	DB.QueryRow("SELECT COUNT(*) FROM products").Scan(&count)
	if count == 0 {
		DB.Exec("INSERT INTO products (name, barcode, cost_price, selling_price, shelf_stock, warehouse_stock) VALUES ('Indomie Goreng', '11111111', 2500, 3500, 50, 100)")
		DB.Exec("INSERT INTO products (name, barcode, cost_price, selling_price, shelf_stock, warehouse_stock) VALUES ('Gula Pasir', '22222222', 8000, 9500, 20, 50)")
	}

	// Seed Vault
	DB.QueryRow("SELECT COUNT(*) FROM vault").Scan(&count)
	if count == 0 {
		DB.Exec("INSERT INTO vault (balance) VALUES (0)")
	}
}
