<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('menu_item_id')->constrained('menu_items')->cascadeOnDelete();
            $table->foreignId('grocery_item_id')->constrained('grocery_items')->restrictOnDelete();
            $table->decimal('quantity_required', 10, 4);
            $table->foreignId('measurement_unit_id')->constrained('measurement_units')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['menu_item_id', 'grocery_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
