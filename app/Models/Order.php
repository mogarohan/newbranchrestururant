<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'restaurant_id',
        'restaurant_table_id',
        'qr_session_id',
        'status',
        'payment_status',
        'customer_name',
        'notes',
        //'subtotal',
        'total_amount',
        'branch_id',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'restaurant_table_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(QrSession::class, 'qr_session_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
    public function kitchenQueue()
    {
        return $this->hasOne(KitchenQueue::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(OrderStatusLog::class);
    }
    public function restaurantTable()
    {
        // Make sure the foreign key ('restaurant_table_id') matches your database column. 
        // If your column is named just 'table_id', change it below.
        return $this->belongsTo(RestaurantTable::class, 'restaurant_table_id');
    }
}
