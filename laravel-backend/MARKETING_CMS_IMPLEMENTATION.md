# Marketing CMS, Blog, Affiliate, Device, Promotion

## Struktur Folder

```text
app/Models/
  Page.php
  Section.php
  SectionContent.php
  Post.php
  BlogCategory.php
  AffiliateLink.php
  AffiliateClick.php
  Device.php
  RecommendedDevice.php
  Plan.php
  Promotion.php

app/Http/Controllers/
  PublicContentController.php
  SuperAdminContentController.php

database/migrations/
  2026_04_28_221000_create_marketing_cms_tables.php

database/seeders/
  MarketingCmsSeeder.php
```

## Public Endpoint

```text
GET  /api/pages/{slug}
GET  /api/posts
GET  /api/posts/{slug}
GET  /api/devices/recommendation?context=pos
POST /api/affiliate-links/{affiliate}/click
```

## Superadmin Endpoint

Semua endpoint berikut memakai `auth:sanctum`, `subscription`, dan `superadmin`.

```text
GET|POST        /api/superadmin/cms/pages
PUT|DELETE      /api/superadmin/cms/pages/{page}

GET|POST        /api/superadmin/cms/blog-categories
GET|POST        /api/superadmin/cms/posts
PUT|DELETE      /api/superadmin/cms/posts/{post}

GET|POST        /api/superadmin/cms/affiliate-links
PUT|DELETE      /api/superadmin/cms/affiliate-links/{affiliateLink}

GET|POST        /api/superadmin/cms/devices
PUT|DELETE      /api/superadmin/cms/devices/{device}
GET|POST        /api/superadmin/cms/device-recommendations
PUT|DELETE      /api/superadmin/cms/device-recommendations/{recommendedDevice}

GET|POST        /api/superadmin/cms/plans
PUT|DELETE      /api/superadmin/cms/plans/{plan}
GET|POST        /api/superadmin/cms/promotions
PUT|DELETE      /api/superadmin/cms/promotions/{promotion}
```

## Contoh Response Landing

```json
{
  "id": 1,
  "title": "NessaPOS",
  "slug": "home",
  "meta_title": "NessaPOS - Aplikasi Kasir Online untuk UMKM",
  "meta_description": "POS SaaS untuk toko, kasir, stok, laporan, langganan, dan thermal printer.",
  "is_active": true,
  "sections": [
    {
      "name": "hero",
      "order": 1,
      "contents": [
        { "type": "text", "key": "title", "value": "POS online untuk toko yang ingin tumbuh rapi" },
        { "type": "text", "key": "subtitle", "value": "Kelola kasir, stok, laporan, langganan, dan printer dari satu sistem." },
        { "type": "button", "key": "cta_text", "value": "Coba Gratis" }
      ]
    }
  ]
}
```

## Contoh Request Superadmin

```json
{
  "title": "Home",
  "slug": "home",
  "meta_title": "NessaPOS - Aplikasi Kasir Online",
  "meta_description": "Aplikasi kasir online untuk UMKM.",
  "is_active": true,
  "sections": [
    {
      "name": "hero",
      "order": 1,
      "is_active": true,
      "contents": [
        { "type": "text", "key": "title", "value": "POS online untuk toko modern" },
        { "type": "text", "key": "subtitle", "value": "Kelola kasir, stok, laporan..." },
        { "type": "json", "key": "buttons", "value": [
          { "text": "Coba Gratis", "link": "/coba-gratis", "type": "primary" },
          { "text": "Lihat Fitur", "link": "#fitur", "type": "secondary" }
        ]}
      ]
    }
  ]
}
```

## Contoh Frontend Vue

```ts
import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
})

export async function loadLandingHome() {
  const { data } = await api.get('/pages/home')
  return data
}

export async function loadBlogPosts() {
  const { data } = await api.get('/posts')
  return data.data
}

export async function loadPosDevices() {
  const { data } = await api.get('/devices/recommendation', {
    params: { context: 'pos' }
  })
  return data.devices
}
```

## Contoh Frontend Next.js

```ts
export async function getHomePage() {
  const res = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/pages/home`, {
    next: { revalidate: 300 }
  })
  return res.json()
}

