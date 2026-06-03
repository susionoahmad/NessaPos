<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant; // Import the trait

class SubscriptionRenewalRequest extends Model
{
    use BelongsToTenant; // Apply the trait

    protected $fillable = [
        'tenant_id',
        'package_id',
        'price_at_request',
        'status',
        'payment_method',
        'notes',
        'processed_at',
        'processed_by',
    ];

    protected $casts = [
        'price_at_request' => 'float',
        'processed_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function package()
    {
        return $this->belongsTo(SubscriptionPackage::class, 'package_id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
