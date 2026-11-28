<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\User;
use App\Models\Role;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\QrSession;
use App\Services\Orders\InvoiceService;
use App\Filament\Pages\ManagerDashboard;
use Livewire\Livewire;
use Illuminate\Support\Str;

class BillItemMergeTest extends TestCase
{
    use RefreshDatabase;

    protected Restaurant $restaurant;
    protected RestaurantTable $table;
    protected Category $category;
    protected MenuItem $menuItem1;
    protected MenuItem $menuItem2;
    protected User $manager;
    protected QrSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->restaurant = Restaurant::create([
            'name' => 'Taste Of India',
            'slug' => 'taste-india-' . Str::random(6),
            'currency' => 'INR',
            'gstin_number' => '24ABCDE1234F1Z5',
            'address' => 'Station Road, Surat',
            'phone' => '9825000000',
            'upi_id' => 'taste@upi',
        ]);

        $role = Role::create([
            'name' => 'manager',
            'label' => 'Manager',
        ]);

        $this->manager = User::create([
            'restaurant_id' => $this->restaurant->id,
            'role_id' => $role->id,
            'name' => 'Floor Manager',
            'email' => 'mgr-' . Str::random(6) . '@test.com',
            'password' => bcrypt('secret'),
        ]);

        $this->table = RestaurantTable::create([
            'restaurant_id' => $this->restaurant->id,
            'table_number' => 'T-04',
            'seating_capacity' => 4,
            'status' => 'occupied',
        ]);

        $this->session = QrSession::create([
            'restaurant_id' => $this->restaurant->id,
            'restaurant_table_id' => $this->table->id,
            'session_token' => Str::random(32),
            'customer_name' => 'Ramesh Patel',
            'is_active' => true,
            'is_primary' => true,
            'expires_at' => now()->addHours(4),
        ]);