export async function getPosts() {
  const res = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/posts`, {
    next: { revalidate: 300 }
  })
  return res.json()
}
```

## Catatan

Tabel kategori blog memakai `blog_categories`, bukan `categories`, karena tabel `categories` sudah dipakai POS untuk kategori produk multi-tenant.

## Mengelola Features di Home Page

### Struktur Features Section

Features di halaman home dapat dikelola melalui CMS dengan struktur berikut:

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
          "body": "Proses transaksi, cari produk, atur keranjang, dan selesaikan pembayaran tanpa alur yang membingungkan."
        },
        {
          "title": "Stok Lebih Rapi",
          "body": "Produk, harga, kategori, barcode, dan perubahan stok bisa dipantau dari satu tempat."
        }
      ]
    }
  ]
}
```

### Update Features via API Superadmin

**Request:**
```bash
PUT /api/superadmin/cms/pages/{page_id}
```

**Body:**
```json
{
  "title": "NessaPOS",
  "slug": "home",
  "meta_title": "NessaPOS - Aplikasi Kasir Online untuk UMKM",
  "meta_description": "POS SaaS untuk toko, kasir, stok, laporan, langganan, dan thermal printer.",
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
              "body": "Proses transaksi, cari produk, atur keranjang, dan selesaikan pembayaran tanpa alur yang membingungkan."
            },
            {
              "title": "Stok Lebih Rapi",
              "body": "Produk, harga, kategori, barcode, dan perubahan stok bisa dipantau dari satu tempat."
            }
          ]
        }
      ]
    }
  ]
}
```

### Content Types

- **text**: Teks biasa (title, subtitle, button text)
- **json**: Data terstruktur (array of objects untuk features, plans, dll)
- **button**: Teks untuk CTA button

Setiap perubahan di fitur akan langsung ter-reflect di halaman home berkat ISR (Incremental Static Regeneration) dengan revalidate 300 detik.

## Mengelola Buttons di Hero Section

### Struktur Hero Buttons

Tombol di hero section dapat dikelola sepenuhnya melalui CMS dengan unlimited buttons:

```json
{
  "name": "hero",
  "order": 1,
  "is_active": true,
  "contents": [
    {
      "type": "text",
      "key": "title",
      "value": "POS online untuk toko yang ingin tumbuh rapi"
    },
    {
      "type": "text",
      "key": "subtitle",
      "value": "Kelola kasir, stok, laporan, langganan, dan printer dari satu sistem."
    },
    {
      "type": "json",
      "key": "buttons",
      "value": [
        {
          "text": "Coba Gratis",
          "link": "/coba-gratis",
          "type": "primary"
        },
        {
          "text": "Lihat Fitur",
          "link": "#fitur",
          "type": "secondary"
        },
        {
          "text": "Hubungi Sales",
          "link": "https://wa.me/62123456789",
          "type": "secondary"
        }
      ]
    }
  ]
}
```

### Update Buttons via API Superadmin

**Request:**
```bash
PUT /api/superadmin/cms/pages/1
```

**Body:**
```json
{
  "title": "NessaPOS",
  "slug": "home",
  "meta_title": "NessaPOS - Aplikasi Kasir Online untuk UMKM",
  "meta_description": "POS SaaS untuk toko, kasir, stok, laporan, langganan, dan thermal printer.",
  "is_active": true,
  "sections": [
    {
      "name": "hero",
      "order": 1,
      "is_active": true,
      "contents": [
        {
          "type": "text",
          "key": "title",
          "value": "POS online untuk toko yang ingin tumbuh rapi"
        },
        {
          "type": "text",
          "key": "subtitle",
          "value": "Kelola kasir, stok, laporan, langganan, dan printer dari satu sistem."
        },
        {
          "type": "json",
          "key": "buttons",
          "value": [
            {
              "text": "Coba Gratis",
              "link": "/coba-gratis",
              "type": "primary"
            },
            {
              "text": "Lihat Fitur",
              "link": "#fitur",
              "type": "secondary"
            }
          ]
        }
      ]
    }
  ]
}
```

### Tipe Button

- **primary**: Tombol utama (gaya prominent)
- **secondary**: Tombol sekunder (gaya subdued)

### Jenis Link yang Didukung

- **Internal link**: `/coba-gratis`, `/blog`, etc
- **Anchor**: `#fitur`, `#harga`, etc
- **External link**: `https://wa.me/...`, `https://example.com`, dll (akan membuka di tab baru)

