<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            // Core Gateway Fields
            $table->string('gateway')->default('razorpay')->after('payment_method');
            $table->string('gateway_order_id')->nullable()->unique()->after('gateway');
            $table->string('gateway_payment_id')->nullable()->unique()->after('gateway_order_id');
            $table->string('gateway_signature')->nullable()->after('gateway_payment_id');
            $table->string('gateway_status')->default('pending')->after('gateway_signature');

            // Money handling (Paise is Source of Truth)
            $table->unsignedBigInteger('amount_paise')->default(0)->after('amount');

            // Timestamps
            $table->timestamp('expires_at')->nullable()->after('created_at');
            $table->timestamp('verified_at')->nullable();

            // Operational Tracking
            $table->unsignedInteger('attempts')->default(0);
        });
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'gateway',
                'gateway_order_id',
                'gateway_payment_id',
                'gateway_signature',
                'gateway_status',
                'amount_paise',
                'expires_at',
                'verified_at',
                'attempts'
            ]);
        });
    }
};