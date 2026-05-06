<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class Restaurant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'logo_path',
        'user_limits',
        'is_active',
        'created_by',
        'has_branches',
        'max_branches',
        'upi_id', // 👈 ADD THIS
        'address',
        'phone_no',
        'is_pay_first',
        'gst_no',
        'table_limits',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // Automatically assign the authenticated user's ID to created_by
        static::creating(function (Model $restaurant) {
            if (Auth::check()) {
                $restaurant->created_by = Auth::id();
            }
        });

        // Cleanup: Delete the restaurant's directory when the record is deleted
        static::deleted(function (Model $restaurant) {
            if ($restaurant->slug) {
                Storage::disk('public')->deleteDirectory(
                    'restaurants/' . $restaurant->slug
                );
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function tables(): HasMany
    {
        return $this->hasMany(RestaurantTable::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}