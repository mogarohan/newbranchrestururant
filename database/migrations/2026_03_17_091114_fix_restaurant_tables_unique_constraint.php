<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // 👈 DB import zaroori hai

return new class extends Migration {
    public function up(): void
    {
        // 👇 Foreign Key constraints ko temporary off karo
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Schema::table('restaurant_tables', function (Blueprint $table) {
            // 1. Purana unique index drop karo
            // Humne index name wahi use kiya hai jo error message mein aaya hai
            $table->dropUnique('restaurant_tables_restaurant_id_table_number_unique');

            // 2. Naya unique index lagao (Restaurant + Branch + Table No)
            $table->unique(['restaurant_id', 'branch_id', 'table_number'], 'res_branch_table_unique');
        });

        // 👇 Foreign Key constraints ko wapas ON karo
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down(): void
    {
        Schema::table('restaurant_tables', function (Blueprint $table) {
            $table->dropUnique('res_branch_table_unique');
            $table->unique(['restaurant_id', 'table_number']);
        });
    }
};