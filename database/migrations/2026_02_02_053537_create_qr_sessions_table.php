<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('qr_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->foreignId('restaurant_table_id')->constrained('restaurant_tables')->cascadeOnDelete();

            $table->string('session_token')->unique();
            $table->string('customer_name')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('host_session_id')->nullable()->constrained('qr_sessions')->cascadeOnDelete();

            $table->enum('join_status', [
                'active',      // Primary active
                'pending',     // Waiting for approval
                'approved',    // Approved by primary
                'rejected'     // Rejected by primary
            ])->default('active');

            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_sessions');
    }
};