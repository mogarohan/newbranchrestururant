<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Restaurant;
use App\Models\Branch;
use App\Models\RestaurantTable;
use App\Models\ParcelQrCode;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\User;
use App\Models\Role;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Invoice;
use App\Filament\Pages\ManagerDashboard;
use Livewire\Livewire;
use Illuminate\Support\Str;

class InstantBillTest extends TestCase
{
    use RefreshDatabase;

    protected Restaurant $restaurant;
    protected RestaurantTable $table;
    protected ParcelQrCode $parcelCounter;
    protected Category $category;
    protected MenuItem $menuItem1;
    protected MenuItem $menuItem2;
    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->restaurant = Restaurant::create([
            'name' => 'Instant Dine Bistro',
            'slug' => 'instant-dine-' . Str::random(6),
            'currency' => 'INR',
            'gstin_number' => '24AAAAA0000A1Z5',
            'address' => 'Floor 1, Food Street, Ahmedabad',
            'phone' => '9876543210',
            'upi_id' => 'bistro@upi',
        ]);

        $role = Role::create([
            'name' => 'manager',
            'label' => 'Manager',
        ]);

        $this->manager = User::create([
            'restaurant_id' => $this->restaurant->id,
            'role_id' => $role->id,
            'name' => 'Test Manager',
            'email' => 'manager-' . Str::random(6) . '@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->table = RestaurantTable::create([
            'restaurant_id' => $this->restaurant->id,
            'table_number' => '12',
            'seating_capacity' => 4,
            'status' => 'available',
        ]);

        $this->parcelCounter = ParcelQrCode::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Takeaway Express Counter',
            'qr_token' => 'TAKEAWAY-01',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Fast Food',
        ]);

        $this->menuItem1 = MenuItem::create([
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $this->category->id,
            'name' => 'Cheese Pizza',
            'price' => 250.00,
            'track_stock' => true,
            'stock_quantity' => 10,
            'is_available' => true,
            'type' => 'veg',
        ]);

        $this->menuItem2 = MenuItem::create([
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $this->category->id,
            'name' => 'Cold Coffee',
            'price' => 80.00,
            'track_stock' => true,
            'stock_quantity' => 15,
            'is_available' => true,
            'type' => 'veg',
        ]);
    }

    public function test_can_open_instant_billing_mode_with_defaults(): void
    {
        Livewire::actingAs($this->manager)
            ->test(ManagerDashboard::class)
            ->call('openInstantBilling', $this->table->id)
            ->assertSet('isInstantBillMode', true)
            ->assertSet('isPlaceOrderOpen', true)
            ->assertSet('placeOrderTableId', $this->table->id)
            ->assertSet('placeOrderCustomerName', 'Walk-in Guest')
            ->assertSet('placeOrderPaymentMethod', 'cash');
    }

    public function test_submit_instant_bill_creates_settled_order_payment_and_tax_invoice(): void
    {
        Livewire::actingAs($this->manager)
            ->test(ManagerDashboard::class)
            ->call('openInstantBilling', $this->table->id)
            ->set('placeOrderCustomerName', 'Rajesh Sharma')
            ->set('placeOrderCustomerPhone', '9898000000')
            ->set('placeOrderPaymentMethod', 'upi')
            ->set('instantBillDiscount', 30.00) // 330 - 30 = 300 taxable
            ->set('instantBillTaxPercent', 5.00) // 5% of 300 = 15.00
            ->set('instantBillExtraCharges', 10.00) // 300 + 15 + 10 = 325.00
            ->set('instantBillIncludeQr', true)
            ->set('instantBillPaperSize', '80mm')
            ->call('addToCart', $this->menuItem1->id) // 250
            ->call('addToCart', $this->menuItem2->id) // 80 -> Subtotal 330
            ->call('submitInstantBill')
            ->assertHasNoErrors()
            ->assertSet('isPlaceOrderOpen', false);

        // Verify Order
        $order = Order::where('customer_name', 'Rajesh Sharma')->latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals('served', $order->status);
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals('9898000000', $order->customer_phone);
        $this->assertEquals('dine_in', $order->service_type);
        $this->assertEquals(325.00, (float) $order->total_amount);
        $this->assertCount(2, $order->items);

        foreach ($order->items as $item) {
            $this->assertEquals('served', $item->status);
        }

        // Verify stock decremented
        $this->assertEquals(9, $this->menuItem1->fresh()->stock_quantity);
        $this->assertEquals(14, $this->menuItem2->fresh()->stock_quantity);

        // Verify Payment with discount, tax, charges breakdown
        $payment = Payment::where('order_id', $order->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('paid', $payment->status);
        $this->assertEquals('upi', $payment->payment_method);
        $this->assertEquals(330.00, (float) $payment->subtotal);
        $this->assertEquals(30.00, (float) $payment->discount_amount);
        $this->assertEquals(15.00, (float) $payment->tax_amount);
        $this->assertEquals(10.00, (float) $payment->extra_charges);
        $this->assertEquals(325.00, (float) $payment->amount);
        $this->assertNotNull($payment->bill_number);
        $this->assertStringStartsWith('B-', $payment->bill_number);

        // Verify Tax Invoice
        $invoice = Invoice::where('qr_session_id', $order->qr_session_id)->first();
        $this->assertNotNull($invoice);
        $this->assertNotNull($invoice->invoice_number);
        $this->assertEquals('Rajesh Sharma', $invoice->customer_name);
        $this->assertEquals('9898000000', $invoice->customer_phone);
        $this->assertEquals($this->restaurant->gstin_number, $invoice->gstin);
        $this->assertEquals(330.00, (float) $invoice->subtotal);
        $this->assertEquals(30.00, (float) $invoice->discount_amount);
        $this->assertEquals(15.00, (float) $invoice->tax_amount);
        $this->assertEquals(10.00, (float) $invoice->extra_charges);
        $this->assertEquals(325.00, (float) $invoice->grand_total);
    }

    public function test_submit_instant_bill_for_parcel_takeaway(): void
    {
        Livewire::actingAs($this->manager)
            ->test(ManagerDashboard::class)
            ->call('openInstantBilling', null, $this->parcelCounter->id)
            ->set('placeOrderServiceType', 'parcel')
            ->set('placeOrderCustomerName', 'Priya Patel')
            ->set('placeOrderCustomerPhone', '9123456789')
            ->set('placeOrderPaymentMethod', 'card')
            ->call('addToCart', $this->menuItem1->id) // 250
            ->call('submitInstantBill')
            ->assertHasNoErrors()
            ->assertSet('isPlaceOrderOpen', false);

        // Verify Order
        $order = Order::where('customer_name', 'Priya Patel')->latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals('served', $order->status);
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals('parcel', $order->service_type);
        $this->assertNotNull($order->parcel_qr_session_id);

        // Verify Invoice
        $invoice = Invoice::where('parcel_qr_session_id', $order->parcel_qr_session_id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('Priya Patel', $invoice->customer_name);
        $this->assertEquals('9123456789', $invoice->customer_phone);
    }
}
