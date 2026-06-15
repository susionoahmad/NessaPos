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

        return response()->json($query->orderBy('price')->get());
    }
}

