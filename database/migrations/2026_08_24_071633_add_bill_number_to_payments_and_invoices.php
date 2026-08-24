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
    Schema::table('payments', function (Blueprint $table) {
        $table->string('bill_number')->nullable()->after('transaction_reference');
    });

    Schema::table('invoices', function (Blueprint $table) {
        $table->string('bill_number')->nullable()->after('invoice_number');
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments_and_invoices', function (Blueprint $table) {
            //
        });
    }
};
