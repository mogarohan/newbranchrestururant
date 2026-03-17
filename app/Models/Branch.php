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
        'is_active'
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }
}