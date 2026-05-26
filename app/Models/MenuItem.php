<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MenuItem extends Model
{
    protected $fillable = [
        'restaurant_id',
        'category_id',
        'name',
        'description',
        'price',
        'image_path',
        'is_available',
        'branch_id',
        'type',
        'track_stock',
        'stock_quantity',
        'low_stock_threshold',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_available' => 'boolean',
        'track_stock' => 'boolean',
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
    ];

    // ── Scopes ──────────────────────────────────────────────
    public function scopeLowStock($query)
    {
        return $query->where('track_stock', true)
            ->whereNotNull('stock_quantity')
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('track_stock', true)
            ->where('stock_quantity', 0);
    }

    // ── Accessors ────────────────────────────────────────────
    public function getStockStatusAttribute(): string
    {
        if (!$this->track_stock || $this->stock_quantity === null)
            return 'untracked';
        if ($this->stock_quantity <= 0)
            return 'out_of_stock';
        if ($this->stock_quantity <= $this->low_stock_threshold)
            return 'low';
        return 'ok';
    }

    // ── Helpers ──────────────────────────────────────────────

    /**
     * Called when a chef clicks "Preparing".
     * Deducts qty for every item in the order.
     */
    public static function deductStockForOrder(\App\Models\Order $order): void
    {
        foreach ($order->items as $orderItem) {
            if (!$orderItem->menu_item_id)
                continue;

            $menuItem = static::find($orderItem->menu_item_id);
            if (!$menuItem || !$menuItem->track_stock || $menuItem->stock_quantity === null)
                continue;

            $newQty = max(0, $menuItem->stock_quantity - $orderItem->quantity);
            $menuItem->update(['stock_quantity' => $newQty]);

            // Auto mark unavailable when stock hits 0
            if ($newQty === 0) {
                $menuItem->update(['is_available' => false]);
            }
        }
    }

    // ── Relations ────────────────────────────────────────────
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    protected static function booted()
    {
        static::deleting(function (MenuItem $item) {
            if ($item->image_path && Storage::disk('public')->exists($item->image_path)) {
                Storage::disk('public')->delete($item->image_path);
            }
        });
    }
}