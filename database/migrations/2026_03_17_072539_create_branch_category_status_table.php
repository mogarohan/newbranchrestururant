<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('branch_category_status', function (Blueprint $table) {
            $table->id();
            // Kaunsi branch hai
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            // Kaunsi category hai
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            // Branch ke liye status kya hai
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_category_status');
    }
};