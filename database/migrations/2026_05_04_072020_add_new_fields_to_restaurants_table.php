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
        Schema::table('restaurants', function (Blueprint $table) {
            $table->boolean('is_pay_first')->default(false)->after('phone_no'); // Adjust 'after' as needed
            $table->string('gst_no')->nullable()->after('is_pay_first');
            $table->integer('table_limits')->default(0)->after('gst_no');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['is_pay_first', 'gst_no', 'table_limits']);
        });
    }
};
