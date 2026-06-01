<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'restaurant_id',
        'branch_id',
        'name',
        'phone',
        'address',
        'is_active',
        'upi_id', // 👈 ADD THIS
    ];

    // public function restaurant()
    // {
    //     return $this->belongsTo(Restaurant::class);
    // }
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    // 👇 Ensure you have this one too! 👇
    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id');
    }
}