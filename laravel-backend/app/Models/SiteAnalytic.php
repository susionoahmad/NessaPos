<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteAnalytic extends Model
{
    protected $fillable = [
        'event_type',
        'ip_address',
        'user_agent',
    ];
}
