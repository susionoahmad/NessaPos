# NessaPOS Repository

NessaPOS is a POS SaaS project with a public landing site, browser POS app, centralized API, desktop app, and local thermal print support.

## System Requirements

- Windows, macOS, or Linux
- Go 1.20 or newer
- Node.js and npm 16 or newer
- PHP and Composer for the API server

## Project Structure

```text
pos-online/
├── frontend-landing/  # Public landing page and SEO blog
├── frontend-pos/      # Browser POS app for cashier/admin users
├── laravel-backend/   # Central API server
├── wails-app/         # Desktop app and local device integration
├── go-print-service/  # Local thermal printer helper
├── database/          # Runtime SQLite database output for desktop mode
└── README.md
```

Landing content is intentionally non-technical. Developer details live in this README and in `laravel-backend/README.md`.

## Product Modules

- Landing page: conversion-focused public website and educational blog.
- POS app: cashier, products, stock, reports, settings, and admin flows.
- API backend: authentication, products, transactions, tenants, subscription, and central data.
- Desktop app: local desktop mode and bridge to device features.
- Print service: thermal receipt formatting and printer communication.

## Running The Desktop App

During development, the easiest way to launch the desktop app is:

```bash
cd wails-app
wails dev
```

This starts the desktop shell and loads the POS frontend for development.

## Building The Desktop App

```bash
cd wails-app
wails build
```

From the repository root, you can also run:

```powershell
./build-desktop.ps1
```

## Landing Page Deployment

The marketing landing page lives in `frontend-landing` and can be deployed separately.

```bash
cd frontend-landing
npm install
npm run build
```

Use `frontend-landing/vercel.json` if deploying the landing site to Vercel.

## Developer Architecture Outline

### Architecture

NessaPOS is split into four main runtime parts:

- `frontend-landing`: public landing page and SEO blog.
- `frontend-pos`: POS web app for cashier, admin, and super admin users. In production this can be deployed to Vercel as `app.domain.com`.
- `laravel-backend`: centralized API server. In production this should run as `api.domain.com`.
- `wails-app`: desktop app used when the cashier device needs local desktop integration.
- `go-print-service`: embedded/local print service used by the desktop app to communicate with thermal printers.

Recommended production domains:

- `www.domain.com`: landing page and blog.
- `app.domain.com`: browser POS app.
- `api.domain.com`: API server.
- Local desktop app: cashier workstation for thermal printing and local device access.

### Auth

Authentication uses Laravel Sanctum token-based auth.

Bearer token flow:

1. POS app sends credentials to `/api/login`.
2. API validates the user and returns an access token.
3. POS app stores the token in `localStorage`.
4. Every protected API request sends `Authorization: Bearer <token>`.
5. Logout clears local session data and should revoke or discard the active token.

### System Flow

Login flow:

1. User opens the POS app from browser or desktop.
2. User enters store/user credentials.
3. App calls `/api/login`.
4. App stores the returned bearer token.
5. App loads products, settings, sessions, and transaction data from the API.

Print flow:

1. Cashier completes a transaction.
2. POS app prepares receipt data.
3. POS app sends the receipt payload to Wails desktop.
4. Wails forwards the print job to the embedded Go print service.
5. Go print service sends formatted output to the selected thermal printer.

### Main Endpoints

- `POST /api/login`: authenticate user and return bearer token.
- `GET /api/products`: list products for the authenticated store.
- `POST /api/products`: create a product.
- `PUT /api/products/{id}`: update a product.
- `GET /api/transactions`: list transaction history.
- `POST /api/transactions`: create a transaction.

If route names differ in implementation, keep this section as the product-level contract and update endpoint aliases in the API layer or documentation.

## API Docs

The Laravel backend includes generated documentation assets:

- `laravel-backend/openapi.json`
- `laravel-backend/public/openapi.json`
- `laravel-backend/NessaPOS_API_Collection.json`
- API explorer at `/docs`

## Database Notes

The desktop database output under `database/` is generated at runtime and is separate from the Laravel API database.

Default local desktop accounts:

- Admin: `admin` / `admin123`
- Kasir: `kasir` / `kasir123`
