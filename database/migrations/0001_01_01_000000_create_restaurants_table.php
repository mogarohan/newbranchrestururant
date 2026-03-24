<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo_path')->nullable();

            $table->integer('user_limits')->default(1);
            $table->boolean('has_branches')->default(false);
            $table->integer('max_branches')->nullable();
            $table->string('upi_id')->nullable();
            
            $table->boolean('is_active')->default(true);

            // 👇 YAHAN FIX KIYA HAI: constrained('users') hata diya gaya hai deadlock todne ke liye
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};