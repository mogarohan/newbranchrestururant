<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_status_logs', function (Blueprint $table) {
            $table->id();

            // Changed to cascadeOnDelete to prevent constraint errors if an order is deleted
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();

            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('changed_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_logs');
    }
};