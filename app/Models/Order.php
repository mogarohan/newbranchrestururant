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
        'room_session_id', // 👈 ADD THIS
        'status',
        'payment_status',
        'customer_name',
        'customer_phone',
        'customer_id',
        'notes',
        //'subtotal',
        'total_amount',
        'branch_id',
        'parcel_qr_session_id',
        'service_type',
    ];
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function parcelQrSession()
    {
        return $this->belongsTo(ParcelQrSession::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
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
    public function roomSession()
    {
        return $this->belongsTo(\App\Models\RoomSession::class);
    }

    public function syncStatusFromItems(bool $save = true): string
    {
        $activeItems = $this->items()
            ->where('quantity', '>', 0)
            ->where('status', '!=', 'cancelled')
            ->get();

        if ($activeItems->isEmpty()) {
            return $this->status;
        }

        $allServed = $activeItems->every(fn($item) => in_array($item->status, ['served', 'completed']));
        $allReady = $activeItems->every(fn($item) => in_array($item->status, ['ready', 'served', 'completed']));
        $anyPreparing = $activeItems->contains(fn($item) => $item->status === 'preparing');
        $anyReady = $activeItems->contains(fn($item) => in_array($item->status, ['ready', 'served', 'completed']));

        $newStatus = $this->status;

        if ($allServed) {
            $newStatus = 'served';
        } elseif ($allReady) {
            if (!in_array($this->status, ['served', 'completed'])) {
                $newStatus = 'ready';
            }
        } elseif ($anyPreparing || $anyReady) {
            if (!in_array($this->status, ['served', 'completed'])) {
                $newStatus = 'preparing';
            }
        } else {
            // All items are placed
            if (in_array($this->status, ['preparing', 'ready'])) {
                $newStatus = 'placed';
            }
        }

        if ($save && $newStatus !== $this->status) {
            $oldStatus = $this->status;
            $this->update(['status' => $newStatus]);

            if (in_array($newStatus, ['placed', 'preparing', 'ready'])) {
                KitchenQueue::updateOrCreate(
                    ['order_id' => $this->id],
                    ['current_status' => $newStatus]
                );
            } elseif (in_array($newStatus, ['served', 'completed', 'cancelled', 'rejected'])) {
                KitchenQueue::where('order_id', $this->id)->delete();
            }

            OrderStatusLog::create([
                'order_id' => $this->id,
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'changed_by' => auth()->id() ?? null,
            ]);

            \App\Events\OrderStatusUpdated::dispatch($this->fresh(['items.menuItem']));
        }

        return $newStatus;
    }

    public function setAllItemsStatus(string $status): void
    {
        $this->items()
            ->where('quantity', '>', 0)
            ->where('status', '!=', 'cancelled')
            ->update(['status' => $status]);

        $this->unsetRelation('items');

        $this->syncStatusFromItems(true);
    }
}
