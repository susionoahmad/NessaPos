<?php

namespace Database\Seeders;

use App\Models\AffiliateLink;
use App\Models\BlogCategory;
use App\Models\Device;
use App\Models\Page;
use App\Models\Plan;
use App\Models\Post;
use App\Models\Promotion;
use App\Models\RecommendedDevice;
use Illuminate\Database\Seeder;

class MarketingCmsSeeder extends Seeder
{
    public function run(): void
    {
        $page = Page::updateOrCreate(
            ['slug' => 'home'],
            [
                'title' => 'NessaPOS',
                'meta_title' => 'NessaPOS - Aplikasi Kasir Online untuk UMKM',
                'meta_description' => 'POS SaaS untuk toko, kasir, stok, laporan, langganan, dan thermal printer.',
                'is_active' => true,
            ]
        );

        $hero = $page->sections()->updateOrCreate(
            ['name' => 'hero'],
            ['order' => 1, 'is_active' => true]
        );

        $hero->contents()->updateOrCreate(
            ['key' => 'title'],
            ['type' => 'text', 'value' => 'POS online untuk toko yang ingin tumbuh rapi']
        );
        $hero->contents()->updateOrCreate(
            ['key' => 'subtitle'],
            ['type' => 'text', 'value' => 'Kelola kasir, stok, laporan, langganan, dan printer dari satu sistem.']
        );
        $hero->contents()->updateOrCreate(
            ['key' => 'buttons'],
            ['type' => 'json', 'value' => [
                [
                    'text' => 'Coba Gratis',
                    'link' => '/coba-gratis',
                    'type' => 'primary'
                ],
                [
                    'text' => 'Lihat Fitur',
                    'link' => '#fitur',
                    'type' => 'secondary'
                ]
            ]]
        );

        $hero->contents()->updateOrCreate(
            ['key' => 'cta_text'],
            ['type' => 'text', 'value' => 'Coba Gratis']
        );

        $pricing = $page->sections()->updateOrCreate(
            ['name' => 'pricing'],
            ['order' => 3, 'is_active' => true]
        );
        $pricing->contents()->updateOrCreate(
            ['key' => 'title'],
            ['type' => 'text', 'value' => 'Paket fleksibel untuk toko kecil sampai multi-cabang']
        );

        $features = $page->sections()->updateOrCreate(
            ['name' => 'features'],
            ['order' => 2, 'is_active' => true]
        );
        $features->contents()->updateOrCreate(
            ['key' => 'title'],
            ['type' => 'text', 'value' => 'Dibuat untuk toko yang ingin kerja lebih ringan']
        );
        $features->contents()->updateOrCreate(
            ['key' => 'subtitle'],
            ['type' => 'text', 'value' => 'NessaPOS membantu kasir, admin, dan pemilik toko bekerja dengan alur yang sederhana.']
        );
        $features->contents()->updateOrCreate(
            ['key' => 'items'],
            ['type' => 'json', 'value' => [
                [
                    'title' => 'Kasir Lebih Cepat',
                    'body' => 'Proses transaksi, cari produk, atur keranjang, dan selesaikan pembayaran tanpa alur yang membingungkan.'
                ],
                [
                    'title' => 'Stok Lebih Rapi',
                    'body' => 'Produk, harga, kategori, barcode, dan perubahan stok bisa dipantau dari satu tempat.'
                ],
                [
                    'title' => 'Laporan Jelas',
                    'body' => 'Pantau penjualan harian, produk laris, pembayaran, dan performa kasir dengan ringkasan yang mudah dibaca.'
                ],
                [
                    'title' => 'Print Thermal Tanpa Ribet',
                    'body' => 'Cetak struk ke printer thermal langsung dari alur kasir, tanpa bolak-balik membuka dialog browser.'
                ],
                [
                    'title' => 'Cocok untuk Banyak Toko',
                    'body' => 'Kelola beberapa toko atau cabang dengan akses admin dan kasir yang lebih tertata.'
                ],
                [
                    'title' => 'Browser dan Desktop',
                    'body' => 'Bisa dipakai lewat browser untuk akses praktis, atau desktop untuk kebutuhan kasir dan print yang lebih stabil.'
                ]
            ]]
        );

        $plans = $page->sections()->updateOrCreate(
            ['name' => 'plans'],
            ['order' => 4, 'is_active' => true]
        );
        $plans->contents()->updateOrCreate(
            ['key' => 'plans'],
            ['type' => 'json', 'value' => [
                [
                    'name' => 'Starter',
                    'price' => 'Gratis',
                    'details' => 'Coba fitur kasir, produk, stok, dan laporan sebelum mulai berlangganan.'
                ],
                [
                    'name' => 'Business',
                    'price' => 'Bulanan',
                    'details' => 'Untuk toko aktif yang butuh kasir cepat, laporan rutin, dan cetak struk thermal.'
                ],
                [
                    'name' => 'Scale',
                    'price' => 'Tahunan',
                    'details' => 'Untuk bisnis yang mulai mengelola banyak toko, cabang, atau tim kasir.'
                ]
            ]]
        );

        $category = BlogCategory::updateOrCreate(
            ['slug' => 'perangkat-kasir'],
            ['name' => 'Perangkat Kasir']
        );

        $affiliate = AffiliateLink::where('title', 'Printer Thermal 58mm Rekomendasi')->first();
        if (!$affiliate || !str_contains($affiliate->image, 'shopee.co.id') && !str_contains($affiliate->image, 'cf.shopee.co.id')) {
            $affiliate = AffiliateLink::updateOrCreate(
                ['title' => 'Printer Thermal 58mm Rekomendasi'],
                [
                    'url' => 'https://shopee.co.id/universal-thermal-printer',
                    'platform' => 'shopee',
                    'product_name' => 'Printer Thermal 58mm',
                    'image' => 'https://cf.shopee.co.id/file/6bc502f68b35581c7e997a61d6706e28', // Placeholder real shopee image format
                    'price' => 250000,
                    'is_active' => true,
                ]
            );
        }

        $post = Post::where('slug', 'cara-memilih-printer-thermal-untuk-pos')->first();
        if (!$post || !str_contains($post->thumbnail, 'http')) {
            $post = Post::updateOrCreate(
                ['slug' => 'cara-memilih-printer-thermal-untuk-pos'],
                [
                    'title' => 'Cara Memilih Printer Thermal untuk POS',
                    'content' => '<p>Pilih printer thermal yang kompatibel dengan ukuran struk, koneksi USB/Bluetooth, dan kebutuhan volume transaksi toko.</p>',
                    'excerpt' => 'Panduan singkat memilih printer thermal untuk aplikasi kasir.',
                    'thumbnail' => 'https://images.unsplash.com/photo-1556742044-3c52d6e88c62?auto=format&fit=crop&q=80&w=800',
                    'meta_title' => 'Cara Memilih Printer Thermal untuk POS',
                    'meta_description' => 'Tips memilih printer thermal untuk toko, warung, dan kasir POS.',
                    'status' => 'published',
                    'published_at' => now(),
                ]
            );
        }
        $post->categories()->syncWithoutDetaching([$category->id]);
        $post->affiliates()->syncWithoutDetaching([$affiliate->id]);

        $device = Device::updateOrCreate(
            ['name' => 'Printer Thermal 58mm USB'],
            [
                'type' => 'printer',
                'brand' => 'Generic',
                'description' => 'Printer thermal ekonomis untuk transaksi harian toko kecil.',
                'image' => '/images/devices/printer-58mm.jpg',
                'is_active' => true,
            ]
        );
        $device->affiliates()->syncWithoutDetaching([$affiliate->id]);

        $recommendation = RecommendedDevice::updateOrCreate(
            ['context' => 'pos'],
            [
                'title' => 'Perangkat awal untuk kasir toko',
                'description' => 'Rekomendasi perangkat dasar untuk menjalankan NessaPOS di kasir.',
            ]
        );
        $recommendation->devices()->syncWithoutDetaching([$device->id]);

        $plan = Plan::updateOrCreate(
            ['name' => 'Starter POS'],
            [
                'price' => 99000,
                'billing_type' => 'monthly',
                'features' => ['1 toko', '2 kasir', 'Laporan harian', 'Bridge printer'],
                'is_active' => true,
            ]
        );

        $promotion = Promotion::updateOrCreate(
            ['title' => 'Diskon Launching 20%'],
            [
                'description' => 'Promo untuk pengguna baru bulan pertama.',
                'discount_type' => 'percentage',
                'discount_value' => 20,
                'start_date' => now()->startOfMonth(),
                'end_date' => now()->endOfMonth(),
                'is_active' => true,
            ]
        );
        $plan->promotions()->syncWithoutDetaching([$promotion->id]);

        // --- Coba Gratis Page ---
        $tryPage = Page::updateOrCreate(
            ['slug' => 'coba-gratis'],
            [
                'title' => 'Coba Gratis',
                'meta_title' => 'Coba Gratis NessaPOS - Pilih Cara Mencoba',
                'meta_description' => 'Mulai coba NessaPOS dari browser atau download aplikasi desktop untuk print thermal.',
                'is_active' => true,
            ]
        );

        $tryHero = $tryPage->sections()->updateOrCreate(
            ['name' => 'hero'],
            ['order' => 1, 'is_active' => true]
        );
        $tryHero->contents()->updateOrCreate(['key' => 'title'], ['type' => 'text', 'value' => 'Pilih cara mencoba NessaPOS']);
        $tryHero->contents()->updateOrCreate(['key' => 'subtitle'], ['type' => 'text', 'value' => 'Mulai dari browser untuk demo cepat, atau gunakan aplikasi desktop jika toko membutuhkan print thermal dan pengalaman kasir yang lebih stabil.']);

        $tryOptions = $tryPage->sections()->updateOrCreate(
            ['name' => 'options'],
            ['order' => 2, 'is_active' => true]
        );
        $tryOptions->contents()->updateOrCreate(
            ['key' => 'items'],
            ['type' => 'json', 'value' => [
                [
                    'title' => 'Akses POS via Browser',
                    'description' => 'Coba fitur kasir NessaPOS secara instan langsung dari browser Anda tanpa perlu instalasi tambahan.',
                    'note' => 'Cara tercepat untuk mencoba fitur dasar dan alur transaksi kasir secara langsung.',
                    'link_label' => 'Mulai Demo Web',
                    'link_url' => 'https://pos.nessapos.com'
                ],
                [
                    'title' => 'Aplikasi Desktop (Cloud)',
                    'description' => 'Dukungan penuh printer thermal dan laci kasir dengan data yang tersinkronisasi secara real-time ke cloud.',
                    'note' => 'Pilihan terbaik untuk toko yang membutuhkan performa cetak struk yang stabil dan cepat.',
                    'link_label' => 'Unduh Versi Cloud',
                    'link_url' => '/downloads/NessaPOS-Desktop-Cloud.exe'
                ],
                [
                    'title' => 'Aplikasi Desktop (Offline)',
                    'description' => 'Versi mandiri untuk komputer kasir yang tetap bisa bekerja optimal meskipun tanpa koneksi internet.',
                    'note' => 'Gunakan pilihan ini jika lokasi toko Anda memiliki akses internet yang terbatas atau tidak stabil.',
                    'link_label' => 'Unduh Versi Offline',
                    'link_url' => '/downloads/NessaPOS-Desktop-Local.exe'
                ]
            ]]
        );
    }
}
