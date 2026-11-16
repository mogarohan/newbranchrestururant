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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('name');
            $table->string('phone', 20)->nullable()->index();
            $table->unsignedInteger('total_orders_count')->default(0);
            $table->decimal('total_spent', 10, 2)->default(0.00);
            $table->timestamp('last_visit_at')->nullable();
            $table->timestamps();

            $table->index(['restaurant_id', 'phone']);
            $table->index(['restaurant_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
