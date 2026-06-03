# Vue 3 + TypeScript + Vite

This template should help get you started developing with Vue 3 and TypeScript in Vite. The template uses Vue
3 `<script setup>` SFCs, check out
the [script setup docs](https://v3.vuejs.org/api/sfc-script-setup.html#sfc-script-setup) to learn more.

## Recommended IDE Setup

- [VS Code](https://code.visualstudio.com/) + [Volar](https://marketplace.visualstudio.com/items?itemName=Vue.volar)

## Type Support For `.vue` Imports in TS

Since TypeScript cannot handle type information for `.vue` imports, they are shimmed to be a generic Vue component type
by default. In most cases this is fine if you don't really care about component prop types outside of templates.
However, if you wish to get actual prop types in `.vue` imports (for example to get props validation when using
manual `h(...)` calls), you can enable Volar's Take Over mode by following these steps:

1. Run `Extensions: Show Built-in Extensions` from VS Code's command palette, look
   for `TypeScript and JavaScript Language Features`, then right click and select `Disable (Workspace)`. By default,
   Take Over mode will enable itself if the default TypeScript extension is disabled.
2. Reload the VS Code window by running `Developer: Reload Window` from the command palette.

You can learn more about Take Over mode [here](https://github.com/johnsoncodehk/volar/discussions/471).

## PWA & APK Android (PWABuilder)

Production build includes `manifest.webmanifest` and service worker (`dist/sw.js`).

1. Deploy ke Vercel (HTTPS) dengan `VITE_API_URL` production.
2. Buka [PWABuilder](https://www.pwabuilder.com/) → masukkan URL deploy.
3. Package for Android → ikuti wizard (TWA/APK).
4. Di HP: Settings → metode cetak **RawBT** untuk printer thermal Bluetooth.

Ikon PWA ada di `public/pwa-*.png` (ganti jika perlu resolusi 192×192 terpisah).

## Build for Wails Desktop

To compile the POS SPA into the Wails desktop app assets, run:
```bash
cd frontend-pos
npm install
npm run build:wails
```

## Auth workflow

The POS SPA is configured to use Laravel Sanctum token-based authentication.
Tokens should be stored in `localStorage` and sent with every request:

`Authorization: Bearer <access_token>`

This makes the SPA compatible with both Vercel deployment and the Wails desktop app.
