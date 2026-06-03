# Wails Desktop App

This module contains the Wails desktop application for NessaPOS.

## Development

Run from `wails-app` to launch the app with hot reload:

```bash
cd wails-app
wails dev
```

## Build

The desktop build depends on the POS SPA assets from `../frontend-pos`.

From within `wails-app`, run:

```bash
cd wails-app
wails build
```

Alternatively, from the repo root you can run:

```powershell
./build-desktop.ps1
```

## Notes

- `wails.json` is configured to build the frontend assets from `../frontend-pos`.
- If you build the SPA separately, ensure the output is placed into `wails-app/frontend/dist`.
- **API URL:** set `frontend-pos/.env.production` (`VITE_API_URL=https://api.nessapos.com/api`) before `wails build`. URL ini ikut ke dalam `.exe`.
- **CORS:** origin Wails (`wails://wails`) sudah diizinkan di `laravel-backend/config/cors.php`. Pastikan server production sudah `config:cache` setelah deploy.
