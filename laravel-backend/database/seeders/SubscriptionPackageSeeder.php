<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubscriptionPackage;

class SubscriptionPackageSeeder extends Seeder
{
    public function run(): void
    {
        SubscriptionPackage::updateOrCreate(
            ['slug' => 'monthly'],
            [
                'name' => 'Cloud (Bulanan)',
                'price' => 69000,
                'original_price' => 99000,
                'duration_days' => 30,
                'features' => [
                    'Transaksi Tanpa Batas',
                    'Laporan Lengkap',
                    'Multi Kasir',
                    'Sinkronisasi Cloud'
                ],
                'style' => [
                    'badge' => null,
                    'button' => 'btn-primary'
                ]
            ]
        );

        SubscriptionPackage::updateOrCreate(
            ['slug' => 'yearly'],
            [
                'name' => 'Cloud (Tahunan)',
                'price' => 690000,
                'original_price' => 990000,
                'duration_days' => 365,
                'features' => [
                    'Semua Fitur Pro',
                    'Prioritas Support',
                    'Hemat 2 Bulan',
                    'Akses Multi-Device'
                ],
                'style' => [
                    'badge' => 'PALING HEMAT',
                    'button' => 'btn-primary-inverse',
                    'card_class' => 'highlight'
                ]
            ]
        );

        SubscriptionPackage::updateOrCreate(
            ['slug' => 'lifetime'],
            [
                'name' => 'Cloud (Lifetime)',
                'price' => 2500000,
                'original_price' => 2900000,
                'duration_days' => null,
                'features' => [
                    'Miliki Selamanya',
                    'Tanpa Biaya Bulanan',
                    'Setup & Training',
                    'Full Cloud Arsitektur'
                ],
                'style' => [
                    'badge' => null,
                    'button' => 'btn-primary'
                ]
            ]
        );

        // Desktop Local Packages
        SubscriptionPackage::updateOrCreate(
            ['slug' => 'local_monthly'],
            [
                'name' => 'Local (Bulanan)',
                'price' => 29000,
                'original_price' => 49000,
                'duration_days' => 30,
                'features' => [
                    'Kasir Desktop Lokal',
                    'Tanpa Perlu Internet',
                    'Data di Komputer Sendiri',
                    'Print Struk Stabil'
                ],
                'style' => [
                    'badge' => 'EKONOMIS',
                    'button' => 'btn-primary'
                ]
            ]
        );

        SubscriptionPackage::updateOrCreate(
            ['slug' => 'local_yearly'],
            [
                'name' => 'Local (Tahunan)',
                'price' => 199000,
                'original_price' => 299000,
                'duration_days' => 365,
                'features' => [
                    'Semua Fitur Lokal',
                    'Lisensi 1 Tahun',
                    'Hemat Biaya Operasional',
                    'Ideal untuk Toko Offline'
                ],
                'style' => [
                    'badge' => 'BEST VALUE',
                    'button' => 'btn-primary'
                ]
            ]
        );

        SubscriptionPackage::updateOrCreate(
            ['slug' => 'local_lifetime'],
            [
                'name' => 'Local (Lifetime)',
                'price' => 499000,
                'original_price' => 799000,
                'duration_days' => null,
                'features' => [
                    'Bayar 1x Selamanya',
                    'Tanpa Biaya Langganan',
                    'Lisensi Perangkat Permanen',
                    'Backup Data Lokal'
                ],
                'style' => [
                    'badge' => 'SPECIAL OFFER',
                    'button' => 'btn-primary'
                ]
            ]
        );
    }
}
