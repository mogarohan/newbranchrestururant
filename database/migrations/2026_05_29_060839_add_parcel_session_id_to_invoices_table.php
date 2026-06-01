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
        Schema::table('invoices', function (Blueprint $table) {
            // 1. Make the original table session ID nullable
            $table->unsignedBigInteger('qr_session_id')->nullable()->change();

            // 2. Add the new room and parcel session IDs
            $table->foreignId('room_session_id')->nullable()->after('qr_session_id')->constrained()->nullOnDelete();
            $table->foreignId('parcel_qr_session_id')->nullable()->after('room_session_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['room_session_id']);
            $table->dropForeign(['parcel_qr_session_id']);
            $table->dropColumn(['room_session_id', 'parcel_qr_session_id']);
            
            // Note: Reverting back to non-nullable might fail if there are null records
            $table->unsignedBigInteger('qr_session_id')->nullable(false)->change();
        });
    }
};
