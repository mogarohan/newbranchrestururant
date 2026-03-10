<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Scopes\RestaurantScope;
use Laravel\Sanctum\HasApiTokens;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    
    use HasFactory, Notifiable, HasApiTokens;
    protected $fillable = [
        'restaurant_id',
        'role_id',
        'name',
        'email',
        'is_super_admin',
        'password',
        'is_active',
    ];

     protected $casts = [
        'is_super_admin' => 'boolean',
        'is_active' => 'boolean',
    ];
    protected $hidden = ['password'];

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_super_admin
            || $this->role?->name === 'restaurant_admin'
            || $this->role?->name === 'manager'
            || $this->role?->name === 'waiter'
            || $this->role?->name === 'chef';
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
    
    /* ---------- ROLE CHECKS ---------- */

    // public function isSuperAdmin() { return $this->role->name === RoleEnum::SUPER_ADMIN->value; }
    // public function isRestaurantAdmin() { return $this->role->name === RoleEnum::RESTAURANT_ADMIN->value; }
    // public function isManager() { return $this->role->name === RoleEnum::MANAGER->value; }
    // public function isChef() { return $this->role->name === RoleEnum::CHEF->value; }
    // public function isWaiter() { return $this->role->name === RoleEnum::WAITER->value; }

     public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    public function isRestaurantAdmin(): bool
    {
        return $this->role?->name === 'restaurant_admin';
    }

    public function isManager(): bool
    {
        return $this->role?->name === 'manager';
    }
}