---

## Panduan Lengkap: Update Hero Buttons dengan Postman

### Step 1: Setup Postman Request

1. **Buka Postman** (atau import collection dari `NessaPOS_API_Collection.json`)
2. **Buat request baru atau gunakan yang sudah ada**:
   - Method: **PUT**
   - URL: `http://localhost:8000/api/superadmin/cms/pages/1`

### Step 2: Setup Headers

Di tab **Headers**, tambahkan:

| Key | Value |
|-----|-------|
| `Content-Type` | `application/json` |
| `Authorization` | `Bearer {YOUR_SANCTUM_TOKEN}` |
| `Accept` | `application/json` |

**Cara dapat token:**
```bash
# Login terlebih dahulu
POST /api/login
Body: {"email": "admin@example.com", "password": "password"}
```

Atau gunakan token dari database:
```php
// Di Laravel Tinker
$user = App\Models\User::where('email', 'admin@example.com')->first();
echo $user->createToken('api-token')->plainTextToken;
```

### Step 3: Setup Body

Di tab **Body**, pilih **raw** → **JSON**, lalu paste salah satu contoh di bawah:

---

#### **Contoh 1: Update dengan 2 Button (Default)**

```json
{
  "title": "NessaPOS",
  "slug": "home",
  "meta_title": "NessaPOS - Aplikasi Kasir Online untuk UMKM",
  "meta_description": "POS SaaS untuk toko, kasir, stok, laporan, langganan, dan thermal printer.",
  "is_active": true,
  "sections": [
    {
      "name": "hero",
      "order": 1,
      "is_active": true,
      "contents": [
        {
          "type": "text",
          "key": "title",
          "value": "POS online untuk toko yang ingin tumbuh rapi"
        },
        {
          "type": "text",
          "key": "subtitle",
          "value": "Kelola kasir, stok, laporan, langganan, dan printer dari satu sistem."
        },
        {
          "type": "json",
          "key": "buttons",
          "value": "[{\"text\":\"Coba Gratis\",\"link\":\"\\/coba-gratis\",\"type\":\"primary\"},{\"text\":\"Lihat Fitur\",\"link\":\"#fitur\",\"type\":\"secondary\"}]"
        }
      ]
    }
  ]
}
```

---

#### **Contoh 2: Update dengan 3 Button (Tambah WhatsApp)**

```json
{
  "title": "NessaPOS",
  "slug": "home",
  "meta_title": "NessaPOS - Aplikasi Kasir Online untuk UMKM",
  "meta_description": "POS SaaS untuk toko, kasir, stok, laporan, langganan, dan thermal printer.",
  "is_active": true,
  "sections": [
    {
      "name": "hero",
      "order": 1,
      "is_active": true,
      "contents": [
        {
          "type": "text",
          "key": "title",
          "value": "POS online untuk toko yang ingin tumbuh rapi"
        },
        {
          "type": "text",
          "key": "subtitle",
          "value": "Kelola kasir, stok, laporan, langganan, dan printer dari satu sistem."
        },
        {
          "type": "json",
          "key": "buttons",
          "value": "[{\"text\":\"Coba Gratis\",\"link\":\"\\/coba-gratis\",\"type\":\"primary\"},{\"text\":\"Lihat Fitur\",\"link\":\"#fitur\",\"type\":\"secondary\"},{\"text\":\"Hubungi Sales\",\"link\":\"https:\\/\\/wa.me\\/62123456789\",\"type\":\"secondary\"}]"
        }
      ]
    }
  ]
}
```

---

#### **Contoh 3: Update dengan 4 Button (Mix Internal & External)**

