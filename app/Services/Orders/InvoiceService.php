<?php

namespace App\Services\Orders;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\QrSession;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Exception;

class InvoiceService
{
    /**
     * 🔒 Atomic + Safe + Race-Proof Invoice Generation
     */
    public static function generateInvoice(QrSession $session, Payment $payment)
    {
        return DB::transaction(function () use ($session, $payment) {

            // ✅ Prevent duplicate invoice (Idempotency)
            if (Invoice::where('qr_session_id', $session->id)->exists()) {
                return Invoice::where('qr_session_id', $session->id)->first();
            }

            // 🛡️ DEFENSIVE FIX: Use raw foreign keys instead of relying on Eloquent relationships
            $restaurantId = $session->restaurant_id ?? $payment->restaurant_id;
            $branchId = $session->branch_id ?? $payment->branch_id;

            // Explicitly find the models to safely access GST and Naming data
            $restaurant = Restaurant::find($restaurantId);
            $branch = $branchId ? Branch::find($branchId) : null;

            if (!$restaurant) {
                throw new Exception("Critical Error: Restaurant data missing for this session.");
            }

            // 🔒 Lock existing invoices of this branch to prevent race conditions
            $lastSequence = Invoice::where('restaurant_id', $restaurantId)
                ->where('branch_id', $branchId)
                ->lockForUpdate()
                ->max('invoice_sequence');

            $nextSequence = ($lastSequence ?? 0) + 1;

            // Format: INV-2026-MAIN-000001
            $year = now()->year;
            $branchCode = $branch ? strtoupper(substr($branch->name, 0, 4)) : 'MAIN';
            $prefix = "INV-{$year}-{$branchCode}";
            $invoiceNumber = $prefix . '-' . str_pad($nextSequence, 6, '0', STR_PAD_LEFT);

            // 🛡️ DEFENSIVE FIX: Query orders explicitly in case $session->orders() relationship is missing
            $orders = Order::with('items.menuItem')
                ->where('qr_session_id', $session->id)
                ->whereNotIn('status', ['cancelled', 'rejected'])
                ->get();

            // 🧾 Snapshot all items (FINAL STATE)
            $items = $orders->flatMap(function ($order) {
                return $order->items->map(function ($item) {
                    return [
                        'item_id' => $item->menu_item_id,
                        'name' => $item->menuItem->name ?? $item->item_name ?? 'Unknown Item',
                        'qty' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        //'hsn_code' => $item->menuItem->hsn_code ?? null,
                        //'tax_rate' => $item->tax_rate ?? 0,
                        //'tax_amount' => $item->tax_amount ?? 0,
                        //'discount' => $item->discount_amount ?? 0,
                        'total' => $item->total_price,
                    ];
                });
            });

            try {
                return Invoice::create([
                    'restaurant_id'   => $restaurantId,
                    'branch_id'       => $branchId,
                    'qr_session_id'   => $session->id,
                    'payment_id'      => $payment->id,
                    'invoice_sequence'=> $nextSequence,
                    'invoice_prefix'  => $prefix,
                    'invoice_number'  => $invoiceNumber,
                    'invoice_date'    => now()->toDateString(),
                    'gstin'           => $restaurant->gst_no ?? null, 
                    'place_of_supply' => $restaurant->address ?? null, 
                    'customer_name'   => $session->customer_name ?? 'Customer',
                    'subtotal'        => $payment->subtotal,
                    'tax_amount'      => $payment->tax_amount ?? 0,
                    'discount_amount' => $payment->discount_amount ?? 0,
                    'extra_charges'   => $payment->extra_charges ?? 0,
                    'grand_total'     => $payment->amount,
                    'items_snapshot'  => $items->toArray(),
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                // Secondary fallback for race condition duplicate entry (1062)
                if ($e->errorInfo[1] == 1062) {
                    return Invoice::where('qr_session_id', $session->id)->firstOrFail();
                }
                throw $e;
            }
        });
    }
}