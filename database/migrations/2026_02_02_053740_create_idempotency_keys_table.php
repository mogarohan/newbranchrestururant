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
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key', 150);   // ✅ explicit length (was default 255 -> combined index exceeded 1000 bytes on utf8mb4)
            $table->string('scope', 50);  // ✅ explicit length
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('status');
            $table->timestamps();

            $table->unique(['key', 'scope']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};