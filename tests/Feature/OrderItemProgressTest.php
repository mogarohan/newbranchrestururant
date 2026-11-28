<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\KitchenQueue;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\QrSession;
use App\Models\Category;
use App\Models\MenuItem;
use Illuminate\Support\Str;

class OrderItemProgressTest extends TestCase
{
    use RefreshDatabase;

    protected Restaurant $restaurant;
    protected RestaurantTable $table;
    protected QrSession $session;
    protected MenuItem $menuItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->restaurant = Restaurant::create([
            'name' => 'Test Restaurant',
            'slug' => 'test-restaurant-' . Str::random(6),
            'currency' => 'INR',
        ]);

        $this->table = RestaurantTable::create([
            'restaurant_id' => $this->restaurant->id,
            'table_number' => '101',
            'seating_capacity' => 4,
            'status' => 'occupied',
        ]);

        $this->session = QrSession::create([
            'session_token' => 'test-token-' . Str::random(8),
            'restaurant_id' => $this->restaurant->id,
            'restaurant_table_id' => $this->table->id,
            'status' => 'active',
            'is_active' => true,
            'expires_at' => now()->addHours(4),
        ]);

        $category = Category::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Main Course',
        ]);

        $this->menuItem = MenuItem::create([
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $category->id,
            'name' => 'Burger',
            'price' => 100,
            'type' => 'veg',
            'is_available' => true,
        ]);
    }

    protected function createTestOrder(array $itemStatuses = ['placed', 'placed', 'placed']): Order
    {
        $order = Order::create([
            'restaurant_id' => $this->restaurant->id,
            'restaurant_table_id' => $this->table->id,
            'qr_session_id' => $this->session->id,
            'status' => 'placed',
            'total_amount' => 300,
        ]);

        KitchenQueue::create([
            'order_id' => $order->id,
            'current_status' => 'placed',
            'priority' => 0,
        ]);

        foreach ($itemStatuses as $idx => $status) {
            $order->items()->create([
                'menu_item_id' => $this->menuItem->id,
                'item_name' => "Item " . ($idx + 1),
                'unit_price' => 100,
                'quantity' => 1,
                'total_price' => 100,
                'status' => $status,
            ]);
        }

        $order->syncStatusFromItems(true);

        return $order;
    }

    public function test_order_item_defaults_to_placed(): void
    {
        $order = $this->createTestOrder(['placed']);
        $item = $order->items->first();

        $this->assertEquals('placed', $item->status);
        $this->assertTrue($item->isPlaced());
        $this->assertFalse($item->isPreparing());
        $this->assertFalse($item->isReady());
    }

    public function test_starting_one_item_moves_order_to_preparing(): void
    {
        $order = $this->createTestOrder(['placed', 'placed', 'placed']);
        $this->assertEquals('placed', $order->status);

        $item1 = $order->items[0];
        $item1->update(['status' => 'preparing']);

        $order->syncStatusFromItems(true);

        $this->assertEquals('preparing', $order->fresh()->status);
        $this->assertEquals('preparing', KitchenQueue::where('order_id', $order->id)->value('current_status'));
    }

    public function test_order_stays_preparing_when_some_items_ready_and_some_placed(): void
    {
        $order = $this->createTestOrder(['placed', 'placed', 'placed']);

        // Item 1 is ready, Item 2 is preparing, Item 3 is placed
        $order->items[0]->update(['status' => 'ready']);
        $order->items[1]->update(['status' => 'preparing']);

        $order->syncStatusFromItems(true);

        $this->assertEquals('preparing', $order->fresh()->status);
        $this->assertEquals('preparing', KitchenQueue::where('order_id', $order->id)->value('current_status'));
    }

    public function test_order_becomes_ready_only_when_all_items_ready(): void
    {
        $order = $this->createTestOrder(['placed', 'placed', 'placed']);

        foreach ($order->items as $item) {
            $item->update(['status' => 'ready']);
        }

        $order->syncStatusFromItems(true);

        $this->assertEquals('ready', $order->fresh()->status);
        $this->assertEquals('ready', KitchenQueue::where('order_id', $order->id)->value('current_status'));
    }

    public function test_undo_item_from_ready_reverts_order_to_preparing(): void
    {
        $order = $this->createTestOrder(['ready', 'ready', 'ready']);
        $order->syncStatusFromItems(true);
        $this->assertEquals('ready', $order->fresh()->status);

        // Chef undoes Item 1 back to preparing
        $order->items[0]->update(['status' => 'preparing']);
        $order->syncStatusFromItems(true);

        $this->assertEquals('preparing', $order->fresh()->status);
        $this->assertEquals('preparing', KitchenQueue::where('order_id', $order->id)->value('current_status'));
    }

    public function test_set_all_items_status(): void
    {
        $order = $this->createTestOrder(['placed', 'placed']);

        $order->setAllItemsStatus('preparing');
        $this->assertEquals('preparing', $order->fresh()->status);
        $this->assertTrue($order->fresh(['items'])->items->every(fn($i) => $i->status === 'preparing'));

        $order->setAllItemsStatus('ready');
        $this->assertEquals('ready', $order->fresh()->status);
        $this->assertTrue($order->fresh(['items'])->items->every(fn($i) => $i->status === 'ready'));
    }

    public function test_zero_quantity_items_do_not_block_ready_status(): void
    {
        $order = $this->createTestOrder(['ready', 'ready']);

        // Add a 0-quantity item (e.g. cancelled / out of stock)
        $order->items()->create([
            'menu_item_id' => $this->menuItem->id,
            'item_name' => "Out of Stock Item",
            'unit_price' => 50,
            'quantity' => 0,
            'total_price' => 0,
            'status' => 'placed',
        ]);

        $order->syncStatusFromItems(true);

        // Should still resolve to ready because only quantity > 0 items are active!
        $this->assertEquals('ready', $order->fresh()->status);
    }

    public function test_manager_dashboard_can_update_item_status(): void
    {
        $role = \App\Models\Role::create(['name' => 'manager', 'label' => 'Manager']);
        $user = \App\Models\User::create([
            'restaurant_id' => $this->restaurant->id,
            'role_id' => $role->id,
            'name' => 'Manager John',
            'email' => 'manager@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($user);

        $order = $this->createTestOrder(['placed', 'placed']);
        $item1 = $order->items[0];

        $page = new \App\Filament\Pages\ManagerDashboard();
        $page->updateItemStatus($item1->id, 'preparing');

        $this->assertEquals('preparing', $item1->fresh()->status);
        $this->assertEquals('preparing', $order->fresh()->status);

        $page->updateItemStatus($item1->id, 'ready');
        $this->assertEquals('ready', $item1->fresh()->status);
        $this->assertEquals('preparing', $order->fresh()->status);

        $item2 = $order->items[1];
        $page->updateItemStatus($item2->id, 'ready');
        $this->assertEquals('ready', $item2->fresh()->status);
        $this->assertEquals('ready', $order->fresh()->status);
    }

    public function test_manager_dashboard_can_update_order_status(): void
    {
        $role = \App\Models\Role::create(['name' => 'manager', 'label' => 'Manager']);
        $user = \App\Models\User::create([
            'restaurant_id' => $this->restaurant->id,
            'role_id' => $role->id,
            'name' => 'Manager John 2',
            'email' => 'manager2@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($user);

        $order = $this->createTestOrder(['placed', 'placed']);

        $page = new \App\Filament\Pages\ManagerDashboard();

        // 1. Mark preparing
        $page->updateStatus($order->id, 'preparing');
        $this->assertEquals('preparing', $order->fresh()->status);
        $this->assertTrue($order->fresh(['items'])->items->every(fn($i) => $i->status === 'preparing'));

        // 2. Mark ready
        $page->updateStatus($order->id, 'ready');
        $this->assertEquals('ready', $order->fresh()->status);
        $this->assertTrue($order->fresh(['items'])->items->every(fn($i) => $i->status === 'ready'));

        // 3. Mark served
        $page->updateStatus($order->id, 'served');
        $this->assertEquals('served', $order->fresh()->status);
        $this->assertTrue($order->fresh(['items'])->items->every(fn($i) => $i->status === 'served'));
        $this->assertNull(KitchenQueue::where('order_id', $order->id)->first());
    }

    public function test_manager_actions_are_idempotent_and_prevent_duplicate_reactions(): void
    {
        $role = \App\Models\Role::firstOrCreate(['name' => 'manager'], ['label' => 'Manager']);
        $user = \App\Models\User::create([
            'restaurant_id' => $this->restaurant->id,
            'role_id' => $role->id,
            'name' => 'Manager Idempotent',
            'email' => 'idempotent@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($user);

        // Enable stock tracking for menu item
        $this->menuItem->update(['track_stock' => true, 'stock_quantity' => 20]);

        $order = $this->createTestOrder(['placed']);
        $page = new \App\Filament\Pages\ManagerDashboard();

        // Initial acceptance: stock decreases by 1 (20 -> 19)
        $page->updateStatus($order->id, 'accepted');
        $this->assertEquals('accepted', $order->fresh()->status);
        $this->assertEquals(19, $this->menuItem->fresh()->stock_quantity);
        $initialLogsCount = \App\Models\OrderStatusLog::where('order_id', $order->id)->count();
        $this->assertEquals(1, $initialLogsCount);

        // Second duplicate acceptance: stock should NOT be decremented again, log should NOT duplicate
        $page->updateStatus($order->id, 'accepted');
        $this->assertEquals('accepted', $order->fresh()->status);
        $this->assertEquals(19, $this->menuItem->fresh()->stock_quantity);
        $this->assertEquals($initialLogsCount, \App\Models\OrderStatusLog::where('order_id', $order->id)->count());

        // Item status duplicate update test
        $item = $order->items->first();
        $page->updateItemStatus($item->id, 'preparing');
        $this->assertEquals('preparing', $item->fresh()->status);

        $logsAfterPreparing = \App\Models\OrderStatusLog::where('order_id', $order->id)->count();

        // Duplicate item status update with same status: no-op
        $page->updateItemStatus($item->id, 'preparing');
        $this->assertEquals('preparing', $item->fresh()->status);
        $this->assertEquals($logsAfterPreparing, \App\Models\OrderStatusLog::where('order_id', $order->id)->count());
    }

    public function test_waiter_dashboard_handles_partial_ready_and_item_serving(): void
    {
        $role = \App\Models\Role::firstOrCreate(['name' => 'waiter'], ['label' => 'Waiter']);
        $waiter = \App\Models\User::create([
            'restaurant_id' => $this->restaurant->id,
            'role_id' => $role->id,
            'name' => 'Waiter Sam',
            'email' => 'waitersam@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($waiter);

        // Order with 2 items: 1 ready, 1 still preparing
        $order = $this->createTestOrder(['ready', 'preparing']);
        $this->assertEquals('preparing', $order->fresh()->status);

        $waiterPage = new \App\Filament\Pages\WaiterDashboard();

        // 1. Ready orders property should find this order because 1 item is ready!
        $readyOrders = $waiterPage->getReadyOrdersProperty();
        $this->assertTrue($readyOrders->contains('id', $order->id));

        $item1 = $order->items->where('status', 'ready')->first();
        $item2 = $order->items->where('status', 'preparing')->first();

        // 2. Deliver only Item 1
        $waiterPage->markItemAsServed($item1->id);
        $this->assertEquals('served', $item1->fresh()->status);
        $this->assertEquals('preparing', $item2->fresh()->status);

        // Order should still be preparing because item 2 is not served yet
        $this->assertEquals('preparing', $order->fresh()->status);

        // 3. Since no items are ready anymore (1 served, 1 preparing), order should NOT be in readyOrders
        $this->assertFalse($waiterPage->getReadyOrdersProperty()->contains('id', $order->id));

        // 4. Kitchen marks Item 2 as ready
        $item2->update(['status' => 'ready']);
        $order->syncStatusFromItems(true);

        // Now order appears again in readyOrders!
        $this->assertTrue($waiterPage->getReadyOrdersProperty()->contains('id', $order->id));

        // 5. Deliver Item 2
        $waiterPage->markItemAsServed($item2->id);
        $this->assertEquals('served', $item2->fresh()->status);

        // Now all items are served, so the order status automatically becomes served!
        $this->assertEquals('served', $order->fresh()->status);
    }

    public function test_waiter_dashboard_blade_view_renders_successfully(): void
    {
        $role = \App\Models\Role::firstOrCreate(['name' => 'waiter'], ['label' => 'Waiter']);
        $waiter = \App\Models\User::create([
            'restaurant_id' => $this->restaurant->id,
            'role_id' => $role->id,
            'name' => 'Waiter Blade Test',
            'email' => 'waiterblade@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($waiter);

        $order = $this->createTestOrder(['ready', 'preparing', 'served']);

        \Livewire\Livewire::test(\App\Filament\Pages\WaiterDashboard::class)
            ->assertSuccessful()
            ->assertSee('Ready to Serve')
            ->assertSee('PARTIAL READY');
    }

    public function test_kitchen_display_board_item_status_dispatches_order_status_updated_event(): void
    {
        \Illuminate\Support\Facades\Event::fake([\App\Events\OrderStatusUpdated::class]);

        $role = \App\Models\Role::firstOrCreate(['name' => 'chef'], ['label' => 'Chef']);
        $chef = \App\Models\User::create([
            'restaurant_id' => $this->restaurant->id,
            'role_id' => $role->id,
            'name' => 'Chef Pierre',
            'email' => 'chefpierre@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($chef);

        // Order already preparing
        $order = $this->createTestOrder(['preparing', 'preparing']);
        $item1 = $order->items->first();

        $kds = new \App\Filament\Pages\KitchenDisplayBoard();
        $kds->updateItemStatus($item1->id, 'ready');

        $this->assertEquals('ready', $item1->fresh()->status);
        // Overall order is still preparing
        $this->assertEquals('preparing', $order->fresh()->status);

        // OrderStatusUpdated event MUST have been dispatched so waiters get the ready alert!
        \Illuminate\Support\Facades\Event::assertDispatched(\App\Events\OrderStatusUpdated::class, function ($event) use ($order) {
            return $event->orderPayload['id'] === $order->id;
        });
    }

    public function test_kitchen_display_board_displays_served_badge_when_item_served_by_waiter(): void
    {
        $role = \App\Models\Role::firstOrCreate(['name' => 'chef'], ['label' => 'Chef']);
        $chef = \App\Models\User::create([
            'restaurant_id' => $this->restaurant->id,
            'role_id' => $role->id,
            'name' => 'Chef Luigi',
            'email' => 'chefluigi@test.com',
            'password' => bcrypt('password'),
        ]);

        // Order has 2 items: 1 served, 1 cooking
        $order = $this->createTestOrder(['served', 'preparing']);
        $order->syncStatusFromItems(true);

        \Livewire\Livewire::actingAs($chef)
            ->test(\App\Filament\Pages\KitchenDisplayBoard::class)
            ->assertSee('SERVED')
            ->assertSee('1 Served')
            ->assertDontSee('WAITING');
    }
}
