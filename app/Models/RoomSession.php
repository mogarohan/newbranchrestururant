<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomSession extends Model
{
    protected $guarded = [];

    protected $casts = [
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'is_billed' => 'boolean',
    ];

    public function room() { return $this->belongsTo(Room::class); }
    public function orders() { return $this->hasMany(Order::class); }
}
