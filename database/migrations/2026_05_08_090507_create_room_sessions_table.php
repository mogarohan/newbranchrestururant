<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('room_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            
            $table->string('guest_name');
            $table->uuid('session_token')->unique();
            
            $table->timestamp('check_in_at')->nullable();
            $table->timestamp('check_out_at')->nullable();
            
            $table->enum('status', ['active', 'checked_out', 'expired', 'cancelled'])->default('active');
            $table->boolean('is_billed')->default(false);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_sessions');
    }
};
