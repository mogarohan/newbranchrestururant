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
        'room_session_id',
        'status',
        'payment_status',
        'customer_name',
        'notes',
        'total_amount',
       
       'is_hidden',
        'branch_id',
        'parcel_qr_session_id',
        'service_type',
        'daily_order_number', // 👈 NAYU ADD KARYU
    ];

    // 🌟 AUTOMATIC DAILY ORDER SEQUENCE PER RESTAURANT 🌟
    protected static function booted()
    {
        static::creating(function ($order) {
            if (empty($order->daily_order_number) && $order->restaurant_id) {
                // Find highest order number for today for THIS specific restaurant
                $maxNumber = static::where('restaurant_id', $order->restaurant_id)
                    ->whereDate('created_at', now()->toDateString())
                    ->max('daily_order_number');
                
                // Assign next sequence starting from 1
                $order->daily_order_number = $maxNumber ? $maxNumber + 1 : 1;
            }
        });
    }

    public function parcelQrSession()
    {
        return $this->belongsTo(ParcelQrSession::class);
    }

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
        return $this->belongsTo(RestaurantTable::class, 'restaurant_table_id');
    }
    
    public function roomSession()
    {
        return $this->belongsTo(\App\Models\RoomSession::class);
    }
}