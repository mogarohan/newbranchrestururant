<?php

namespace App\Services;

use App\Models\GroceryItem;
use App\Models\InventoryTransaction;
use App\Models\MenuItem;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Deducts grocery items for an accepted order.
     * For each OrderItem → load MenuItem recipes → multiply qty × quantity_required
     * → decrement GroceryItem::current_stock → log InventoryTransaction.
     *
     * Returns array of stock warning messages (empty if all OK).
     */
    public static function deductForOrder(Order $order): array
    {
        $warnings = [];

        $order->loadMissing('items');

        foreach ($order->items as $orderItem) {
            if (!$orderItem->menu_item_id) continue;

            $menuItem = MenuItem::with('recipes.groceryItem')->find($orderItem->menu_item_id);
            if (!$menuItem) continue;

            // If no recipes defined, skip (fallback to old stock_quantity is handled by caller)
            if ($menuItem->recipes->isEmpty()) continue;

            $orderedQty = (int) ($orderItem->confirmed_qty ?? $orderItem->quantity);
            if ($orderedQty <= 0) continue;

            foreach ($menuItem->recipes as $recipe) {
                $groceryItem = GroceryItem::where('id', $recipe->grocery_item_id)->lockForUpdate()->first();
                if (!$groceryItem) continue;

                $requiredAmount = $recipe->quantity_required * $orderedQty;
                $available = (float) $groceryItem->current_stock;

                if ($available < $requiredAmount) {
                    $warnings[] = "{$groceryItem->name}: needed {$requiredAmount} " . ($recipe->measurementUnit->short_name ?? '') . ", only {$available} available.";
                    // Deduct whatever is available
                    $deductAmount = max(0, $available);
                } else {
                    $deductAmount = $requiredAmount;
                }

                if ($deductAmount > 0) {
                    $groceryItem->decrement('current_stock', $deductAmount);

                    InventoryTransaction::create([
                        'restaurant_id' => $order->restaurant_id,
                        'branch_id' => $order->branch_id,
                        'grocery_item_id' => $groceryItem->id,
                        'type' => 'order_fulfillment',
                        'quantity' => $deductAmount,
                        'reference_type' => Order::class,
                        'reference_id' => $order->id,
                        'notes' => "Order #{$order->id}: {$orderedQty}x {$menuItem->name} → {$deductAmount} " . ($recipe->measurementUnit->short_name ?? '') . " of {$groceryItem->name}",
                        'performed_by' => auth()->id(),
                    ]);
                }
            }
        }

        return $warnings;
    }

    /**
     * Reverses a deduction for a cancelled order.
     * Looks up inventory_transactions for this order and restores stock.
     */
    public static function restoreForOrder(Order $order): void
    {
        $transactions = InventoryTransaction::where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->where('type', 'order_fulfillment')
            ->whereNull('deleted_at')
            ->get();

        foreach ($transactions as $transaction) {
            $groceryItem = GroceryItem::find($transaction->grocery_item_id);
            if (!$groceryItem) continue;

            $groceryItem->increment('current_stock', $transaction->quantity);

            InventoryTransaction::create([
                'restaurant_id' => $order->restaurant_id,
                'branch_id' => $order->branch_id,
                'grocery_item_id' => $groceryItem->id,
                'type' => 'order_cancellation',
                'quantity' => $transaction->quantity,
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'notes' => "Cancelled Order #{$order->id}: restored {$transaction->quantity} of {$groceryItem->name}",
                'performed_by' => auth()->id(),
            ]);
        }
    }

    /**
     * Pre-check: Can we fulfill this order with available grocery stock?
     * Returns array of insufficient ingredient messages, or empty array if OK.
     */
    public static function checkAvailability(Order $order): array
    {
        $issues = [];
        $order->loadMissing('items');

        foreach ($order->items as $orderItem) {
            if (!$orderItem->menu_item_id) continue;

            $menuItem = MenuItem::with('recipes.groceryItem', 'recipes.measurementUnit')->find($orderItem->menu_item_id);
            if (!$menuItem || $menuItem->recipes->isEmpty()) continue;

            $orderedQty = (int) $orderItem->quantity;

            foreach ($menuItem->recipes as $recipe) {
                $required = $recipe->quantity_required * $orderedQty;
                $available = (float) ($recipe->groceryItem->current_stock ?? 0);
                $unitName = $recipe->measurementUnit->short_name ?? '';

                if ($available < $required) {
                    $issues[] = "{$recipe->groceryItem->name}: need {$required} {$unitName}, only {$available} available (for {$menuItem->name})";
                }
            }
        }

        return $issues;
    }

    /**
     * Manual stock addition from Filament UI.
     */
    public static function addStock(GroceryItem $item, float $qty, ?int $userId = null, ?string $notes = null): void
    {
        $item->increment('current_stock', $qty);

        InventoryTransaction::create([
            'restaurant_id' => $item->restaurant_id,
            'branch_id' => $item->branch_id,
            'grocery_item_id' => $item->id,
            'type' => 'addition',
            'quantity' => $qty,
            'notes' => $notes ?? 'Manual stock addition',
            'performed_by' => $userId ?? auth()->id(),
        ]);
    }

    /**
     * Manual spoilage recording from Filament UI.
     */
    public static function recordSpoilage(GroceryItem $item, float $qty, ?int $userId = null, ?string $notes = null): void
    {
        $deductAmount = min($qty, (float) $item->current_stock);
        if ($deductAmount > 0) {
            $item->decrement('current_stock', $deductAmount);
        }

        InventoryTransaction::create([
            'restaurant_id' => $item->restaurant_id,
            'branch_id' => $item->branch_id,
            'grocery_item_id' => $item->id,
            'type' => 'spoilage',
            'quantity' => $qty,
            'notes' => $notes ?? 'Spoilage recorded',
            'performed_by' => $userId ?? auth()->id(),
        ]);
    }

    /**
     * Check if a given MenuItem has recipes defined (used for fallback logic).
     */
    public static function hasRecipes(MenuItem $menuItem): bool
    {
        return $menuItem->recipes()->exists();
    }
}
