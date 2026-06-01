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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('parcel_qr_session_id')->nullable()->after('room_session_id')->constrained()->nullOnDelete();
            $table->enum('service_type', ['dine_in', 'room_service', 'parcel'])->default('dine_in')->after('parcel_qr_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['parcel_qr_session_id']);
            $table->dropColumn(['parcel_qr_session_id', 'service_type']);
        });
    }
};
