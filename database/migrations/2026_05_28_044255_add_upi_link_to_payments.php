<?php
// database/migrations/2026_05_28_000001_add_upi_link_fields_to_payments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Stores the Razorpay Payment Link ID (plink_xxxxxxxxxx)
            // so the webhook can look up which local payment to mark as paid
            $table->string('gateway_payment_link_id')->nullable()->after('gateway_signature');

            // Distinguish standard checkout ("online") from UPI link ("upi_direct")
            // payment_method column already exists — just documenting new possible value here
            // $table->string('payment_method') already exists
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('gateway_payment_link_id');
        });
    }
};