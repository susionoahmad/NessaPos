# NessaPOS Landing Page

Ini adalah landing page statis untuk NessaPOS.

## Run local

npm install
npm run dev

If Next.js shows a missing chunk error such as `Cannot find module './819.js'`, stop the dev server and run:

```bash
npm run dev:clean
```

## Environment

Copy `.env.example` to `.env.local` if you want to override URLs.

```env
NEXT_PUBLIC_API_URL=http://localhost:8000/api
NEXT_PUBLIC_POS_WEB_URL=http://localhost:5173
```

For production, change it to your API and POS subdomain, for example:

```env
NEXT_PUBLIC_API_URL=https://api.domain.com/api
NEXT_PUBLIC_POS_WEB_URL=https://app.domain.com
```

## Build

npm run build
npm run start

## Deploy ke Vercel

1. Pastikan `frontend-landing` sebagai project root di Vercel.
2. Gunakan build command:
   ```bash
   npm install
   npm run build
   ```
3. Output Vercel otomatis akan men-deploy Next.js.

## Konfigurasi Vercel

File `vercel.json` telah disediakan untuk pengaturan project Next.js.

## Catatan

- Jika Anda menggunakan domain custom, arahkan `app.domain.com` ke Vercel.
- Pastikan resource landing site hanya memanggil API melalui domain `api.domain.com`.
