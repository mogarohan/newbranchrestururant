<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('parcel_qr_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->uuid('qr_token')->unique();
            $table->string('qr_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
            
            // Ensure a restaurant can't have duplicate counter names
            $table->unique(['restaurant_id', 'branch_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parcel_qr_codes');
    }
};