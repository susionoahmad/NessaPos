<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashierSession extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'user_id', 'start_time', 'end_time', 
        'status', 'start_amount', 'end_amount_calculated', 
        'end_amount_physical', 'difference', 'start_denoms', 'end_denoms'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
