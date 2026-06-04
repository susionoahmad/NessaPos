<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'store_name', 'store_address', 'store_phone', 
        'tax_rate', 'tax_type', 'receipt_text', 'printer_name', 
        'refresh_interval_sec', 'print_session_slip', 'cash_drawer_enabled',
        'bridge_token', 'bridge_port', 'allowed_origins',
        'receipt_logo', 'decimal_digits'
    ];

    protected $casts = [
        'print_session_slip' => 'boolean',
        'cash_drawer_enabled' => 'boolean',
    ];
}
