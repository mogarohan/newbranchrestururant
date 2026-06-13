<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffAttendance extends Model
{
    // role_id ko fillable mein daal diya
    protected $fillable = [
        'restaurant_id',
        'staff_id',
        'role_id',
        'date',
        'status',
        'overtime_hours',
        'manual_deduction',
        'manual_bonus',
    ];

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    // Role ki relation (Optional but helpful for Filament)
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}