<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'order_id', 'payment_method', 
        'amount_paid', 'change_amount', 'status'
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
