<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->boolean('has_attendance')->default(false)->after('has_detailed_inventory');
        });
    }
    public function down()
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn('has_attendance');
        });
    }
};
