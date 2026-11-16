<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'branch_id',
        'name',
        'phone',
        'total_orders_count',
        'total_spent',
        'last_visit_at',
    ];

    protected $casts = [
        'total_orders_count' => 'integer',
        'total_spent' => 'decimal:2',
        'last_visit_at' => 'datetime',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->orderBy('created_at', 'desc');
    }

    public function qrSessions(): HasMany
    {
        return $this->hasMany(QrSession::class);
    }

    public function parcelQrSessions(): HasMany
    {
        return $this->hasMany(ParcelQrSession::class);
    }
}
