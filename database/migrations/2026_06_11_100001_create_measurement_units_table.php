<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('measurement_units', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('restaurant_id')->constrained()->restrictOnDelete();
            $table->string('name');           // e.g. "Kilogram"
            $table->string('short_name');     // e.g. "kg"
            $table->string('base_unit')->nullable(); // e.g. "gram"
            $table->decimal('conversion_factor', 12, 4)->default(1); // e.g. 1000
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['restaurant_id', 'short_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('measurement_units');
    }
};
