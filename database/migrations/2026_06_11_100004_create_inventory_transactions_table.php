<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('restaurant_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->foreignId('grocery_item_id')->constrained('grocery_items')->restrictOnDelete();
            $table->enum('type', ['addition', 'deduction', 'spoilage', 'order_fulfillment', 'order_cancellation']);
            $table->decimal('quantity', 12, 4);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('performed_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['grocery_item_id', 'type']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
