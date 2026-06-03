<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = ['name', 'price', 'billing_type', 'features', 'is_active'];

    protected $casts = [
        'price' => 'float',
        'features' => 'array',
        'is_active' => 'boolean',
    ];

    public function promotions()
    {
        return $this->belongsToMany(Promotion::class, 'plan_promotions');
    }
}
