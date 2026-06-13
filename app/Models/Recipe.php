<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recipe extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'menu_item_id',
        'grocery_item_id',
        'quantity_required',
        'measurement_unit_id',
    ];

    protected $casts = [
        'quantity_required' => 'decimal:4',
    ];

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function groceryItem(): BelongsTo
    {
        return $this->belongsTo(GroceryItem::class);
    }

    public function measurementUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class);
    }
}
