# Guide: Dynamic Home Features & Landing Page CMS

## Ringkasan Perubahan

Halaman home landing page NessaPOS kini dapat dikelola secara dinamis melalui Super Admin CMS Panel tanpa perlu mengubah kode.

### ✅ Yang Sudah Diimplementasikan

1. **Backend (Laravel)**
   - ✅ Seeder `MarketingCmsSeeder` sudah diupdate dengan data fitur (features) dalam format JSON
   - ✅ SuperAdminContentController sudah siap untuk menerima update fitur via API
   - ✅ Database schema mendukung penyimpanan fitur dalam `section_contents` table dengan type "json"

2. **Frontend (Next.js)**
   - ✅ `frontend-landing/app/page.tsx` sudah diupdate untuk mengambil fitur dari CMS
   - ✅ Implementasi fallback ke data statis jika API tidak tersedia
   - ✅ ISR (Incremental Static Regeneration) dengan revalidate 300 detik

## Struktur Data

### Features Section Structure
```json
{
  "name": "features",
  "order": 2,
  "is_active": true,
  "contents": [
    {
      "type": "text",
      "key": "title",
      "value": "Dibuat untuk toko yang ingin kerja lebih ringan"
    },
    {
      "type": "text",
      "key": "subtitle",
      "value": "NessaPOS membantu kasir, admin, dan pemilik toko bekerja dengan alur yang sederhana."
    },
    {
      "type": "json",
      "key": "items",
      "value": [
        {
          "title": "Kasir Lebih Cepat",
          "body": "Proses transaksi, cari produk, atur keranjang, dan selesaikan pembayaran..."
        },
        {
          "title": "Stok Lebih Rapi",
          "body": "Produk, harga, kategori, barcode, dan perubahan stok bisa dipantau..."
        },
        ...
      ]
    }
  ]
}
```

## Cara Menggunakan

### 1. Setup Backend
Jalankan seeder untuk menginisialisasi data:
```bash
php artisan db:seed MarketingCmsSeeder
```

### 2. Update Features via Super Admin API
```bash
PUT /api/superadmin/cms/pages/1
```

Body:
```json
{
  "title": "NessaPOS",
  "slug": "home",
  "meta_title": "...",
  "meta_description": "...",
  "is_active": true,
  "sections": [
    {
      "name": "features",
      "order": 2,
      "is_active": true,
      "contents": [
        {
          "type": "text",
          "key": "title",
          "value": "Judul fitur baru"
        },
        {
          "type": "text",
          "key": "subtitle",
          "value": "Subtitle fitur baru"
        },
        {
          "type": "json",
          "key": "items",
          "value": [
            {
              "title": "Fitur 1",
              "body": "Deskripsi fitur 1"
            },
            {
              "title": "Fitur 2",
              "body": "Deskripsi fitur 2"
            }
          ]
        }
      ]
    }
  ]
}
```

### 3. Frontend akan secara otomatis refresh
Halaman home akan memperbarui konten dalam waktu 5 menit (300 detik) berkat ISR.

## File-file yang Diubah

### Backend
- `laravel-backend/database/seeders/MarketingCmsSeeder.php` - Menambahkan data features
- `laravel-backend/MARKETING_CMS_IMPLEMENTATION.md` - Update dokumentasi

### Frontend
- `frontend-landing/app/page.tsx` - Update untuk mengambil features dari API

## Endpoints API

### Public (Tanpa Auth)
```
GET /api/pages/{slug}  # Ambil data halaman termasuk semua sections
```

### Super Admin (Dengan Auth)
```
GET|POST    /api/superadmin/cms/pages           # List/Create pages
PUT|DELETE  /api/superadmin/cms/pages/{page}    # Update/Delete page
```

## Notes

- ✅ Data features tersimpan di database dalam format JSON untuk fleksibilitas maksimal
- ✅ Perubahan langsung ter-reflect di Super Admin Panel
- ✅ Frontend home otomatis update berkat ISR
- ✅ Fallback ke data statis jika API down
- ✅ Support unlimited number of features

## Jika Ingin Menambah Section Baru

Contoh menambah section "benefits" atau section lainnya:

1. Update `MarketingCmsSeeder` dengan section baru
2. Update `frontend-landing/app/page.tsx` dengan logic untuk menampilkan section
3. Jalankan seeder kembali atau update via API
4. Frontend akan otomatis menampilkan section baru setelah cache expires

---

**Last Updated:** April 28, 2026
