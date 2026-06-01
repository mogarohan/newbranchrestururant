<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParcelQrSession extends Model
{
    use SoftDeletes;

    protected $fillable = ['restaurant_id', 'branch_id', 'parcel_qr_code_id', 'customer_name', 'session_token', 'status', 'last_activity_at'];
    protected $casts = ['last_activity_at' => 'datetime'];

    public function parcelQrCode() { return $this->belongsTo(ParcelQrCode::class); }
    public function restaurant() { return $this->belongsTo(Restaurant::class); }
    public function orders() { return $this->hasMany(Order::class); }
}