<?php

namespace App\Traits;

use App\Scopes\TenantScope; // Import the dedicated TenantScope class
use App\Models\Tenant; // Still needed for the tenant() relationship
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth; // Needed for Auth::check() and Auth::user()

trait BelongsToTenant
{
    protected static function bootBelongsToTenant()
    {
        static::creating(function ($model) {
            // Hanya set tenant_id jika belum diatur dan user terautentikasi serta bukan superadmin
            if (empty($model->tenant_id) && Auth::check() && Auth::user()->role !== 'superadmin') {
                $model->tenant_id = Auth::user()->tenant_id;
            }
        });

        // Terapkan TenantScope yang sudah didefinisikan
        static::addGlobalScope(new TenantScope);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
