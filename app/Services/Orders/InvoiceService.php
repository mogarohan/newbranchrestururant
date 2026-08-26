<?php

namespace App\Services\Orders;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Exception;

class InvoiceService
{
    /**
     * 🔒 Atomic + Safe + Race-Proof Invoice Generation for Table, Parcel & Room
     */
    public static function generateInvoice($session, Payment $payment, $specificOrders = null)
    {
        return DB::transaction(function () use ($session, $payment, $specificOrders) {

            // Determine session type
            $sessionColumn = 'qr_session_id';
            if ($session instanceof \App\Models\ParcelQrSession) {
                $sessionColumn = 'parcel_qr_session_id';
            } elseif ($session instanceof \App\Models\RoomSession) {
                $sessionColumn = 'room_session_id';
            }

            // 🌟 FIX: Checked by PAYMENT_ID instead of SESSION to allow Multiple Invoices per Session
            $existingInvoice = Invoice::where('payment_id', $payment->id)->first();
            if ($existingInvoice) {
                return $existingInvoice;
            }

            $restaurantId = $session->restaurant_id ?? $payment->restaurant_id;
            $branchId = $session->branch_id ?? $payment->branch_id;

            $restaurant = Restaurant::find($restaurantId);
            $branch = $branchId ? Branch::find($branchId) : null;

            if (!$restaurant) {
                throw new Exception("Critical Error: Restaurant data missing for this session.");
            }

            $lastSequence = Invoice::where('restaurant_id', $restaurantId)
                ->where('branch_id', $branchId)
                ->lockForUpdate()
                ->max('invoice_sequence');

            $nextSequence = ($lastSequence ?? 0) + 1;

            $year = now()->timezone('Asia/Kolkata')->year;
            $branchCode = $branch ? strtoupper(substr($branch->name, 0, 4)) : 'MAIN';
            $prefix = "INV-{$year}-{$branchCode}";
            $invoiceNumber = $prefix . '-' . str_pad($nextSequence, 6, '0', STR_PAD_LEFT);

            // Fetch explicitly passed unpaid orders, or fallback
            $orders = $specificOrders ?: Order::with('items.menuItem')
                ->where($sessionColumn, $session->id)
                ->whereNotIn('status', ['cancelled', 'rejected'])
                ->get();

            $items = $orders->flatMap(function ($order) {
                return $order->items->map(function ($item) {
                    return [
                        'item_id' => $item->menu_item_id,
                        'name' => $item->menuItem->name ?? $item->item_name ?? 'Unknown Item',
                        'qty' => $item->confirmed_qty ?? $item->quantity,
                        'unit_price' => $item->unit_price,
                        'total' => $item->unit_price * ($item->confirmed_qty ?? $item->quantity),
                    ];
                });
            });

            try {
                $invoiceData = [
                    'restaurant_id'    => $restaurantId,
                    'branch_id'        => $branchId,
                    'qr_session_id'    => ($sessionColumn === 'qr_session_id') ? $session->id : null,
                    'parcel_qr_session_id' => ($sessionColumn === 'parcel_qr_session_id') ? $session->id : null,
                    'room_session_id'  => ($sessionColumn === 'room_session_id') ? $session->id : null,
                    'payment_id'       => $payment->id,
                    'invoice_sequence' => $nextSequence,
                    'invoice_prefix'   => $prefix,
                    'invoice_number'   => $invoiceNumber,
                    'bill_number'      => $payment->bill_number,
                    'invoice_date'     => now()->timezone('Asia/Kolkata')->toDateString(),
                    'gstin'            => $restaurant->gst_no ?? null,
                    'place_of_supply'  => $restaurant->address ?? null,
                    'customer_name'    => $session->customer_name ?? $session->guest_name ?? 'Guest',
                    'subtotal'         => $payment->subtotal,
                    'tax_amount'       => $payment->tax_amount ?? 0,
                    'discount_amount'  => $payment->discount_amount ?? 0,
                    'extra_charges'    => $payment->extra_charges ?? 0,
                    'grand_total'      => $payment->amount,
                    'items_snapshot'   => $items->toArray(),
                ];

                return Invoice::create($invoiceData);
            } catch (\Illuminate\Database\QueryException $e) {
                if ($e->errorInfo[1] == 1062) {
                    return Invoice::where('payment_id', $payment->id)->firstOrFail();
                }
                throw $e;
            }
        });
    }
}