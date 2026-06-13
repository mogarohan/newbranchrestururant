<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('staff_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();

            // Staff ID (users table se linked)
            $table->foreignId('staff_id')->constrained('users')->cascadeOnDelete();

            // 🌟 ROLE ID ADDED (roles table se linked) 🌟
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();

            $table->date('date');
            $table->enum('status', ['pending', 'present', 'absent', 'half_day'])->default('pending');
            $table->timestamps();

            // Ek staff ki ek din mein ek hi entry hogi
            $table->unique(['staff_id', 'date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('staff_attendances');
    }
};