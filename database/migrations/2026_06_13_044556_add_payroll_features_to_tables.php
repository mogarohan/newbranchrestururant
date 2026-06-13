<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        // 1. User table me Duty Hours (Shift) add kar rahe hain
        Schema::table('users', function (Blueprint $table) {
            // Default 8 ghante ki shift
            $table->integer('shift_hours')->default(8)->after('monthly_salary');
        });

        // 2. Attendance table me OT, Bonus, Deduction add kar rahe hain
        Schema::table('staff_attendances', function (Blueprint $table) {
            $table->decimal('overtime_hours', 5, 2)->default(0)->after('status');
            $table->decimal('manual_deduction', 10, 2)->default(0)->after('overtime_hours');
            $table->decimal('manual_bonus', 10, 2)->default(0)->after('manual_deduction');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('shift_hours');
        });
        Schema::table('staff_attendances', function (Blueprint $table) {
            $table->dropColumn(['overtime_hours', 'manual_deduction', 'manual_bonus']);
        });
    }
};