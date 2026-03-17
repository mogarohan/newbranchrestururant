<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QrSession extends Model
{
    protected $fillable = [
        'restaurant_id',
        'restaurant_table_id',
        'session_token',
        'customer_name',
        'is_primary',
        'join_status',
        'is_active',
        'host_session_id',
        'expires_at',
        'branch_id',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'restaurant_table_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
    public function guests(): HasMany
    {
        return $this->hasMany(QrSession::class, 'host_session_id');
    }

    // Get the host of this guest
    public function host(): BelongsTo
    {
        return $this->belongsTo(QrSession::class, 'host_session_id');
    }
}
