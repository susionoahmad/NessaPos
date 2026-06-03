<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashierTransaction extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'session_id', 'user_id', 'type', 
        'amount', 'balance_after', 'description'
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(CashierSession::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
