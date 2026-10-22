<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'restaurant_id',
        'branch_id',
        'name',
        'logo_path',
        'phone',
        'gst_no',
        'address',
        'is_active',
        'table_limits',
        'has_inventory',
        'has_detailed_inventory',
        'is_rooms_facility',
        'rooms_limit',
        'has_attendance',
        'is_all_in_one_cafe',
        'is_pay_first',
        'inherit_restaurant_menu',
        'upi_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'table_limits' => 'integer',
        'has_inventory' => 'boolean',
        'has_detailed_inventory' => 'boolean',
        'is_rooms_facility' => 'boolean',
        'rooms_limit' => 'integer',
        'has_attendance' => 'boolean',
        'is_all_in_one_cafe' => 'boolean',
        'is_pay_first' => 'boolean',
        'inherit_restaurant_menu' => 'boolean',
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