<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateLink extends Model
{
    protected $fillable = [
        'title',
        'url',
        'platform',
        'product_name',
        'image',
        'price',
        'is_active',
    ];

    protected $casts = [
        'price' => 'float',
        'is_active' => 'boolean',
    ];

    public function posts()
    {
        return $this->belongsToMany(Post::class, 'post_affiliates', 'affiliate_id', 'post_id');
    }

    public function devices()
    {
        return $this->belongsToMany(Device::class, 'device_affiliates', 'affiliate_id', 'device_id');
    }

    public function clicks()
    {
        return $this->hasMany(AffiliateClick::class, 'affiliate_id');
    }
}
