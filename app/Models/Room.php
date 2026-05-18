<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes; // 👈 1. ADD THIS IMPORT

class Room extends Model
{
    use SoftDeletes; // 👈 2. ADD THIS TRAIT
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($room) {
            if (empty($room->qr_token)) {
                $room->qr_token = Str::uuid()->toString();
            }
        });
    }

    public function restaurant() 
    { return $this->belongsTo(Restaurant::class); }

    public function activeSession() 
    { return $this->belongsTo(RoomSession::class, 'active_room_session_id'); }

    public function sessions() 
    { return $this->hasMany(RoomSession::class); }
}