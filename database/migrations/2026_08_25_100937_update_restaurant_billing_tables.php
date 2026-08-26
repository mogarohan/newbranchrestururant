<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update Room Sessions Table
        Schema::table('room_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('room_sessions', 'is_billed')) {
                $table->boolean('is_billed')->default(false)->after('status');
            }
        });

        // 2. Update Invoices Table
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'room_session_id')) {
                $table->foreignId('room_session_id')->nullable()->after('qr_session_id')->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('invoices', 'parcel_qr_session_id')) {
                $table->foreignId('parcel_qr_session_id')->nullable()->after('qr_session_id')->constrained()->nullOnDelete();
            }
        });

        // 3. Update Payments Table (Make order_id nullable for empty room checkout)
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'order_id')) {
                $table->unsignedBigInteger('order_id')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('room_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('room_sessions', 'is_billed')) {
                $table->dropColumn('is_billed');
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'room_session_id')) {
                $table->dropForeign(['room_session_id']);
                $table->dropColumn('room_session_id');
            }
            if (Schema::hasColumn('invoices', 'parcel_qr_session_id')) {
                $table->dropForeign(['parcel_qr_session_id']);
                $table->dropColumn('parcel_qr_session_id');
            }
        });
    }
};