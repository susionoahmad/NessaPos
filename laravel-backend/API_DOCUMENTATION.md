# Laravel API Documentation

Base URL: `https://api.domain.com/api`

## Authentication

Authentication uses Laravel Sanctum token-based auth, not session cookies.
The SPA front-end stores the access token in `localStorage` and sends it on each API request with:

`Authorization: Bearer <access_token>`

- `POST /login`
  - Login user
  - Request body: `username`, `password`, `store_id`

- `POST /register`
  - Register new account
  - Request body: `store_name`, `store_slug`, `username`, `password`

## Public Endpoints

- `GET /packages`
  - List subscription packages

## Authenticated Endpoints

All authenticated routes require `auth:sanctum` and `subscription` middleware.

### Session & Cashier

- `POST /logout`
  - Logout current user

- `PUT /settings/subscription`
  - Update tenant subscription settings

- `POST /settings/setup`
  - Complete application setup

- `POST /subscription/renew`
  - Create subscription renewal request

- `GET /tenant/info`
  - Get current tenant info, subscription status, and latest renewal request

### Products

- `GET /products`
  - List all products

- `POST /products`
  - Create a new product

- `PUT /products/{product}`
  - Update product data

- `DELETE /products/{product}`
  - Remove a product

- `GET /stock-mutations`
  - List stock mutation history

### Orders

- `GET /orders`
  - List orders

- `GET /orders/stats`
  - Get daily order statistics

- `POST /orders`
  - Create an order

### Sessions

- `GET /sessions`
  - List payment sessions

- `GET /sessions/current`
  - Get current cashier session

- `POST /sessions/open`
  - Open a new cashier session

- `POST /sessions/close`
  - Close the current cashier session

- `GET /cashier-transactions`
  - Get cashier transaction history

### Vault

- `GET /vault`
  - Get vault information

- `GET /vault/transactions`
  - List vault transactions

- `POST /vault/deposit`
  - Create vault deposit transaction

### Settings

- `GET /settings`
  - Get current settings

- `PUT /settings`
  - Update settings

### Users

- `GET /users`
  - List users (admin only)

- `POST /users`
  - Create user

- `PUT /users/{id}`
  - Update user

- `DELETE /users/{id}`
  - Delete user

## Superadmin Endpoints

These endpoints require the `superadmin` middleware.

## API explorer

A static API documentation page is available at:

`https://api.domain.com/docs`

The OpenAPI specification is published at:

`https://api.domain.com/openapi.json`

All frontends should use bearer token auth; no CSRF cookie flow is required for SPA or Wails.

- `GET /superadmin/stats`
  - Get admin-level statistics

- `GET /superadmin/tenants`
  - List tenants

- `POST /superadmin/tenants`
  - Create tenant

- `PUT /superadmin/tenants/{id}/subscription`
  - Update tenant subscription

- `POST /superadmin/tenants/{id}/toggle`
  - Enable or disable tenant

- `DELETE /superadmin/tenants/{id}`
  - Delete tenant

- `GET /superadmin/packages`
  - List subscription packages

- `PUT /superadmin/packages/{id}`
  - Update subscription package

- `GET /superadmin/renewals`
  - List renewal requests

- `POST /superadmin/renewals/{id}/approve`
  - Approve a renewal request

- `POST /superadmin/renewals/{id}/reject`
  - Reject a renewal request

## Notes

- All authenticated endpoints use Sanctum bearer auth or cookie-based auth depending on frontend integration.
- API routes assume the `/api` prefix from `routes/api.php`.
- For frontend SPA integration, set `VITE_API_URL=https://api.domain.com/api` in production.
