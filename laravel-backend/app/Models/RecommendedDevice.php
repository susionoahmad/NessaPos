<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecommendedDevice extends Model
{
    protected $fillable = ['title', 'description', 'context'];

    public function devices()
    {
        return $this->belongsToMany(Device::class, 'recommended_device_items', 'recommendation_id', 'device_id');
    }
}
