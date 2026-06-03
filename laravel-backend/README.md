# NessaPOS Laravel API

This module provides the centralized API server for NessaPOS. It should serve business data for the browser POS app, desktop app, and admin flows.

## Role In The System

- Public landing and blog live in `frontend-landing`.
- Cashier/admin POS app lives in `frontend-pos`.
- API server lives in `laravel-backend` and is intended for `api.domain.com`.
- Desktop printing and local device access are handled by `wails-app` and the embedded Go print service.

## Architecture

Production shape:

- `www.domain.com`: landing page and blog.
- `app.domain.com`: POS web app, usually deployed to Vercel.
- `api.domain.com`: Laravel API.
- Local desktop app: Wails app installed on the cashier computer.
- Local print service: Go service embedded with the desktop app for thermal printer output.

## Authentication

NessaPOS uses Laravel Sanctum token-based authentication.

Bearer token flow:

1. POS app sends credentials to `/api/login`.
2. API validates the user.
3. API returns an access token and user context.
4. POS app stores the token in `localStorage`.
5. POS app sends `Authorization: Bearer <token>` on protected requests.
6. Logout clears local session data and should revoke or discard the token.

## Main Flow

Login:

1. User opens the POS app from browser or desktop.
2. User enters store/user credentials.
3. App calls `/api/login`.
4. App stores the returned token.
5. App loads products, settings, sessions, and transaction data.

Transaction:

1. Cashier opens a selling session.
2. Cashier selects products and completes payment.
3. App creates a transaction through the API.
4. App refreshes products, reports, and session summary as needed.

Print:

1. POS app prepares receipt data after checkout.
2. POS app sends receipt payload to Wails desktop.
3. Wails forwards the job to the Go print service.
4. Go print service formats and sends the output to the thermal printer.

## Main Endpoints

- `POST /api/login`: authenticate user and return bearer token.
- `GET /api/products`: list products.
- `POST /api/products`: create product.
- `PUT /api/products/{id}`: update product.
- `GET /api/transactions`: list transactions.
- `POST /api/transactions`: create transaction.

Related generated docs:

- `openapi.json`
- `public/openapi.json`
- `NessaPOS_API_Collection.json`
- API explorer at `/docs`

## Development

Install dependencies:

```bash
composer install
npm install
```

Run migrations:

```bash
php artisan migrate
```

Run local server:

```bash
php artisan serve
```
