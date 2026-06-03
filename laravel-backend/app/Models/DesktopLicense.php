<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DesktopLicense extends Model
{
    use HasFactory;

    protected $fillable = [
        'serial_key',
        'device_id',
        'licensee_name',
        'expiry_date',
        'is_active',
        'activated_at',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'activated_at' => 'datetime',
        'is_active' => 'boolean',
    ];
}