        $this->category = Category::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Main Course',
        ]);

        $this->menuItem1 = MenuItem::create([
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $this->category->id,
            'name' => 'Paneer Butter Masala',
            'price' => 250.00,
            'is_available' => true,
        ]);

        $this->menuItem2 = MenuItem::create([
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $this->category->id,
            'name' => 'Butter Naan',
            'price' => 40.00,
            'is_available' => true,
        ]);
    }

    public function test_print_pending_bill_merges_duplicate_items_and_calculates_total_qty(): void
    {
        $this->actingAs($this->manager);

        // Round 1: 1x Paneer Butter Masala + 2x Butter Naan
        $order1 = Order::create([
            'restaurant_id' => $this->restaurant->id,
            'restaurant_table_id' => $this->table->id,
            'qr_session_id' => $this->session->id,
            'customer_name' => 'Ramesh Patel',
            'status' => 'served',
            'service_type' => 'dine_in',
            'total_amount' => 330.00,
            'payment_status' => 'pending',
        ]);
        OrderItem::create([
            'order_id' => $order1->id,
            'menu_item_id' => $this->menuItem1->id,
            'item_name' => 'Paneer Butter Masala',
            'unit_price' => 250.00,
            'quantity' => 1,
            'confirmed_qty' => 1,
            'total_price' => 250.00,
            'status' => 'served',
        ]);
        OrderItem::create([
            'order_id' => $order1->id,
            'menu_item_id' => $this->menuItem2->id,
            'item_name' => 'Butter Naan',
            'unit_price' => 40.00,
            'quantity' => 2,
            'confirmed_qty' => 2,
            'total_price' => 80.00,
            'status' => 'served',
        ]);

        // Round 2: 2x Butter Naan
        $order2 = Order::create([
            'restaurant_id' => $this->restaurant->id,
            'restaurant_table_id' => $this->table->id,
            'qr_session_id' => $this->session->id,
            'customer_name' => 'Ramesh Patel',
            'status' => 'served',
            'service_type' => 'dine_in',
            'total_amount' => 80.00,
            'payment_status' => 'pending',
        ]);
        OrderItem::create([
            'order_id' => $order2->id,
            'menu_item_id' => $this->menuItem2->id,
            'item_name' => 'Butter Naan',
            'unit_price' => 40.00,
            'quantity' => 2,
            'confirmed_qty' => 2,
            'total_price' => 80.00,
            'status' => 'served',
        ]);

        // Round 3: 1x Paneer Butter Masala + 1x Butter Naan
        $order3 = Order::create([
            'restaurant_id' => $this->restaurant->id,
            'restaurant_table_id' => $this->table->id,
            'qr_session_id' => $this->session->id,
            'customer_name' => 'Ramesh Patel',
            'status' => 'served',
            'service_type' => 'dine_in',
            'total_amount' => 290.00,
            'payment_status' => 'pending',
        ]);
        OrderItem::create([
            'order_id' => $order3->id,
            'menu_item_id' => $this->menuItem1->id,
            'item_name' => 'Paneer Butter Masala',
            'unit_price' => 250.00,
            'quantity' => 1,
            'confirmed_qty' => 1,
            'total_price' => 250.00,
            'status' => 'served',
        ]);
        OrderItem::create([
            'order_id' => $order3->id,
            'menu_item_id' => $this->menuItem2->id,
            'item_name' => 'Butter Naan',
            'unit_price' => 40.00,
            'quantity' => 1,
            'confirmed_qty' => 1,
            'total_price' => 40.00,
            'status' => 'served',
        ]);

        // Total expected:
        // Paneer Butter Masala: 1 + 1 = 2 qty (Rs 500)
        // Butter Naan: 2 + 2 + 1 = 5 qty (Rs 200)
        // Total Qty: 2 + 5 = 7 items

        $component = Livewire::test(ManagerDashboard::class)
            ->call('openTable', $this->table->id);

        $res = $component->call('printPendingBill');
        $script = $res->effects['xjs'][0]['expression'] ?? '';

        // The JavaScript popup output should have:
        // 1. Merged Qty for Butter Naan (5)
        // 2. Merged Qty for Paneer Butter Masala (2)
        // 3. TOTAL QTY: 7
        $this->assertStringContainsString('TOTAL QTY', $script);
        $this->assertStringContainsString('Total Qty: 7', $script);
        $this->assertStringContainsString('2 Items', $script);

        // Check occurrence count of Butter Naan in the table: should only appear ONCE in tbody!
        $this->assertEquals(1, substr_count($script, '>Butter Naan<'));
        $this->assertEquals(1, substr_count($script, '>Paneer Butter Masala<'));

        // The JavaScript popup output should have:
        // 1. Merged Qty for Butter Naan (5)
        // 2. Merged Qty for Paneer Butter Masala (2)
        // 3. TOTAL QTY: 7
        $this->assertStringContainsString('TOTAL QTY', $script);
        $this->assertStringContainsString('Total Qty: 7', $script);
        $this->assertStringContainsString('2 Items', $script);

        // Check occurrence count of Butter Naan in the table: should only appear ONCE in tbody!
        $this->assertEquals(1, substr_count($script, '>Butter Naan<'));
        $this->assertEquals(1, substr_count($script, '>Paneer Butter Masala<'));
    }

    public function test_invoice_service_merges_items_in_snapshot(): void
    {
        $order = Order::create([
            'restaurant_id' => $this->restaurant->id,
            'restaurant_table_id' => $this->table->id,
            'qr_session_id' => $this->session->id,
            'customer_name' => 'Ramesh Patel',
            'status' => 'served',
            'service_type' => 'dine_in',
            'total_amount' => 580.00,
            'payment_status' => 'pending',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'menu_item_id' => $this->menuItem2->id,
            'item_name' => 'Butter Naan',
            'unit_price' => 40.00,
            'quantity' => 2,
            'total_price' => 80.00,
            'status' => 'served',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'menu_item_id' => $this->menuItem2->id,
            'item_name' => 'Butter Naan',
            'unit_price' => 40.00,
            'quantity' => 3,
            'total_price' => 120.00,
            'status' => 'served',
        ]);

        $payment = Payment::create([
            'restaurant_id' => $this->restaurant->id,
            'order_id' => $order->id,
            'qr_session_id' => $this->session->id,
            'bill_number' => 'BILL-001',
            'subtotal' => 200.00,
            'amount' => 200.00,
            'status' => 'paid',
            'payment_method' => 'cash',
        ]);

        $invoice = InvoiceService::generateInvoice($this->session, $payment);

        $this->assertNotNull($invoice);
        $snapshot = $invoice->items_snapshot;

        // Butter Naan was ordered in 2 entries (2 + 3 = 5), so snapshot must have exactly 1 merged entry with qty = 5
        $this->assertCount(1, $snapshot);
        $this->assertEquals('Butter Naan', $snapshot[0]['name']);
        $this->assertEquals(5, $snapshot[0]['qty']);
        $this->assertEquals(200.00, $snapshot[0]['total']);
    }
}
