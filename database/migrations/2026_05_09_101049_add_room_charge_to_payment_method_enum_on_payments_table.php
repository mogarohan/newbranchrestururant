<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. CLEANUP: Ensure no rows have a value that will break the ENUM change.
        // If row 4 has a value not in the list, this sets it to 'pending' first.
        DB::table('payments')
            ->whereNotIn('payment_method', ['cash', 'online', 'card', 'pending'])
            ->update(['payment_method' => 'pending']);

        // 2. ALTER: Modify the ENUM to include 'room_charge'
        // Using raw SQL is necessary for ENUM changes in MySQL
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('cash', 'online', 'card', 'pending', 'room_charge') DEFAULT 'pending'");
    }

    public function down(): void
    {
        // Change 'room_charge' back to 'pending' before reverting the column definition
        DB::table('payments')
            ->where('payment_method', 'room_charge')
            ->update(['payment_method' => 'pending']);

        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('cash', 'online', 'card', 'pending') DEFAULT 'pending'");
    }
};