<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $posts = [
            [
                'title' => 'Kenapa POS Modern Memakai Arsitektur Terpisah?',
                'slug' => 'arsitektur-pos-modern',
                'content' => 'Banyak toko mulai beralih dari aplikasi kasir lama ke POS modern karena kebutuhan operasional makin luas. Kasir harus cepat, data penjualan harus bisa dipantau dari banyak perangkat, dan pemilik toko ingin laporan yang tidak tercecer di komputer kasir saja.

Karena itu POS modern biasanya memakai arsitektur terpisah: tampilan kasir untuk transaksi, API sebagai pusat data, dan aplikasi desktop untuk kebutuhan perangkat lokal seperti printer thermal. Konsepnya sederhana: setiap bagian fokus pada tugasnya masing-masing sehingga sistem lebih stabil dan lebih mudah dikembangkan.

Bagi pemilik bisnis, manfaatnya terasa langsung. Jika suatu hari toko bertambah cabang, data tetap bisa dikumpulkan di satu pusat. Jika kasir memakai perangkat berbeda, proses transaksi tetap konsisten. Jika butuh cetak struk, desktop app atau bridge lokal bisa membantu tanpa mengganggu alur transaksi.

Dari sisi teknis, pemisahan frontend, API, dan desktop app juga membuat update produk lebih aman. Tim bisa memperbaiki halaman kasir tanpa harus mengubah layanan data, atau meningkatkan fitur cetak tanpa mengganggu laporan penjualan.

Jika Anda sedang mencari sistem kasir yang siap untuk kebutuhan toko hari ini dan tetap fleksibel saat bisnis tumbuh, lihat ringkasan fitur NessaPOS di halaman utama. Untuk perlengkapan toko, Anda juga bisa menyiapkan printer thermal 58mm atau 80mm dan barcode scanner USB sebagai perangkat pendukung.',
                'excerpt' => 'Panduan singkat untuk memahami frontend, API, dan desktop app pada sistem kasir SaaS tanpa bahasa yang terlalu teknis.',
                'status' => 'published',
                'published_at' => now(),
                'meta_title' => 'Kenapa POS Modern Memakai Arsitektur Terpisah?',
                'meta_description' => 'Panduan singkat untuk memahami frontend, API, dan desktop app pada sistem kasir SaaS tanpa bahasa yang terlalu teknis.',
            ],
            [
                'title' => 'Printer Thermal dan Bridge Lokal',
                'slug' => 'printer-thermal',
                'content' => 'Cetak struk adalah bagian kecil yang sangat terasa di meja kasir. Jika setiap transaksi harus membuka pop-up print browser, kasir menjadi lambat dan antrean pelanggan bisa lebih panjang.

Solusi yang lebih rapi adalah memakai bridge lokal. Secara sederhana, bridge lokal menjadi penghubung antara aplikasi POS dan printer thermal yang terpasang di komputer kasir. Kasir tetap bekerja dari layar transaksi, lalu sistem mengirim perintah cetak ke printer tanpa langkah manual yang berulang.

Untuk toko, manfaatnya adalah proses checkout lebih cepat, tampilan operasional lebih profesional, dan kasir tidak perlu memahami pengaturan teknis setiap kali mencetak struk.

Saat memilih perangkat, pastikan printer thermal mendukung ukuran struk yang Anda butuhkan. Printer 58mm biasanya cukup untuk toko kecil, sedangkan 80mm lebih nyaman untuk struk yang memuat banyak item. Barcode scanner USB juga bisa menjadi pilihan affiliate yang relevan karena membantu mempercepat input produk.

NessaPOS dirancang agar kebutuhan print thermal seperti ini tetap praktis. Anda bisa melihat fitur kasir dan print di halaman utama sebelum menentukan perangkat yang cocok untuk toko.',
                'excerpt' => 'Cara cetak struk tanpa pop-up browser agar proses kasir tetap cepat dan terlihat profesional.',
                'status' => 'published',
                'published_at' => now(),
                'meta_title' => 'Printer Thermal dan Bridge Lokal',
                'meta_description' => 'Cara cetak struk tanpa pop-up browser agar proses kasir tetap cepat dan terlihat profesional.',
            ],
            [
                'title' => 'Kenapa Sistem Kasir Multi-Toko Perlu API Terpusat?',
                'slug' => 'subscription-dan-tenant',
                'content' => 'Saat bisnis masih punya satu toko, data kasir mungkin terasa cukup disimpan di satu perangkat. Namun ketika mulai membuka cabang, kebutuhan berubah. Pemilik bisnis perlu melihat laporan dari beberapa toko, mengatur user kasir, dan memastikan data transaksi tetap konsisten.

Di sinilah API terpusat menjadi penting. API berfungsi sebagai pintu data utama yang menerima login, produk, transaksi, laporan, dan pengaturan toko. Dengan model ini, setiap cabang bisa memakai aplikasi kasir masing-masing, tetapi data tetap masuk ke pusat.

Untuk produk POS SaaS, API terpusat juga memudahkan pengelolaan subscription. Pemilik sistem bisa mengatur masa aktif toko, paket bulanan atau tahunan, serta akses admin tanpa perlu mengubah aplikasi di setiap perangkat kasir.

Pendekatan ini membantu bisnis bertumbuh lebih rapi. Data aman di cloud, akses lebih mudah dikontrol, dan pemilik toko bisa membaca kondisi bisnis tanpa harus datang ke setiap cabang.

Jika Anda ingin mulai dari satu toko lalu punya ruang untuk berkembang ke banyak cabang, NessaPOS menyediakan alur kasir, stok, laporan, dan print thermal dalam satu platform. Perangkat tambahan seperti barcode scanner dan printer thermal tetap bisa disiapkan bertahap sesuai kebutuhan toko.',
                'excerpt' => 'Penjelasan sederhana tentang data cloud, cabang toko, subscription, dan kontrol akses pada POS SaaS.',
                'status' => 'published',
                'published_at' => now(),
                'meta_title' => 'Kenapa Sistem Kasir Multi-Toko Perlu API Terpusat?',
                'meta_description' => 'Penjelasan sederhana tentang data cloud, cabang toko, subscription, dan kontrol akses pada POS SaaS.',
            ],
        ];

        foreach ($posts as $postData) {
            Post::create($postData);
        }
    }
}