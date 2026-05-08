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
       Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            
            // 🔒 One session = one invoice (critical)
            $table->foreignId('qr_session_id')->unique()->constrained('qr_sessions')->restrictOnDelete();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();

            // 🔢 Sequential numbering (Index-backed)
            $table->unsignedBigInteger('invoice_sequence');
            $table->string('invoice_prefix')->default('INV');
            $table->string('invoice_number'); // e.g., INV-2026-AMD-000001
            
            // Legal Compliance (GST / Regional)
            $table->date('invoice_date');
            $table->string('gstin')->nullable();
            $table->string('place_of_supply')->nullable();
            
            // Customer Details
            $table->string('customer_name')->nullable();

            // Financials
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('extra_charges', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2)->default(0);

            // 🧾 Snapshot (VERY IMPORTANT)
            $table->json('items_snapshot'); 
            
            $table->timestamps();

            // 🔥 Unique per branch sequence
            $table->unique(['restaurant_id', 'branch_id', 'invoice_sequence']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
