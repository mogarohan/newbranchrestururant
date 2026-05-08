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
        Schema::table('restaurant_tables', function (Blueprint $table) {
            $table->softDeletes();
            $table->string('qr_token')->nullable()->change();
            $table->string('qr_path')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurant_tables', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->string('qr_token')->nullable(false)->change();
            $table->string('qr_path')->nullable(false)->change();
        });
    }
};
