<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SubscriptionPackage;

class PackageController extends Controller
{
    public function index(Request $request)
    {
        $query = SubscriptionPackage::where('is_active', true);

        // By default, only show Cloud packages for the web POS overlay.
        // Pass ?type=all to include desktop/local packages (e.g. for landing page).
        if ($request->query('type') !== 'all') {
            $query->where(function ($q) {
                $q->whereNotIn('slug', ['local_monthly', 'local_yearly', 'local_lifetime'])
                  ->orWhere('slug', 'not like', 'local_%');
            });
        }

        return response()->json($query->orderBy('price')->get());
    }
}
