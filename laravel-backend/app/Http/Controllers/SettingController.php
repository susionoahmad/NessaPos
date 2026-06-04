<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;

class SettingController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $tenant = $user->tenant;

        $setting = Setting::where('tenant_id', $tenant->id)->first();

        if (!$setting) {
            // Create a default setting if not found
            $setting = Setting::create([
                'tenant_id' => $tenant->id,
                'store_name' => $tenant->name ?? 'Nama Toko',
                'store_address' => $tenant->address ?? '',
                'store_phone' => $tenant->phone ?? '',
                'tax_rate' => 0.0,
                'tax_type' => 'exclusive',
                'receipt_text' => "Terima Kasih\nSudah Berbelanja!",
                'refresh_interval_sec' => 30,
                'print_session_slip' => true
            ]);
        }
        
        return response()->json($setting);
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        $tenant = $user->tenant;

        // Get existing settings for the current tenant
        $setting = Setting::where('tenant_id', $tenant->id)->first();

        // If it doesn't exist, we'll create it using defaults merged with request
        if (!$setting) {
            $setting = Setting::create([
                'tenant_id' => $tenant->id,
                'store_name' => $tenant->name ?? 'Nama Toko',
                'store_address' => $tenant->address ?? '',
                'store_phone' => $tenant->phone ?? '',
                'tax_rate' => 0.0,
                'tax_type' => 'exclusive',
                'receipt_text' => "Terima Kasih\nSudah Berbelanja!",
                'refresh_interval_sec' => 30,
                'print_session_slip' => true
            ]);
        }

        // Validate only fields that are provided and NOT empty
        // This allows Kasir to update only Bridge fields without violating 'required' on hidden fields
        $rules = [
            'store_name' => 'sometimes|string',
            'store_address' => 'sometimes|string',
            'store_phone' => 'sometimes|string',
            'tax_rate' => 'sometimes|numeric',
            'tax_type' => 'sometimes|string',
            'receipt_text' => 'sometimes|string',
            'printer_name' => 'nullable|string',
            'refresh_interval_sec' => 'sometimes|integer',
            'print_session_slip' => 'sometimes',
            'cash_drawer_enabled' => 'sometimes',
            'bridge_token' => 'nullable|string',
            'bridge_port' => 'sometimes|integer',
            'allowed_origins' => 'nullable|string',
            'decimal_digits' => 'sometimes|integer',
            'receipt_logo' => $request->hasFile('receipt_logo') ? 'image|max:1024' : 'nullable'
        ];

        $data = $request->all();

        // Handle File Upload for receipt_logo
        if ($request->hasFile('receipt_logo')) {
            if ($setting->receipt_logo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($setting->receipt_logo);
            }
            $path = $request->file('receipt_logo')->store('logos', 'public');
            $data['receipt_logo'] = $path;
        } elseif ($request->exists('receipt_logo') && $request->receipt_logo == '') {
            if ($setting->receipt_logo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($setting->receipt_logo);
            }
            $data['receipt_logo'] = null;
        } else {
            // If no new file and no removal, don't update receipt_logo
            unset($data['receipt_logo']);
        }

        $validated = validator($data, $rules)->validate();

        $setting->update($validated);

        return response()->json($setting);
    }

    public function setup(Request $request)
    {
        $validated = $request->validate([
            'store_name' => 'required',
            'store_address' => 'required',
            'store_phone' => 'required',
            'tax_rate' => 'required|numeric'
        ]);

        $setting = Setting::create(array_merge($validated, [
            'tax_type' => 'exclusive',
            'receipt_text' => 'Terima kasih!',
            'refresh_interval_sec' => 30,
            'print_session_slip' => true
        ]));

        return response()->json($setting);
    }

    public function updateSubscription(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Hanya pemilik yang dapat mengubah paket'], 403);
        }

        $request->validate([
            'plan' => 'required|in:monthly,yearly,lifetime'
        ]);

        $tenant = auth()->user()->tenant;
        $tenant->update([
            'subscription_plan' => $request->plan,
            // In a real app, this would be handled after payment. 
            // For now we simulate the selection.
            'subscription_active_until' => $request->plan === 'lifetime' ? null : now()->addMonths(1)
        ]);

        return response()->json([
            'message' => 'Paket berhasil diperbarui',
            'tenant' => $tenant
        ]);
    }
}
