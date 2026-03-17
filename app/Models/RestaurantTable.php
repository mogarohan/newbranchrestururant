<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class RestaurantTable extends Model
{
    protected $fillable = [
        'restaurant_id',
        'table_number',
        'qr_token',
        'qr_path',
        'seating_capacity',
        'is_active',
        'status',
        'branch_id',
    ];

    protected static function booted()
    {
        static::creating(function ($table) {
            $table->qr_token = Str::uuid()->toString();
        });

        static::deleting(function ($table) {
            if ($table->qr_path && Storage::disk('public')->exists($table->qr_path)) {
                Storage::disk('public')->delete($table->qr_path);
            }
        });

    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(QrSession::class);
    }
    public function qrSessions()
    {
        return $this->hasMany(QrSession::class, 'restaurant_table_id');
    }
    public function orders()
    {
        return $this->hasMany(\App\Models\Order::class, 'restaurant_table_id');
    }
}
