<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = ['name', 'type', 'brand', 'description', 'image', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function affiliates()
    {
        return $this->belongsToMany(AffiliateLink::class, 'device_affiliates', 'device_id', 'affiliate_id');
    }

    public function recommendations()
    {
        return $this->belongsToMany(RecommendedDevice::class, 'recommended_device_items', 'device_id', 'recommendation_id');
    }
}
