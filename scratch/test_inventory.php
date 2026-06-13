<?php

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Branch;
use App\Models\GroceryItem;
use App\Models\MeasurementUnit;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Recipe;
use App\Models\Restaurant;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;

DB::beginTransaction();

try {
    echo "Running Detailed Inventory verification...\n";

    // 1. Create a restaurant with detailed inventory enabled
    $restaurant = Restaurant::create([
        'name' => 'Pizza Palace Verification',
        'slug' => 'pizza-palace-verification',
        'has_detailed_inventory' => true,
    ]);

    $branch = Branch::create([
        'restaurant_id' => $restaurant->id,
        'name' => 'Main Branch Verification',
    ]);

    // 2. Create measurement unit
    $unit = MeasurementUnit::create([
        'restaurant_id' => $restaurant->id,
        'name' => 'Gram',
        'short_name' => 'g',
        'conversion_factor' => 1.0,
    ]);

    // 3. Create grocery item (raw material)
    $cheese = GroceryItem::create([
        'restaurant_id' => $restaurant->id,
        'branch_id' => $branch->id,
        'measurement_unit_id' => $unit->id,
        'name' => 'Cheese',
        'current_stock' => 1000.0,
        'low_stock_threshold' => 100.0,
    ]);

    // 3.5 Create Category
    $category = \App\Models\Category::create([
        'restaurant_id' => $restaurant->id,
        'name' => 'Main Category',
    ]);

    // 4. Create menu item
    $pizza = MenuItem::create([
        'restaurant_id' => $restaurant->id,
        'branch_id' => $branch->id,
        'category_id' => $category->id,
        'name' => 'Cheese Pizza',
        'price' => 12.99,
        'is_available' => true,
    ]);

    // 5. Define Recipe: 1 Cheese Pizza requires 150g of Cheese
    Recipe::create([
        'menu_item_id' => $pizza->id,
        'grocery_item_id' => $cheese->id,
        'quantity_required' => 150.0,
        'measurement_unit_id' => $unit->id,
    ]);

    // 6. Place an Order for 2 Cheese Pizzas
    $order = Order::create([
        'restaurant_id' => $restaurant->id,
        'branch_id' => $branch->id,
        'total_amount' => 25.98,
        'status' => 'pending',
    ]);

    $orderItem = OrderItem::create([
        'order_id' => $order->id,
        'menu_item_id' => $pizza->id,
        'item_name' => 'Cheese Pizza',
        'quantity' => 2,
        'unit_price' => 12.99,
        'total_price' => 25.98,
    ]);

    // Verify pre-check
    $availabilityWarnings = InventoryService::checkAvailability($order);
    if (!empty($availabilityWarnings)) {
        throw new Exception("Availability check failed: " . implode(', ', $availabilityWarnings));
    }
    echo "✓ Availability check passed\n";

    // 7. Deduct stock for the order
    $warnings = InventoryService::deductForOrder($order);
    if (!empty($warnings)) {
        throw new Exception("Deduct for order warnings: " . implode(', ', $warnings));
    }

    // Check that stock was reduced: 1000 - (150 * 2) = 700
    $cheese->refresh();
    if ($cheese->current_stock !== 700.0 && (float)$cheese->current_stock !== 700.0) {
        throw new Exception("Stock not reduced correctly: expected 700, got " . $cheese->current_stock);
    }
    echo "✓ Stock deduction verified (stock reduced to {$cheese->current_stock})\n";

    // Verify transaction logged
    $hasTransaction = DB::table('inventory_transactions')->where([
        'restaurant_id' => $restaurant->id,
        'grocery_item_id' => $cheese->id,
        'type' => 'order_fulfillment',
        'quantity' => 300.0,
    ])->exists();
    if (!$hasTransaction) {
        throw new Exception("Order fulfillment transaction not found in DB");
    }
    echo "✓ Transaction log verified\n";

    // 8. Restore stock for the order (e.g. on cancellation)
    InventoryService::restoreForOrder($order);

    $cheese->refresh();
    if ($cheese->current_stock !== 1000.0 && (float)$cheese->current_stock !== 1000.0) {
        throw new Exception("Stock not restored correctly: expected 1000, got " . $cheese->current_stock);
    }
    echo "✓ Stock restoration verified (stock restored to {$cheese->current_stock})\n";

    // Verify restore transaction logged
    $hasRestoreTransaction = DB::table('inventory_transactions')->where([
        'restaurant_id' => $restaurant->id,
        'grocery_item_id' => $cheese->id,
        'type' => 'order_cancellation',
        'quantity' => 300.0,
    ])->exists();
    if (!$hasRestoreTransaction) {
        throw new Exception("Order cancellation transaction not found in DB");
    }
    echo "✓ Cancellation transaction log verified\n";

    echo "Detailed Inventory module verification successful!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
} finally {
    DB::rollBack();
}
