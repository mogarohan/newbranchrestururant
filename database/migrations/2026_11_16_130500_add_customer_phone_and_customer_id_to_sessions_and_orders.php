<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. qr_sessions
        Schema::table('qr_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('qr_sessions', 'customer_phone')) {
                $table->string('customer_phone', 20)->nullable()->after('customer_name');
            }
            if (!Schema::hasColumn('qr_sessions', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->after('branch_id')->constrained('customers')->nullOnDelete();
            }
        });

        // 2. parcel_qr_sessions
        Schema::table('parcel_qr_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('parcel_qr_sessions', 'customer_phone')) {
                $table->string('customer_phone', 20)->nullable()->after('customer_name');
            }
            if (!Schema::hasColumn('parcel_qr_sessions', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->after('branch_id')->constrained('customers')->nullOnDelete();
            }
        });

        // 3. room_sessions
        Schema::table('room_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('room_sessions', 'customer_phone')) {
                $table->string('customer_phone', 20)->nullable()->after('guest_name');
            }
            if (!Schema::hasColumn('room_sessions', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->after('branch_id')->constrained('customers')->nullOnDelete();
            }
        });

        // 4. orders
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'customer_phone')) {
                $table->string('customer_phone', 20)->nullable()->after('customer_name');
            }
            if (!Schema::hasColumn('orders', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->after('branch_id')->constrained('customers')->nullOnDelete();
            }
        });

        // 5. Backfill historical unique customers from orders table
        try {
            $existingOrders = DB::table('orders')
                ->whereNotNull('customer_name')
                ->where('customer_name', '!=', '')
                ->select('restaurant_id', 'branch_id', 'customer_name', DB::raw('count(id) as total_orders'), DB::raw('sum(coalesce(confirmed_total, total_amount)) as total_sum'), DB::raw('max(created_at) as last_seen'))
                ->groupBy('restaurant_id', 'branch_id', 'customer_name')
                ->get();

            foreach ($existingOrders as $row) {
                $customerId = DB::table('customers')->insertGetId([
                    'restaurant_id' => $row->restaurant_id,
                    'branch_id' => $row->branch_id,
                    'name' => $row->customer_name,
                    'phone' => null,
                    'total_orders_count' => (int) $row->total_orders,
                    'total_spent' => (float) ($row->total_sum ?? 0),
                    'last_visit_at' => $row->last_seen ?? now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Link orders to this customer
                DB::table('orders')
                    ->where('restaurant_id', $row->restaurant_id)
                    ->where('customer_name', $row->customer_name)
                    ->whereNull('customer_id')
                    ->update(['customer_id' => $customerId]);
            }
        } catch (\Throwable $e) {
            // Silently continue if table empty or DB constraints differ
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'customer_id')) {
                $table->dropForeign(['customer_id']);
                $table->dropColumn('customer_id');
            }
            if (Schema::hasColumn('orders', 'customer_phone')) {
                $table->dropColumn('customer_phone');
            }
        });

        Schema::table('room_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('room_sessions', 'customer_id')) {
                $table->dropForeign(['customer_id']);
                $table->dropColumn('customer_id');
            }
            if (Schema::hasColumn('room_sessions', 'customer_phone')) {
                $table->dropColumn('customer_phone');
            }
        });

        Schema::table('parcel_qr_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('parcel_qr_sessions', 'customer_id')) {
                $table->dropForeign(['customer_id']);
                $table->dropColumn('customer_id');
            }
            if (Schema::hasColumn('parcel_qr_sessions', 'customer_phone')) {
                $table->dropColumn('customer_phone');
            }
        });

        Schema::table('qr_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('qr_sessions', 'customer_id')) {
                $table->dropForeign(['customer_id']);
                $table->dropColumn('customer_id');
            }
            if (Schema::hasColumn('qr_sessions', 'customer_phone')) {
                $table->dropColumn('customer_phone');
            }
        });
    }
};
