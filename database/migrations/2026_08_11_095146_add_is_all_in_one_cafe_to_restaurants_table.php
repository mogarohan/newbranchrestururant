<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            // Naya All-in-One Dashboard ka column
            $table->boolean('is_all_in_one_cafe')->default(false)->after('has_detailed_inventory');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn('is_all_in_one_cafe');
        });
    }
};