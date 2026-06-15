<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SubscriptionPackage;

class PackageController extends Controller
{
    public function index(Request $request)
    {
        $query = SubscriptionPackage::where('is_active', true);

        // By default, only show Cloud packages for the web POS renewal overlay.
        // Pass ?type=all to get all packages including Desktop Local (for landing page).
        if ($request->query('type') !== 'all') {
            $query->where('slug', 'not like', 'local_%');
        }

        $packages = $query->get();

        // Urutan custom: Paket Local dulu (Bulanan, Tahunan, Lifetime), baru paket Cloud
        $order = [
            'local_monthly'  => 1,
            'local_yearly'   => 2,
            'local_lifetime' => 3,
            'monthly'        => 4,
            'yearly'         => 5,
            'lifetime'       => 6,
        ];

        $sorted = $packages->sortBy(function ($pkg) use ($order) {
            return $order[$pkg->slug] ?? 99;
        })->values();

        return response()->json($sorted);
    }
}

