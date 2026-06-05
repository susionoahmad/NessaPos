<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPackage extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'price',
        'original_price',
        'duration_days',
        'features',
        'style',
        'is_active',
    ];

    protected $casts = [
        'price' => 'float',
        'original_price' => 'float',
        'duration_days' => 'integer',
        'features' => 'array',
        'style' => 'array',
        'is_active' => 'boolean',
    ];
}
