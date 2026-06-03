<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'address',
        'phone',
        'is_active',
        'subscription_plan',
        'subscription_active_until',
        'trial_ends_at',
    ];

    protected $casts = [
        'subscription_active_until' => 'datetime',
        'trial_ends_at'             => 'datetime',
        'is_active'                 => 'boolean',
    ];

    /**
     * Check whether this tenant's subscription is still active.
     */
    public function isSubscriptionActive(): bool
    {
        if ($this->subscription_plan === 'lifetime') return true;
        if ($this->subscription_active_until && $this->subscription_active_until->isFuture()) return true;
        if ($this->trial_ends_at && $this->trial_ends_at->isFuture()) return true;
        return false;
    }

    public function subscriptionStatus(): string
    {
        if ($this->subscription_plan === 'lifetime') return 'active';
        if ($this->subscription_active_until && $this->subscription_active_until->isFuture()) return 'active';
        if ($this->trial_ends_at && $this->trial_ends_at->isFuture()) return 'trial';
        return 'expired';
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
