<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            if (!Schema::hasColumn('restaurants', 'has_detailed_inventory')) {
                $table->boolean('has_detailed_inventory')->default(false)->after('has_inventory');
            }
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            if (Schema::hasColumn('restaurants', 'has_detailed_inventory')) {
                $table->dropColumn('has_detailed_inventory');
            }
        });
    }
};
