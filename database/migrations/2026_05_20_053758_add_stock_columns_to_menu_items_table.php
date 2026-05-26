<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            // Check and add each column only if it doesn't already exist
            if (!Schema::hasColumn('menu_items', 'track_stock')) {
                $table->boolean('track_stock')->default(false)->after('type');
            }

            if (!Schema::hasColumn('menu_items', 'stock_quantity')) {
                $table->integer('stock_quantity')->nullable()->after('track_stock');
            }

            if (!Schema::hasColumn('menu_items', 'low_stock_threshold')) {
                $table->integer('low_stock_threshold')->default(5)->after('stock_quantity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('menu_items', 'track_stock')) {
                $columns[] = 'track_stock';
            }

            if (Schema::hasColumn('menu_items', 'stock_quantity')) {
                $columns[] = 'stock_quantity';
            }

            if (Schema::hasColumn('menu_items', 'low_stock_threshold')) {
                $columns[] = 'low_stock_threshold';
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};