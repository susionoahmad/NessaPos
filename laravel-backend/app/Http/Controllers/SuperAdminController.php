<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Product;
use App\Models\StockMutation;
use App\Models\SubscriptionPackage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            ->withCount([
                'products as product_count' => function ($q) {
                    $q->withoutGlobalScopes();
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
                    'product_count' => $tenant->product_count ?? 0,
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
            'subscription_plan' => 'in:trial,monthly,yearly,lifetime,local_monthly,local_yearly,local_lifetime',
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
            'subscription_plan' => 'in:trial,monthly,yearly,lifetime,local_monthly,local_yearly,local_lifetime',
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
     * Create a new subscription package
     */
    public function storePackage(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'slug' => 'required|string|unique:subscription_packages,slug',
            'price' => 'required|numeric',
            'original_price' => 'nullable|numeric',
            'duration_days' => 'nullable|integer',
            'features' => 'nullable|array',
            'style' => 'nullable|array',
        ]);

        $package = SubscriptionPackage::create($request->all());

        return response()->json([
            'message' => 'Paket berhasil dibuat',
            'package' => $package
        ], 201);
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
     * Delete a subscription package
     */
    public function destroyPackage($id)
    {
        $package = SubscriptionPackage::findOrFail($id);
        $package->delete();

        return response()->json(['message' => 'Paket berhasil dihapus']);
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
                'desktop_download_local_clicks' => \App\Models\SiteAnalytic::whereIn('event_type', ['desktop_download_local_click', 'desktop_download_click'])->count(),
                'desktop_download_cloud_clicks' => \App\Models\SiteAnalytic::where('event_type', 'desktop_download_cloud_click')->count(),
                'pos_frontend_clicks' => \App\Models\SiteAnalytic::where('event_type', 'pos_frontend_click')->count(),
                // Daily stats for the last 30 days filled with 0s for missing dates
                'daily_visits' => collect(range(0, 29))->reverse()->map(function ($days) {
                    $date = now()->subDays($days)->format('Y-m-d');
                    $count = \App\Models\SiteAnalytic::where('event_type', 'landing_page_visit')
                        ->whereDate('created_at', $date)
                        ->count();
                    return ['date' => $date, 'count' => $count];
                })->values()
            ]
        ]);
    }

    /**
     * Get all products belonging to a specific tenant.
     */
    public function tenantProducts($id)
    {
        $tenant = Tenant::withoutGlobalScopes()->findOrFail($id);

        $products = Product::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ],
            'products' => $products,
        ]);
    }

    /**
     * Create a new product for a specific tenant.
     */
    public function storeTenantProduct(Request $request, $id)
    {
        $tenant = Tenant::withoutGlobalScopes()->findOrFail($id);

        $validated = $request->validate([
            'name'            => 'required|string',
            'barcode'         => 'nullable|string',
            'cost_price'      => 'numeric',
            'selling_price'   => 'numeric',
            'shelf_stock'     => 'integer',
            'warehouse_stock' => 'integer',
        ]);

        $product = Product::withoutGlobalScopes()->create([
            'tenant_id'       => $tenant->id,
            'name'            => $validated['name'],
            'barcode'         => $validated['barcode'] ?: null,
            'cost_price'      => $validated['cost_price'] ?? 0,
            'selling_price'   => $validated['selling_price'] ?? 0,
            'shelf_stock'     => $validated['shelf_stock'] ?? 0,
            'warehouse_stock' => $validated['warehouse_stock'] ?? 0,
        ]);

        if ($product->shelf_stock > 0 || $product->warehouse_stock > 0) {
            StockMutation::withoutGlobalScopes()->create([
                'tenant_id'     => $tenant->id,
                'product_id'    => $product->id,
                'type'          => 'ADJUSTMENT',
                'from_location' => 'INITIAL',
                'to_location'   => $product->shelf_stock > 0 ? 'SHELF' : 'WAREHOUSE',
                'quantity'      => $product->shelf_stock + $product->warehouse_stock,
                'reference'     => 'SuperAdmin Initial Stock',
                'created_by'    => Auth::id(),
            ]);
        }

        return response()->json($product, 201);
    }

    /**
     * Update a product for a specific tenant.
     */
    public function updateTenantProduct(Request $request, $id, $productId)
    {
        $tenant = Tenant::withoutGlobalScopes()->findOrFail($id);

        $product = Product::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($productId);

        $validated = $request->validate([
            'name'            => 'required|string',
            'barcode'         => 'nullable|string',
            'cost_price'      => 'numeric',
            'selling_price'   => 'numeric',
            'shelf_stock'     => 'integer',
            'warehouse_stock' => 'integer',
        ]);

        $oldShelf = (int) $product->shelf_stock;
        $oldWh    = (int) $product->warehouse_stock;

        $product->update([
            'name'            => $validated['name'],
            'barcode'         => $validated['barcode'] ?: null,
            'cost_price'      => $validated['cost_price'] ?? 0,
            'selling_price'   => $validated['selling_price'] ?? 0,
            'shelf_stock'     => $validated['shelf_stock'] ?? 0,
            'warehouse_stock' => $validated['warehouse_stock'] ?? 0,
        ]);

        if ($oldShelf !== (int) $product->shelf_stock) {
            StockMutation::withoutGlobalScopes()->create([
                'tenant_id'     => $tenant->id,
                'product_id'    => $product->id,
                'type'          => 'ADJUSTMENT',
                'from_location' => $oldShelf < $product->shelf_stock ? 'EXTERNAL' : 'SHELF',
                'to_location'   => $oldShelf < $product->shelf_stock ? 'SHELF' : 'ADJUSTMENT',
                'quantity'      => abs($product->shelf_stock - $oldShelf),
                'reference'     => 'SuperAdmin Adjustment (Shelf)',
                'created_by'    => Auth::id(),
            ]);
        }

        if ($oldWh !== (int) $product->warehouse_stock) {
            StockMutation::withoutGlobalScopes()->create([
                'tenant_id'     => $tenant->id,
                'product_id'    => $product->id,
                'type'          => 'ADJUSTMENT',
                'from_location' => $oldWh < $product->warehouse_stock ? 'EXTERNAL' : 'WAREHOUSE',
                'to_location'   => $oldWh < $product->warehouse_stock ? 'WAREHOUSE' : 'ADJUSTMENT',
                'quantity'      => abs($product->warehouse_stock - $oldWh),
                'reference'     => 'SuperAdmin Adjustment (Warehouse)',
                'created_by'    => Auth::id(),
            ]);
        }

        return response()->json($product);
    }

    /**
     * Bulk update products for a specific tenant from UI table edits.
     */
    public function bulkUpdateTenantProducts(Request $request, $id)
    {
        $tenant = Tenant::withoutGlobalScopes()->findOrFail($id);

        $validated = $request->validate([
            'products'                   => 'required|array',
            'products.*.id'              => 'required|integer',
            'products.*.name'            => 'required|string',
            'products.*.barcode'         => 'nullable|string',
            'products.*.cost_price'      => 'numeric',
            'products.*.selling_price'   => 'numeric',
            'products.*.shelf_stock'     => 'integer',
            'products.*.warehouse_stock' => 'integer',
        ]);

        $updatedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($validated['products'] as $item) {
                $product = Product::withoutGlobalScopes()
                    ->where('tenant_id', $tenant->id)
                    ->where('id', $item['id'])
                    ->first();

                if (!$product) continue;

                $oldShelf = (int) $product->shelf_stock;
                $oldWh    = (int) $product->warehouse_stock;

                $product->update([
                    'name'            => $item['name'],
                    'barcode'         => !empty($item['barcode']) ? $item['barcode'] : null,
                    'cost_price'      => $item['cost_price'] ?? 0,
                    'selling_price'   => $item['selling_price'] ?? 0,
                    'shelf_stock'     => $item['shelf_stock'] ?? 0,
                    'warehouse_stock' => $item['warehouse_stock'] ?? 0,
                ]);

                if ($oldShelf !== (int) $product->shelf_stock) {
                    StockMutation::withoutGlobalScopes()->create([
                        'tenant_id'     => $tenant->id,
                        'product_id'    => $product->id,
                        'type'          => 'ADJUSTMENT',
                        'from_location' => $oldShelf < $product->shelf_stock ? 'EXTERNAL' : 'SHELF',
                        'to_location'   => $oldShelf < $product->shelf_stock ? 'SHELF' : 'ADJUSTMENT',
                        'quantity'      => abs($product->shelf_stock - $oldShelf),
                        'reference'     => 'SuperAdmin Bulk Edit (Shelf)',
                        'created_by'    => Auth::id(),
                    ]);
                }

                if ($oldWh !== (int) $product->warehouse_stock) {
                    StockMutation::withoutGlobalScopes()->create([
                        'tenant_id'     => $tenant->id,
                        'product_id'    => $product->id,
                        'type'          => 'ADJUSTMENT',
                        'from_location' => $oldWh < $product->warehouse_stock ? 'EXTERNAL' : 'WAREHOUSE',
                        'to_location'   => $oldWh < $product->warehouse_stock ? 'WAREHOUSE' : 'ADJUSTMENT',
                        'quantity'      => abs($product->warehouse_stock - $oldWh),
                        'reference'     => 'SuperAdmin Bulk Edit (Warehouse)',
                        'created_by'    => Auth::id(),
                    ]);
                }

                $updatedCount++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menyimpan perubahan massal: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'message' => "{$updatedCount} produk berhasil diperbarui.",
            'updated' => $updatedCount,
        ]);
    }

    /**
     * Delete a product for a specific tenant.
     */
    public function destroyTenantProduct($id, $productId)
    {
        $tenant = Tenant::withoutGlobalScopes()->findOrFail($id);

        $product = Product::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($productId);

        $product->delete();

        return response()->json(['message' => 'Produk dihapus']);
    }
}
