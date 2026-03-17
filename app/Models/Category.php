<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB; // 👈 Import DB for cleanup

class Category extends Model
{
    protected $fillable = ['restaurant_id', 'name', 'sort_order', 'is_active', 'branch_id'];

    /**
     * The "booted" method of the model.
     * 👇 Jab category delete hogi, yeh automatic cleanup karega
     */
    protected static function booted()
    {
        static::deleting(function ($category) {
            // 1. Category delete hone se pehle uske saare Menu Items delete kar do
            // Yeh wahi SQL Integrity error fix karega jo aapko pehle aaya tha
            $category->menuItems()->delete();

            // 2. Branch overrides (toggles) saaf kar do
            DB::table('branch_category_status')
                ->where('category_id', $category->id)
                ->delete();
        });
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    // Branch ki relationship (Agar dashboard me source dikhana ho)
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }
}