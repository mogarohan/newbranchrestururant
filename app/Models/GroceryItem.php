<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroceryItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'restaurant_id',
        'branch_id',
        'name',
        'sku',
        'measurement_unit_id',
        'current_stock',
        'low_stock_threshold',
        'cost_per_unit',
    ];

    protected $casts = [
        'current_stock' => 'decimal:4',
        'low_stock_threshold' => 'decimal:4',
        'cost_per_unit' => 'decimal:2',
    ];

    // ── Scopes ──────────────────────────────────────────────
    public function scopeLowStock($query)
    {
        return $query->where('current_stock', '>', 0)
            ->whereColumn('current_stock', '<=', 'low_stock_threshold');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('current_stock', '<=', 0);
    }

    // ── Accessors ────────────────────────────────────────────
    public function getStockStatusAttribute(): string
    {
        if ($this->current_stock <= 0) return 'out_of_stock';
        if ($this->current_stock <= $this->low_stock_threshold) return 'low';
        return 'ok';
    }

    // ── Relations ────────────────────────────────────────────
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function measurementUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class);
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }
}
