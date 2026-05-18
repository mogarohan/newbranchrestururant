<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Make table_id and qr_session_id nullable because Rooms won't have them
            $table->unsignedBigInteger('restaurant_table_id')->nullable()->change();
            $table->unsignedBigInteger('qr_session_id')->nullable()->change();
            
            // Just in case room_session_id isn't in your DB yet, let's add it safely
            if (!Schema::hasColumn('orders', 'room_session_id')) {
                $table->foreignId('room_session_id')->nullable()->constrained()->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Revert back if needed (Note: This might fail if data already exists with nulls)
            $table->unsignedBigInteger('restaurant_table_id')->nullable(false)->change();
            $table->unsignedBigInteger('qr_session_id')->nullable(false)->change();
        });
    }
};