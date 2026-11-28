<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('status')->default('placed')->after('notes');
            $table->index(['order_id', 'status']);
        });

        // Backfill existing order items based on their parent order's status
        try {
            DB::statement("
                UPDATE order_items 
                INNER JOIN orders ON order_items.order_id = orders.id 
                SET order_items.status = CASE 
                    WHEN orders.status IN ('ready', 'served', 'completed') THEN orders.status
                    WHEN orders.status = 'preparing' THEN 'preparing'
                    ELSE 'placed'
                END
            ");
        } catch (\Exception $e) {
            // Fallback for database engines that do not support multi-table UPDATE
            $orders = DB::table('orders')->select('id', 'status')->get();
            foreach ($orders as $order) {
                $itemStatus = in_array($order->status, ['ready', 'served', 'completed', 'preparing']) 
                    ? $order->status 
                    : 'placed';
                DB::table('order_items')->where('order_id', $order->id)->update(['status' => $itemStatus]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['order_id', 'status']);
            $table->dropColumn('status');
        });
    }
};
