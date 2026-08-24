<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'restaurant_id',
        'branch_id',
        'amount',
        'amount_paise',        // ← add
        'bill_number',
        'payment_method',
        'status',
        'transaction_reference',
        'paid_at',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'extra_charges',
        'gateway',             // ← add
        'gateway_order_id',    // ← add
        'gateway_payment_id',  // ← add
        'gateway_signature',   // ← add
        'gateway_status',      // ← add
        'expires_at',          // ← add
        'verified_at',         // ← add
        'attempts',            // ← add
    ];
    // DELETE the $guarded = []; line entirely

    public const STATUS_PENDING = 'pending';
    public const STATUS_INITIATED = 'initiated';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';

    //protected $guarded = [];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}