```json
{
  "title": "NessaPOS",
  "slug": "home",
  "meta_title": "NessaPOS - Aplikasi Kasir Online untuk UMKM",
  "meta_description": "POS SaaS untuk toko, kasir, stok, laporan, langganan, dan thermal printer.",
  "is_active": true,
  "sections": [
    {
      "name": "hero",
      "order": 1,
      "is_active": true,
      "contents": [
        {
          "type": "text",
          "key": "title",
          "value": "POS online untuk toko yang ingin tumbuh rapi"
        },
        {
          "type": "text",
          "key": "subtitle",
          "value": "Kelola kasir, stok, laporan, langganan, dan printer dari satu sistem."
        },
        {
          "type": "json",
          "key": "buttons",
          "value": "[{\"text\":\"Coba Gratis\",\"link\":\"\\/coba-gratis\",\"type\":\"primary\"},{\"text\":\"Lihat Fitur\",\"link\":\"#fitur\",\"type\":\"secondary\"},{\"text\":\"Hubungi Sales\",\"link\":\"https:\\/\\/wa.me\\/62123456789\",\"type\":\"secondary\"},{\"text\":\"Instagram\",\"link\":\"https:\\/\\/instagram.com\\/nessapos\",\"type\":\"secondary\"}]"
        }
      ]
    }
  ]
}
```

---

### Step 4: Send Request

1. Klik tombol **Send** (atau tekan Ctrl+Enter)
2. Tunggu response dari server

### Step 5: Cek Response

**Response sukses (Status 200):**
```json
{
  "id": 1,
  "title": "NessaPOS",
  "slug": "home",
  "meta_title": "NessaPOS - Aplikasi Kasir Online untuk UMKM",
  "meta_description": "POS SaaS untuk toko, kasir, stok, laporan, langganan, dan thermal printer.",
  "is_active": true,
  "sections": [
    {
      "id": 1,
      "name": "hero",
      "order": 1,
      "is_active": true,
      "contents": [
        {
          "id": 1,
          "type": "text",
          "key": "title",
          "value": "POS online untuk toko yang ingin tumbuh rapi"
        },
        {
          "id": 2,
          "type": "text",
          "key": "subtitle",
          "value": "Kelola kasir, stok, laporan, langganan, dan printer dari satu sistem."
        },
        {
          "id": 9,
          "type": "json",
          "key": "buttons",
          "value": "[{\"text\":\"Coba Gratis\",\"link\":\"\\/coba-gratis\",\"type\":\"primary\"},{\"text\":\"Lihat Fitur\",\"link\":\"#fitur\",\"type\":\"secondary\"},{\"text\":\"Hubungi Sales\",\"link\":\"https:\\/\\/wa.me\\/62123456789\",\"type\":\"secondary\"}]"
        }
      ]
    }
  ]
}
```

**Response error (Status 422):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "sections": ["Invalid section data"]
  }
}
```

---

### Step 6: Verifikasi di Frontend

1. Buka browser ke `http://localhost:3000` (Next.js frontend)
2. Refresh halaman (hard refresh: Ctrl+Shift+R)
3. Lihat hero section - button baru seharusnya sudah muncul

---

## Quick Copy-Paste Buttons Value

### 2 Buttons Default
```
[{"text":"Coba Gratis","link":"/coba-gratis","type":"primary"},{"text":"Lihat Fitur","link":"#fitur","type":"secondary"}]
```

### 3 Buttons (+ WhatsApp)
```
[{"text":"Coba Gratis","link":"/coba-gratis","type":"primary"},{"text":"Lihat Fitur","link":"#fitur","type":"secondary"},{"text":"Hubungi Sales","link":"https://wa.me/62123456789","type":"secondary"}]
```

### 4 Buttons (+ Instagram)
```
[{"text":"Coba Gratis","link":"/coba-gratis","type":"primary"},{"text":"Lihat Fitur","link":"#fitur","type":"secondary"},{"text":"Hubungi Sales","link":"https://wa.me/62123456789","type":"secondary"},{"text":"Instagram","link":"https://instagram.com/nessapos","type":"secondary"}]
```

---

## Troubleshooting

| Masalah | Solusi |
|---------|--------|
| `401 Unauthorized` | Token expired atau salah. Generate token baru atau login kembali |
| `403 Forbidden` | User bukan superadmin. Cek role di database: `roles` tabel |
| `422 Unprocessable Entity` | Format JSON salah. Cek escaping di `value` field (gunakan `\/` untuk `/`) |
| `Button tidak muncul di frontend` | Lakukan hard refresh (Ctrl+Shift+R) atau clear cache Next.js |
| `Link tidak berfungsi` | Pastikan format link benar (cek backslash escaping) |

---
