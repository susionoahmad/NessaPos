# SmartPOS Desktop Application

A complete Point Of Sale (POS) desktop application built with Wails, Golang, Vue 3, TypeScript, and SQLite, featuring Clean Architecture.

## System Requirements
- OS: Windows / macOS / Linux
- Go (1.20 or newer)
- Node.js & npm (16 or newer)

## Project Structure
```
pos-desktop/
├── backend/
│   ├── api/        # Wails exposed API bindings
│   ├── config/     # Configuration
│   ├── database/   # Database initialization and schemas
│   ├── models/     # Domain models (User, Product, etc)
│   ├── repository/ # Database access layer
│   └── services/   # Business logic
├── frontend/
│   ├── src/
│   │   ├── components/ # Reusable UI components
│   │   ├── pages/      # View pages (POS, Login, etc)
│   │   ├── store/      # Pinia store
│   │   └── router.ts   # Vue Router defining the app navigation
├── database/       # (Generated on run folder where pos.db is located)
├── main.go         # Application entry point
└── wails.json      # Wails configuration
```

## Features Complete:
- Cashier POS (Products Grid, Category filtering, Cart Management)
- User Authentication (Admin & Kasir) with proper Pinia session handling.
- Multiple Payment Mode support (Cash, Simulated QRIS)
- Print formatting for Thermal receipts.
- Database automatic scaffolding and initial data seeding.

## Running the Application
During development, the easiest way to launch the application is by using:
```bash
wails dev
```
This will compile the Golang backend, start the Vite frontend dev server, and open up the app window. Hot reload is fully supported!

## How to Build into .exe (Production)
For building a standalone desktop executable (.exe file on Windows):
```bash
wails build
```
Once the build concludes, you can find the production executable `pos-desktop.exe` under the `build/bin/` folder.

## Database & Default Data
SQLite Driver logic resides in `backend/database/sqlite.go`, using modernc.org/sqlite to support Windows without requiring GCC installation.
**Default Accounts Seeding:**
- Admin: username: `admin`, password: `admin123`
- Kasir: username: `kasir`, password: `kasir123`

The `pos.db` SQLite database is automatically generated inside the `/database/` directory when the app starts. It runs offline natively.
