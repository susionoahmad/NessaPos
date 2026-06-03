<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Vault extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'balance'];
}
