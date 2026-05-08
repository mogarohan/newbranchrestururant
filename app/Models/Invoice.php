<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Exception;

class Invoice extends Model
{
    protected $fillable = [
        'restaurant_id', 'branch_id', 'qr_session_id', 'payment_id',
        'invoice_sequence', 'invoice_prefix', 'invoice_number', 'invoice_date',
        'gstin', 'place_of_supply', 'customer_name', 
        'subtotal', 'tax_amount', 'discount_amount', 'extra_charges', 
        'grand_total', 'items_snapshot'
    ];

    protected $casts = [
        'items_snapshot' => 'array',
        'invoice_date' => 'date',
    ];

    // 🔒 IMMUTABILITY: Non-Negotiable
    protected static function booted()
    {
        static::updating(function ($invoice) {
            throw new Exception("Compliance Error: Invoice cannot be modified.");
        });

        static::deleting(function ($invoice) {
            throw new Exception("Compliance Error: Invoice cannot be deleted.");
        });
    }

    public function restaurant(): BelongsTo { return $this->belongsTo(Restaurant::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function qrSession(): BelongsTo { return $this->belongsTo(QrSession::class, 'qr_session_id'); }
    public function payment(): BelongsTo { return $this->belongsTo(Payment::class); }
}