<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SubscriptionPackage;

class PackageController extends Controller
{
    public function index()
    {
        return response()->json(SubscriptionPackage::where('is_active', true)->get());
    }
}
