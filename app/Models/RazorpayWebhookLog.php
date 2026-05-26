<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RazorpayWebhookLog extends Model
{
    protected $guarded = [];
    protected $casts = [
        'payload' => 'array',
    ];
}