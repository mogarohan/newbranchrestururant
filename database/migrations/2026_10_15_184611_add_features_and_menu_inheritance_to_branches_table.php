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
        Schema::table('branches', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('name');
            $table->string('gst_no', 20)->nullable()->after('phone');
            $table->integer('table_limits')->default(0)->after('is_active');
            $table->boolean('has_inventory')->default(false)->after('table_limits');
            $table->boolean('has_detailed_inventory')->default(false)->after('has_inventory');
            $table->boolean('is_rooms_facility')->default(false)->after('has_detailed_inventory');
            $table->integer('rooms_limit')->default(0)->after('is_rooms_facility');
            $table->boolean('has_attendance')->default(false)->after('rooms_limit');
            $table->boolean('is_all_in_one_cafe')->default(false)->after('has_attendance');
            $table->boolean('is_pay_first')->default(false)->after('is_all_in_one_cafe');
            $table->boolean('inherit_restaurant_menu')->default(true)->after('is_pay_first');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn([
                'logo_path',
                'gst_no',
                'table_limits',
                'has_inventory',
                'has_detailed_inventory',
                'is_rooms_facility',
                'rooms_limit',
                'has_attendance',
                'is_all_in_one_cafe',
                'is_pay_first',
                'inherit_restaurant_menu',
            ]);
        });
    }
};
