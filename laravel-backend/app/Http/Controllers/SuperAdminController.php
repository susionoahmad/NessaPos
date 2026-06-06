<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\User;
use App\Models\SubscriptionPackage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminController extends Controller
{
    /**
     * List all tenants with their subscription status.
     */
    public function index()
    {
        $tenants = Tenant::withoutGlobalScopes()
            ->with([
                'users' => function ($q) {
                    $q->withoutGlobalScopes()->select('id', 'tenant_id', 'username', 'role');
                }
            ])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($tenant) {
                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'address' => $tenant->address,
                    'phone' => $tenant->phone,
                    'is_active' => $tenant->is_active,
                    'subscription_plan' => $tenant->subscription_plan,
                    'subscription_active_until' => $tenant->subscription_active_until?->format('Y-m-d H:i:s'),
                    'trial_ends_at' => $tenant->trial_ends_at?->format('Y-m-d H:i:s'),
                    'subscription_status' => $tenant->subscriptionStatus(),
                    'user_count' => $tenant->users->count(),
                    'created_at' => $tenant->created_at?->format('Y-m-d'),
                ];
            });

        return response()->json($tenants);
    }

    /**
     * Create a new tenant (onboard new store).
     */
    public function createTenant(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|unique:tenants,slug',
            'admin_username' => 'required|string',
            'admin_password' => 'required|string|min:6',
            'subscription_plan' => 'in:trial,monthly,yearly,lifetime',
        ]);

        $slug = $request->slug ?? Str::slug($request->name);

        // Ensure slug is unique
        $base = $slug;
        $i = 1;
        while (Tenant::withoutGlobalScopes()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        $tenant = Tenant::withoutGlobalScopes()->create([
            'name' => $request->name,
            'slug' => $slug,
            'address' => $request->address ?? '',
            'phone' => $request->phone ?? '',
            'is_active' => true,
            'subscription_plan' => $request->subscription_plan ?? 'trial',
            'subscription_active_until' => $request->subscription_active_until ?? null,
            'trial_ends_at' => $request->subscription_plan === 'trial'
                ? ($request->trial_ends_at ?? now()->addDays(7))
                : null,
        ]);

        // Create default admin user for this tenant
        User::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'username' => $request->admin_username,
            'password' => Hash::make($request->admin_password),
            'role' => 'admin',
        ]);

        // Create default settings record
        \App\Models\Setting::create([
            'tenant_id' => $tenant->id,
            'store_name' => $tenant->name,
            'store_address' => $tenant->address ?? '',
            'store_phone' => $tenant->phone ?? '',
            'tax_rate' => 0,
            'tax_type' => 'exclusive',
            'receipt_text' => "Terima Kasih\nSudah Berbelanja!",
        ]);

        return response()->json([
            'message' => 'Toko berhasil dibuat',
            'tenant' => $tenant,
        ], 201);
    }

    /**
     * Update a tenant's subscription info or active status.
     */
    public function updateSubscription(Request $request, $id)
    {
        $tenant = Tenant::withoutGlobalScopes()->findOrFail($id);

        $request->validate([
            'subscription_plan' => 'in:trial,monthly,yearly,lifetime',
            'subscription_active_until' => 'nullable|date',
            'trial_ends_at' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $dataToUpdate = $request->only([
            'subscription_plan',
            'subscription_active_until',
            'is_active',
            'name',
            'address',
            'phone',
        ]);
        
        // Handle trial default manually if empty
        if ($request->subscription_plan === 'trial') {
            $dataToUpdate['trial_ends_at'] = empty($request->trial_ends_at) ? now()->addDays(7) : $request->trial_ends_at;
        } else {
            $dataToUpdate['trial_ends_at'] = empty($request->trial_ends_at) ? null : $request->trial_ends_at;
        }

        $tenant->update($dataToUpdate);

        return response()->json([
            'message' => 'Data toko diperbarui',
            'tenant' => $tenant,
        ]);
    }

    /**
     * Toggle a tenant's active status.
     */
    public function toggleActive($id)
    {
        $tenant = Tenant::withoutGlobalScopes()->findOrFail($id);
        $tenant->update(['is_active' => !$tenant->is_active]);

        return response()->json([
            'message' => $tenant->is_active ? 'Toko diaktifkan' : 'Toko dinonaktifkan',
            'is_active' => $tenant->is_active,
        ]);
    }

    /**
     * Delete a tenant and all its data.
     */
    public function destroy($id)
    {
        $tenant = Tenant::withoutGlobalScopes()->findOrFail($id);
        // Delete associated users first
        User::withoutGlobalScopes()->where('tenant_id', $id)->delete();
        $tenant->delete();

        return response()->json(['message' => 'Toko dihapus']);
    }

    /**
     * List all subscription packages
     */
    public function listPackages()
    {
        return response()->json(SubscriptionPackage::orderBy('price', 'asc')->get());
    }

    /**
     * Update a subscription package
     */
    public function updatePackage(Request $request, $id)
    {
        $package = SubscriptionPackage::findOrFail($id);

        $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric',
            'original_price' => 'nullable|numeric',
            'features' => 'nullable|array',
            'is_active' => 'boolean'
        ]);

        $package->update($request->only([
            'name',
            'price',
            'original_price',
            'features',
            'is_active'
        ]));

        return response()->json([
            'message' => 'Paket berhasil diperbarui',
            'package' => $package
        ]);
    }

    /**
     * Get summary stats for SuperAdmin dashboard
     */
    public function stats()
    {
        return response()->json([
            'pending_renewals_count' => \App\Models\SubscriptionRenewalRequest::where('status', 'pending')->count(),
            'total_tenants' => Tenant::withoutGlobalScopes()->count(),
            'active_tenants' => Tenant::withoutGlobalScopes()->where('is_active', true)->count(),
            'site_stats' => [
                'landing_page_visits' => \App\Models\SiteAnalytic::where('event_type', 'landing_page_visit')->count(),
                'desktop_download_clicks' => \App\Models\SiteAnalytic::whereIn('event_type', ['desktop_download_click', 'desktop_download_local_click', 'desktop_download_cloud_click'])->count(),
                'desktop_download_local_clicks' => \App\Models\SiteAnalytic::where('event_type', 'desktop_download_local_click')->count(),
                'desktop_download_cloud_clicks' => \App\Models\SiteAnalytic::where('event_type', 'desktop_download_cloud_click')->count(),
                'pos_frontend_clicks' => \App\Models\SiteAnalytic::where('event_type', 'pos_frontend_click')->count(),
                // Daily stats for the last 30 days could be added here if needed
                'daily_visits' => \App\Models\SiteAnalytic::where('event_type', 'landing_page_visit')
                    ->where('created_at', '>=', now()->subDays(30))
                    ->selectRaw('DATE(created_at) as date, count(*) as count')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get()
            ]
        ]);
    }
}
