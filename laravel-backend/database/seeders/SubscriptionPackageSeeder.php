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
                'name' => 'Bulanan',
                'price' => 99000,
                'duration_days' => 30,
                'features' => [
                    'Transaksi Tanpa Batas',
                    'Laporan Lengkap',
                    'Multi Kasir'
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
                'name' => 'Tahunan',
                'price' => 990000,
                'duration_days' => 365,
                'features' => [
                    'Semua Fitur Pro',
                    'Prioritas Support',
                    'Hemat 2 Bulan'
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
                'name' => 'Lifetime',
                'price' => 2900000,
                'duration_days' => null,
                'features' => [
                    'Miliki Selamanya',
                    'Tanpa Biaya Bulanan',
                    'Setup & Training'
                ],
                'style' => [
                    'badge' => null,
                    'button' => 'btn-primary'
                ]
            ]
        );
    }
}
