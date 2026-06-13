<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('grocery_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('restaurant_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->foreignId('measurement_unit_id')->constrained('measurement_units')->restrictOnDelete();
            $table->decimal('current_stock', 12, 4)->default(0);
            $table->decimal('low_stock_threshold', 12, 4)->default(0);
            $table->decimal('cost_per_unit', 10, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->unique(['restaurant_id', 'branch_id', 'name'], 'grocery_items_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grocery_items');
    }
};
