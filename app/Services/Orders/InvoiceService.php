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
    public static function generateInvoice( $session, Payment $payment)
    {
        return DB::transaction(function () use ($session, $payment) {

            $isParcel = ($session instanceof \App\Models\ParcelQrSession) || isset($session->parcel_qr_code_id);
            $isRoom = ($session instanceof \App\Models\RoomSession) || isset($session->room_id);
            $sessionCol = $isParcel ? 'parcel_qr_session_id' : ($isRoom ? 'room_session_id' : 'qr_session_id');

            // ✅ Prevent duplicate invoice (Idempotency)
            if (Invoice::where($sessionCol, $session->id)->exists()) {
                return Invoice::where($sessionCol, $session->id)->first();
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
                ->where($sessionCol, $session->id)
                ->whereNotIn('status', ['cancelled', 'rejected'])
                ->get();

            // 🧾 Snapshot all items (FINAL STATE) - Merged by item and unit price
            $mergedItems = [];
            foreach ($orders as $order) {
                foreach ($order->items as $item) {
                    $qty = (int) ($item->confirmed_qty ?? $item->quantity);
                    if ($qty <= 0) continue;
                    $unitPrice = (float) $item->unit_price;
                    $name = $item->menuItem->name ?? $item->item_name ?? 'Unknown Item';
                    $key = ($item->menu_item_id ? ('menu_' . $item->menu_item_id) : ('name_' . md5($name))) . '_' . number_format($unitPrice, 2, '.', '');

                    if (!isset($mergedItems[$key])) {
                        $mergedItems[$key] = [
                            'item_id' => $item->menu_item_id,
                            'name' => $name,
                            'qty' => 0,
                            'unit_price' => $unitPrice,
                            'total' => 0,
                        ];
                    }
                    $mergedItems[$key]['qty'] += $qty;
                    $mergedItems[$key]['total'] += ($qty * $unitPrice);
                }
            }
            $items = array_values($mergedItems);

            try {
                return Invoice::create([
                    'restaurant_id'   => $restaurantId,
                    'branch_id'       => $branchId,
                    'qr_session_id'   => ($sessionCol === 'qr_session_id') ? $session->id : null,
                    'parcel_qr_session_id' => ($sessionCol === 'parcel_qr_session_id') ? $session->id : null,
                    'room_session_id' => ($sessionCol === 'room_session_id') ? $session->id : null,
                    'payment_id'      => $payment->id,
                    'invoice_sequence'=> $nextSequence,
                    'invoice_prefix'  => $prefix,
                    'invoice_number'  => $invoiceNumber,
                    'bill_number'     => $payment->bill_number, // 🌟 Passing Temporary Bill Number to Invoice
                    'invoice_date'    => now()->toDateString(),
                    'gstin'           => ($branch && $branch->gst_no) ? $branch->gst_no : ($restaurant->gst_no ?? null), 
                    'place_of_supply' => ($branch && $branch->address) ? $branch->address : ($restaurant->address ?? null), 
                    'customer_name'   => $session->customer_name ?? 'Customer',
                    'customer_phone'  => $session->customer_phone ?? null,
                    'subtotal'        => $payment->subtotal,
                    'tax_amount'      => $payment->tax_amount ?? 0,
                    'discount_amount' => $payment->discount_amount ?? 0,
                    'extra_charges'   => $payment->extra_charges ?? 0,
                    'grand_total'     => $payment->amount,
                    'items_snapshot'  => is_array($items) ? $items : $items->toArray(),
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                // Secondary fallback for race condition duplicate entry (1062)
                if ($e->errorInfo[1] == 1062) {
                    return Invoice::where($sessionCol, $session->id)->firstOrFail();
                }
                throw $e;
            }
        });
    }
}