<?php

namespace App\Filament\Pages;

use App\Models\RestaurantTable;
use App\Models\Room;
use App\Models\Order;
use App\Models\KitchenQueue;
use App\Models\OrderStatusLog;
use App\Models\QrSession;
use App\Models\RoomSession;
use App\Models\ParcelQrSession;
use App\Models\ParcelQrCode;
use App\Models\MenuItem;
use App\Models\Category;
use Filament\Pages\Page;
use App\Models\ActivityLog;
use App\Events\OrderStatusUpdated;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\InventoryService;

class WaiterDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';
    protected static string $view = 'filament.pages.waiter-dashboard';
    protected static ?string $navigationLabel = 'Waiter Station';
    protected static ?string $title = 'Waiter Service Station';
    protected static ?int $navigationSort = 1;

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return '';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->restaurant_id !== null
            && in_array($user->role->name ?? '', ['waiter']);
    }

    // Active Navigation Tab: 'ready_orders', 'tables'
    public $currentTab = 'ready_orders';

    // Table Filter: 'all', 'occupied', 'available', 'cleaning'
    public $tableFilter = 'all';
    public $searchQuery = '';

    // Active Assistance & Buzzer Alerts
    public $activeAlerts = [];

    // Detailed Table Drawer
    public $viewingTableId = null;

    // Fast Table-side Place Order / KOT modal (Stitch POS Screen)
    public $isPlaceOrderOpen = false;
    public $placeOrderTableId = null;
    public $placeOrderCounterId = null;
    public $placeOrderRoomId = null;
    public $placeOrderSessionId = null;
    public $placeOrderCustomerName = 'Guest';
    public $placeOrderPaymentStatus = 'pending';
    public $placeOrderNotes = '';
    public $placeOrderPriority = 'normal';
    public $placeOrderCategory = 'all';
    public $placeOrderSearch = '';
    public $placeOrderVegFilter = 'all';
    public $placeOrderAutoPrint = true;
    public $placeOrderCart = [];

    public function mount(): void
    {
        $this->currentTab = 'ready_orders';
    }

    public function getListeners(): array
    {
        $restaurantId = auth()->user()->restaurant_id;

        return [
            "echo-private:restaurant.{$restaurantId},.OrderStatusUpdated" => 'handleOrderStatusUpdated',
            "echo-private:restaurant.{$restaurantId},.OrderCancelled" => 'handleOrderCancelled',
            "echo-private:restaurant.{$restaurantId}.alerts,.TableStatusUpdated" => 'handleTableStatusUpdated',
            "echo-private:restaurant.{$restaurantId}.alerts,.WaiterCalled" => 'notifyWaiterCalled',
            "echo-private:restaurant.{$restaurantId}.alerts,.BillRequested" => 'notifyBillRequested',
        ];
    }

    // ── TENANT / BRANCH ISOLATION HELPERS ─────────────────────────
    protected function shouldHandleBranchEvent(?int $eventBranchId): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        if (!empty($user->branch_id)) {
            return (int) $eventBranchId === (int) $user->branch_id;
        }

        $restaurant = $user->restaurant;
        if ($restaurant && $restaurant->has_branches) {
            return empty($eventBranchId);
        }

        return true;
    }

    protected function resolveEventBranchId(array $event): ?int
    {
        if (isset($event['branch_id']) && $event['branch_id'] !== null) {
            return (int) $event['branch_id'];
        }

        if (isset($event['branchId']) && $event['branchId'] !== null) {
            return (int) $event['branchId'];
        }

        if (isset($event['order']['branch_id']) && $event['order']['branch_id'] !== null) {
            return (int) $event['order']['branch_id'];
        }

        if (!empty($event['table_id'])) {
            return RestaurantTable::where('id', $event['table_id'])->value('branch_id');
        }

        if (!empty($event['tableId'])) {
            return RestaurantTable::where('id', $event['tableId'])->value('branch_id');
        }

        if (!empty($event['order']['restaurant_table_id'])) {
            return RestaurantTable::where('id', $event['order']['restaurant_table_id'])->value('branch_id');
        }

        return null;
    }

    public function formatTableNumber($tableNum): string
    {
        if (!$tableNum) return 'Table-Unknown';
        $cleanNum = str_replace(['Table-', 'Table - ', 'Table ', 'T-', 't-'], '', $tableNum);
        return 'Table-' . trim($cleanNum);
    }

    // ── REAL-TIME EVENT HANDLERS ──────────────────────────────────
    public function notifyWaiterCalled($event): void
    {
        if (($event['service_type'] ?? 'dine_in') === 'room') {
            return;
        }

        if (!$this->shouldHandleBranchEvent($this->resolveEventBranchId($event))) {
            return;
        }

        $rawNum = $event['table_number'] ?? '?';
        $displayNum = $this->formatTableNumber($rawNum);
        $customer = $event['customer_name'] ?? 'A Guest';

        $cleanSpeechNum = str_replace(['Table-', 'Table - ', 'Table ', 'T-', 't-'], '', $rawNum);
        $numSpeech = is_numeric($cleanSpeechNum) ? (int)$cleanSpeechNum : ltrim($cleanSpeechNum, '0');

        $alertId = uniqid('alert_');
        $this->activeAlerts[] = [
            'id' => $alertId,
            'type' => 'waiter_called',
            'table_number' => $displayNum,
            'table_id' => $event['table_id'] ?? null,
            'customer_name' => $customer,
            'time' => now()->format('h:i A'),
            'timestamp' => now()->timestamp,
        ];

        Notification::make()
            ->title("Assistance Requested: {$displayNum}")
            ->body("{$customer} is calling for a waiter.")
            ->warning()
            ->duration(4000)
            ->send();

        // 1. Desktop Browser Notification (even if minimized)
        $this->dispatch('trigger-browser-notification', [
            'title' => "🔔 Waiter Called: {$displayNum}",
            'body' => "{$customer} needs assistance.",
            'type' => 'call'
        ]);

        // 2. Play Web Audio Chime (double bell ding-dong)
        $this->dispatch('play-sound', ['type' => 'call']);

        // 3. Indian English / Hindi Speech Voice
        $this->dispatch('speak-notification', ['text' => "Table number {$numSpeech} needs assistance."]);

        $this->dispatch('$refresh');
    }

    public function notifyBillRequested($event): void
    {
        if (!$this->shouldHandleBranchEvent($this->resolveEventBranchId($event))) {
            return;
        }

        $rawNum = $event['table_number'] ?? '?';
        $displayNum = $this->formatTableNumber($rawNum);
        $customer = $event['customer_name'] ?? 'A Guest';

        $cleanSpeechNum = str_replace(['Table-', 'Table - ', 'Table ', 'T-', 't-'], '', $rawNum);
        $numSpeech = is_numeric($cleanSpeechNum) ? (int)$cleanSpeechNum : ltrim($cleanSpeechNum, '0');

        $cacheKey = "waiter_bill_alert_{$rawNum}";
        if (!Cache::has($cacheKey)) {
            $alertId = uniqid('alert_');
            $this->activeAlerts[] = [
                'id' => $alertId,
                'type' => 'bill_requested',
                'table_number' => $displayNum,
                'table_id' => $event['table_id'] ?? null,
                'customer_name' => $customer,
                'time' => now()->format('h:i A'),
                'timestamp' => now()->timestamp,
            ];

            Notification::make()
                ->title("💰 Bill Requested: {$displayNum}")
                ->body("{$customer} requested their final check.")
                ->warning()
                ->duration(4000)
                ->send();

            // 1. Desktop Browser Notification
            $this->dispatch('trigger-browser-notification', [
                'title' => "💰 Bill Requested: {$displayNum}",
                'body' => "{$customer} requested bill.",
                'type' => 'bill'
            ]);

            // 2. Web Audio Chime (cash register tone)
            $this->dispatch('play-sound', ['type' => 'bill']);

            // 3. Voice TTS
            $this->dispatch('speak-notification', ['text' => "Bill requested at Table number {$numSpeech}."]);

            Cache::put($cacheKey, true, now()->addSeconds(30));
        }

        $this->dispatch('$refresh');
    }

    public function handleOrderStatusUpdated($event): void
    {
        if (!$this->shouldHandleBranchEvent($this->resolveEventBranchId($event))) {
            return;
        }

        $order = $event['order'] ?? null;
        $status = $order['status'] ?? null;
        $orderId = $order['id'] ?? '?';

        if ($status === 'ready') {
            $serviceType = $order['service_type'] ?? 'dine_in';
            $displayLocation = 'Unknown';
            $speechLocation = 'Unknown Location';

            if ($serviceType === 'parcel') {
                $counterName = $order['parcel_qr_session']['parcel_qr_code']['name'] ?? 'Counter';
                $displayLocation = "🛍️ Parcel ({$counterName})";
                $speechLocation = "Parcel Counter {$counterName}";
            } elseif ($serviceType === 'room_service') {
                $roomNum = $order['room_session']['room']['room_number'] ?? '?';
                $displayLocation = "🚪 Room {$roomNum}";
                $speechLocation = "Room {$roomNum}";
            } else {
                $tableNum = $order['restaurant_table_id']
                    ? RestaurantTable::where('id', $order['restaurant_table_id'])->value('table_number')
                    : ($order['table_number'] ?? '?');
                $displayLocation = $this->formatTableNumber($tableNum);
                $cleanSpeechNum = str_replace(['Table-', 'Table - ', 'Table ', 'T-', 't-'], '', $tableNum);
                $speechLocation = "Table number " . (is_numeric($cleanSpeechNum) ? (int)$cleanSpeechNum : $cleanSpeechNum);
            }

            Notification::make()
                ->title("🛎️ Order #{$orderId} is READY for Pickup!")
                ->body("Location: {$displayLocation}. Food is hot at the pass.")
                ->success()
                ->duration(4000)
                ->send();

            // 1. Desktop Browser Notification
            $this->dispatch('trigger-browser-notification', [
                'title' => "🛎️ Order #{$orderId} READY for Pickup!",
                'body' => "Destination: {$displayLocation}. Please serve promptly.",
                'type' => 'ready'
            ]);

            // 2. Web Audio Chime (ascending 3-bell pass chime)
            $this->dispatch('play-sound', ['type' => 'ready']);

            // 3. Voice TTS
            $this->dispatch('speak-notification', [
                'text' => "Order number {$orderId} is ready for {$speechLocation}."
            ]);
        }

        $this->dispatch('$refresh');
    }

    public function handleTableStatusUpdated($event): void
    {
        if (!$this->shouldHandleBranchEvent($this->resolveEventBranchId($event))) {
            return;
        }

        $status = $event['status'] ?? null;
        if ($status === 'cleaning') {
            $tableId = $event['tableId'] ?? $event['table_id'] ?? null;
            $table = RestaurantTable::find($tableId);
            $displayNum = $table ? $this->formatTableNumber($table->table_number) : 'Table';
            $cleanSpeechNum = str_replace(['Table-', 'Table - ', 'Table ', 'T-', 't-'], '', $table?->table_number ?? '');
            $numSpeech = is_numeric($cleanSpeechNum) ? (int)$cleanSpeechNum : ltrim($cleanSpeechNum, '0');

            Notification::make()
                ->title("🧹 {$displayNum} Ready for Cleaning")
                ->body("Guest session ended. Please clean and sanitize table.")
                ->warning()
                ->duration(4500)
                ->send();

            $this->dispatch('trigger-browser-notification', [
                'title' => "🧹 {$displayNum} Ready for Cleaning",
                'body' => "Session ended. Please sanitize table.",
                'type' => 'call'
            ]);

            $this->dispatch('play-sound', ['type' => 'call']);
            $this->dispatch('speak-notification', ['text' => "Table number {$numSpeech} is ready for cleaning."]);
        }

        $this->dispatch('$refresh');
    }

    public function handleOrderCancelled($event): void
    {
        if (!$this->shouldHandleBranchEvent($this->resolveEventBranchId($event))) {
            return;
        }
        $this->dispatch('$refresh');
    }

    // ── WAITER WORKFLOW ACTIONS ───────────────────────────────────
    public function resolveAlert($alertId): void
    {
        $this->activeAlerts = array_values(array_filter($this->activeAlerts, fn($a) => $a['id'] !== $alertId));
        Notification::make()->title('Alert attended and dismissed')->success()->send();
    }

    public function markAsServed($orderId): void
    {
        $user = auth()->user();

        $query = Order::where('restaurant_id', $user->restaurant_id)->where('id', $orderId);
        if ($user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        $order = $query->firstOrFail();

        if (!in_array($order->status, ['ready', 'preparing', 'accepted', 'placed'])) {
            Notification::make()->title('Order cannot be marked as served in current status.')->warning()->send();
            return;
        }

        DB::transaction(function () use ($order, $user) {
            $oldStatus = $order->status;
            $order->update(['status' => 'served']);
            KitchenQueue::where('order_id', $order->id)->delete();

            $user->increment('total_served');

            OrderStatusLog::create([
                'order_id' => $order->id,
                'from_status' => $oldStatus,
                'to_status' => 'served',
                'changed_by' => $user->id,
            ]);

            ActivityLog::create([
                'actor_type' => 'staff',
                'actor_id' => $user->id,
                'action' => 'marked_served',
                'entity_type' => Order::class,
                'entity_id' => $order->id,
                'metadata' => [
                    'table_id' => $order->restaurant_table_id,
                    'room_session_id' => $order->room_session_id,
                    'parcel_session_id' => $order->parcel_qr_session_id,
                ]
            ]);
        });

        event(new OrderStatusUpdated($order));

        Notification::make()
            ->title("Order #{$order->id} Marked as Served ✔️")
            ->body("Delivered to guest.")
            ->success()
            ->send();

        $this->dispatch('$refresh');
    }

    public function changeTableStatus($tableId, $status): void
    {
        $user = auth()->user();
        $table = RestaurantTable::where('restaurant_id', $user->restaurant_id)->findOrFail($tableId);

        $oldStatus = $table->status;
        $table->update(['status' => $status]);

        ActivityLog::create([
            'actor_type' => 'staff',
            'actor_id' => $user->id,
            'action' => 'updated_table_status',
            'entity_type' => RestaurantTable::class,
            'entity_id' => $table->id,
            'metadata' => [
                'from_status' => $oldStatus,
                'to_status' => $status,
            ]
        ]);

        event(new \App\Events\TableStatusUpdated($table->id, $table->status, $table->restaurant_id));

        Notification::make()
            ->title("{$this->formatTableNumber($table->table_number)} updated to " . ucfirst($status))
            ->success()
            ->send();

        $this->dispatch('$refresh');
    }

    public function switchTab($tab): void
    {
        $this->currentTab = $tab;
    }

    // ── TABLE DETAILS MODAL ACTIONS ───────────────────────────────
    public function openTableDetails($tableId): void
    {
        $this->viewingTableId = $tableId;
    }

    public function closeTableDetails(): void
    {
        $this->viewingTableId = null;
    }

    public function openPlaceOrderFromDetails($tableId): void
    {
        $this->closeTableDetails();
        $this->openPlaceOrder($tableId);
    }

    // ── TABLE-SIDE FAST KOT ORDERING (STITCH 1a515631d2ff4021b0346acbd2a7a53c) ──
    public function openPlaceOrder($tableId, $counterId = null, $roomId = null): void
    {
        $this->placeOrderTableId = $tableId;
        $this->placeOrderCounterId = $counterId;
        $this->placeOrderRoomId = $roomId;
        $this->placeOrderCart = [];
        $this->placeOrderNotes = '';
        $this->placeOrderPriority = 'normal';
        $this->placeOrderPaymentStatus = 'pending';
        $this->placeOrderCategory = 'all';
        $this->placeOrderSearch = '';
        $this->placeOrderVegFilter = 'all';

        if ($tableId) {
            $table = RestaurantTable::find($tableId);
            $activeSession = QrSession::where('restaurant_table_id', $tableId)
                ->where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->first();

            $this->placeOrderSessionId = $activeSession?->id;
            $this->placeOrderCustomerName = $activeSession?->customer_name ?? ('Guest (' . $this->formatTableNumber($table?->table_number) . ')');
        } elseif ($counterId) {
            $this->placeOrderCustomerName = 'Takeaway Guest';
        }

        $this->isPlaceOrderOpen = true;
    }

    public function closePlaceOrder(): void
    {
        $this->isPlaceOrderOpen = false;
        $this->placeOrderTableId = null;
        $this->placeOrderCounterId = null;
        $this->placeOrderRoomId = null;
        $this->placeOrderSessionId = null;
        $this->placeOrderCart = [];
    }

    public function addToCart($menuItemId): void
    {
        $item = MenuItem::find($menuItemId);
        if (!$item) return;

        if ($item->track_stock && $item->stock_quantity !== null && $item->stock_quantity <= 0) {
            Notification::make()->title("{$item->name} is out of stock.")->danger()->send();
            return;
        }

        $currentQty = $this->placeOrderCart[$menuItemId]['quantity'] ?? 0;
        $newQty = $currentQty + 1;

        if ($item->track_stock && $item->stock_quantity !== null && $newQty > $item->stock_quantity) {
            Notification::make()->title("Only {$item->stock_quantity} available for {$item->name}.")->warning()->send();
            return;
        }

        $imageUrl = $item->image_path ? Storage::disk('public')->url($item->image_path) : null;

        $this->placeOrderCart[$menuItemId] = [
            'id' => $item->id,
            'name' => $item->name,
            'unit_price' => (float) $item->price,
            'quantity' => $newQty,
            'notes' => $this->placeOrderCart[$menuItemId]['notes'] ?? '',
            'type' => $item->type ?? 'veg',
            'category_id' => $item->category_id,
            'image_url' => $imageUrl,
        ];
    }

    public function decreaseCart($menuItemId): void
    {
        if (!isset($this->placeOrderCart[$menuItemId])) return;

        if ($this->placeOrderCart[$menuItemId]['quantity'] > 1) {
            $this->placeOrderCart[$menuItemId]['quantity']--;
        } else {
            unset($this->placeOrderCart[$menuItemId]);
        }
    }

    public function removeFromCart($menuItemId): void
    {
        unset($this->placeOrderCart[$menuItemId]);
    }

    public function clearCart(): void
    {
        $this->placeOrderCart = [];
    }

    public function setOrderCategory($categoryId): void
    {
        $this->placeOrderCategory = $categoryId;
    }

    public function setOrderVegFilter($filter): void
    {
        $this->placeOrderVegFilter = $filter;
    }

    public function submitPlaceOrder(): void
    {
        if (empty($this->placeOrderCart)) {
            Notification::make()->title('Please add at least one dish to the order.')->warning()->send();
            return;
        }

        $user = auth()->user();
        $customerName = trim($this->placeOrderCustomerName) ?: 'Guest';

        if (!$this->placeOrderSessionId) {
            if ($this->placeOrderTableId) {
                $table = RestaurantTable::where('restaurant_id', $user->restaurant_id)->findOrFail($this->placeOrderTableId);
                $session = QrSession::create([
                    'restaurant_id' => $table->restaurant_id,
                    'branch_id' => $table->branch_id,
                    'restaurant_table_id' => $table->id,
                    'customer_name' => $customerName,
                    'session_token' => Str::uuid()->toString(),
                    'is_primary' => true,
                    'join_status' => 'active',
                    'is_active' => true,
                    'expires_at' => now()->addHours(4),
                ]);

                if ($table->status !== 'occupied') {
                    $table->update(['status' => 'occupied']);
                    event(new \App\Events\TableStatusUpdated($table->id, 'occupied', $table->restaurant_id));
                }

                $this->placeOrderSessionId = $session->id;
            } elseif ($this->placeOrderCounterId) {
                $parcelCounter = ParcelQrCode::where('restaurant_id', $user->restaurant_id)->findOrFail($this->placeOrderCounterId);
                $session = ParcelQrSession::create([
                    'restaurant_id' => $parcelCounter->restaurant_id,
                    'branch_id' => $parcelCounter->branch_id,
                    'parcel_qr_code_id' => $parcelCounter->id,
                    'customer_name' => $customerName,
                    'session_token' => Str::uuid()->toString(),
                    'status' => 'active',
                    'last_activity_at' => now(),
                ]);
                $this->placeOrderSessionId = $session->id;
            } else {
                Notification::make()->title('Please select a table or counter first.')->danger()->send();
                return;
            }
        }

        $validatedItems = [];
        $outOfStockNames = [];

        DB::transaction(function () use (&$validatedItems, &$outOfStockNames) {
            foreach ($this->placeOrderCart as $cartItem) {
                $menuItem = MenuItem::where('id', $cartItem['id'])->lockForUpdate()->first();
                if ($menuItem && $menuItem->track_stock && $menuItem->stock_quantity !== null) {
                    if ($menuItem->stock_quantity <= 0) {
                        $outOfStockNames[] = $menuItem->name;
                        continue;
                    } elseif ($menuItem->stock_quantity < $cartItem['quantity']) {
                        $cartItem['quantity'] = $menuItem->stock_quantity;
                    }
                }
                $validatedItems[] = $cartItem;
            }
        });

        if (!empty($outOfStockNames)) {
            Notification::make()->title('Some Items Sold Out!')->body(implode(', ', $outOfStockNames))->warning()->send();
        }

        if (empty($validatedItems)) {
            return;
        }

        $totalAmount = collect($validatedItems)->sum(fn($i) => $i['unit_price'] * $i['quantity']);

        $paymentStatus = match ($this->placeOrderPaymentStatus) {
            'paid_upi', 'paid_card', 'paid_cash', 'paid' => 'paid',
            default => 'pending',
        };

        $orderData = [
            'restaurant_id' => $user->restaurant_id,
            'branch_id' => $user->branch_id,
            'customer_name' => $customerName,
            'notes' => $this->placeOrderNotes,
            'total_amount' => $totalAmount,
            'confirmed_total' => $totalAmount,
            'status' => 'accepted',
            'payment_status' => $paymentStatus,
        ];

        if ($this->placeOrderCounterId) {
            $orderData['parcel_qr_session_id'] = $this->placeOrderSessionId;
            $orderData['service_type'] = 'parcel';
        } elseif ($this->placeOrderRoomId) {
            $orderData['room_session_id'] = $this->placeOrderSessionId;
            $orderData['service_type'] = 'room_service';
        } else {
            $orderData['restaurant_table_id'] = $this->placeOrderTableId;
            $orderData['qr_session_id'] = $this->placeOrderSessionId;
            $orderData['service_type'] = 'dine_in';
        }

        $order = Order::create($orderData);

        foreach ($validatedItems as $item) {
            $menuItem = MenuItem::find($item['id']);
            if ($menuItem && $menuItem->track_stock && $menuItem->stock_quantity !== null) {
                $menuItem->decrement('stock_quantity', $item['quantity']);
                if ($menuItem->fresh()->stock_quantity <= 0) {
                    $menuItem->update(['is_available' => false]);
                }
            }
            $order->items()->create([
                'menu_item_id' => $item['id'],
                'item_name' => $menuItem ? $menuItem->name : 'Custom Item',
                'quantity' => $item['quantity'],
                'confirmed_qty' => $item['quantity'],
                'requested_qty' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['unit_price'] * $item['quantity'],
                'notes' => $item['notes'] ?? null,
            ]);
        }

        if (auth()->user()->restaurant?->has_detailed_inventory) {
            InventoryService::deductForOrder($order);
        }

        $priorityVal = match ($this->placeOrderPriority) {
            'urgent' => 10,
            'hold' => -5,
            default => 0,
        };

        KitchenQueue::firstOrCreate(['order_id' => $order->id], [
            'current_status' => 'placed',
            'priority' => $priorityVal
        ]);

        OrderStatusLog::create([
            'order_id' => $order->id,
            'from_status' => null,
            'to_status' => 'placed',
            'changed_by' => $user->id,
        ]);

        ActivityLog::create([
            'actor_type' => 'staff',
            'actor_id' => $user->id,
            'action' => 'placed_order',
            'entity_type' => Order::class,
            'entity_id' => $order->id,
            'metadata' => [
                'staff_name' => $user->name,
                'role' => $user->role?->name ?? 'waiter',
            ]
        ]);

        OrderStatusUpdated::dispatch($order->fresh(['items.menuItem']));

        Notification::make()->title("KOT #{$order->id} Sent to Kitchen!")->success()->send();
        $this->closePlaceOrder();
        $this->dispatch('$refresh');
    }

    // ── DATA ACCESSORS FOR BLADE ───────────────────────────────────
    public function getReadyOrdersProperty()
    {
        $user = auth()->user();
        $query = Order::with([
            'items.menuItem',
            'table',
            'session',
            'roomSession.room',
            'parcelQrSession.parcelQrCode'
        ])
        ->where('restaurant_id', $user->restaurant_id)
        ->where('status', 'ready');

        if ($user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        } else {
            $query->whereNull('branch_id');
        }

        return $query->orderBy('updated_at', 'asc')->get();
    }

    public function getTablesProperty()
    {
        $user = auth()->user();
        $query = RestaurantTable::where('restaurant_id', $user->restaurant_id)
            ->with([
                'qrSessions' => fn($q) => $q->where('is_active', true)->with(['orders' => fn($o) => $o->whereIn('status', ['placed', 'accepted', 'preparing', 'ready'])]),
            ]);

        if ($user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        } else {
            $query->whereNull('branch_id');
        }

        if (!empty($this->searchQuery)) {
            $query->where('table_number', 'like', '%' . $this->searchQuery . '%');
        }

        if ($this->tableFilter === 'occupied') {
            $query->where('status', 'occupied');
        } elseif ($this->tableFilter === 'available') {
            $query->where('status', 'available');
        } elseif ($this->tableFilter === 'cleaning') {
            $query->where('status', 'cleaning');
        }

        return $query->orderByRaw('CAST(table_number AS UNSIGNED) ASC')->orderBy('table_number', 'asc')->get();
    }

    public function getPlaceOrderCategoriesProperty()
    {
        $user = auth()->user();
        $branchId = $user->branch_id;
        $branch = $branchId ? \App\Models\Branch::find($branchId) : null;

        return Category::where('restaurant_id', $user->restaurant_id)
            ->when(
                $branchId,
                function ($q) use ($branch, $branchId) {
                    if ($branch && !$branch->inherit_restaurant_menu) {
                        return $q->where('branch_id', $branchId);
                    }
                    return $q->where(fn($q2) => $q2->where('branch_id', $branchId)->orWhereNull('branch_id'));
                },
                fn($q) => $q->whereNull('branch_id')
            )
            ->withCount(['menuItems' => fn($q) => $q->where('is_available', true)])
            ->orderBy('sort_order', 'asc')
            ->get();
    }

    public function getPlaceOrderMenuItemsProperty()
    {
        $user = auth()->user();
        $branchId = $user->branch_id;
        $branch = $branchId ? \App\Models\Branch::find($branchId) : null;

        $query = MenuItem::query()
            ->select('menu_items.*')
            ->join('categories', 'categories.id', '=', 'menu_items.category_id')
            ->where('menu_items.restaurant_id', $user->restaurant_id)
            ->when(
                $branchId,
                function ($q) use ($branch, $branchId) {
                    if ($branch && !$branch->inherit_restaurant_menu) {
                        return $q->where('menu_items.branch_id', $branchId);
                    }
                    return $q->where(fn($q2) => $q2->where('menu_items.branch_id', $branchId)->orWhereNull('menu_items.branch_id'));
                },
                fn($q) => $q->whereNull('menu_items.branch_id')
            )
            ->where('menu_items.is_available', true)
            ->with('category');

        if ($this->placeOrderCategory !== 'all') {
            $query->where('menu_items.category_id', $this->placeOrderCategory);
        }

        if (!empty($this->placeOrderSearch)) {
            $q = trim($this->placeOrderSearch);
            $query->where(function ($sub) use ($q) {
                $sub->where('menu_items.name', 'like', "%{$q}%")
                    ->orWhere('menu_items.description', 'like', "%{$q}%");
            });
        }

        if ($this->placeOrderVegFilter === 'veg') {
            $query->where('menu_items.type', 'veg');
        } elseif ($this->placeOrderVegFilter === 'non-veg') {
            $query->where('menu_items.type', 'non-veg');
        }

        return $query
            ->orderBy('categories.sort_order', 'asc')
            ->orderBy('categories.name', 'asc')
            ->orderBy('menu_items.id', 'asc')
            ->get();
    }

    public function getTotalMenuItemsCountProperty(): int
    {
        $user = auth()->user();
        $branchId = $user->branch_id;
        $branch = $branchId ? \App\Models\Branch::find($branchId) : null;

        return MenuItem::where('restaurant_id', $user->restaurant_id)
            ->when(
                $branchId,
                function ($q) use ($branch, $branchId) {
                    if ($branch && !$branch->inherit_restaurant_menu) {
                        return $q->where('branch_id', $branchId);
                    }
                    return $q->where(fn($q2) => $q2->where('branch_id', $branchId)->orWhereNull('branch_id'));
                },
                fn($q) => $q->whereNull('branch_id')
            )
            ->where('is_available', true)
            ->count();
    }

    public function getShiftStatsProperty(): array
    {
        $user = auth()->user();
        $todayServed = OrderStatusLog::where('changed_by', $user->id)
            ->where('to_status', 'served')
            ->whereDate('created_at', today())
            ->count();

        $occupiedCount = RestaurantTable::where('restaurant_id', $user->restaurant_id)
            ->when($user->branch_id, fn($q) => $q->where('branch_id', $user->branch_id), fn($q) => $q->whereNull('branch_id'))
            ->where('status', 'occupied')
            ->count();

        return [
            'ready_count' => $this->readyOrders->count(),
            'calls_count' => count($this->activeAlerts),
            'occupied_tables' => $occupiedCount,
            'served_today' => $todayServed,
            'total_lifetime' => $user->total_served ?? 0,
        ];
    }

    public function getViewingTableProperty()
    {
        if (!$this->viewingTableId) {
            return null;
        }

        $user = auth()->user();
        return RestaurantTable::where('restaurant_id', $user->restaurant_id)
            ->with(['qrSessions' => fn($q) => $q->where('is_active', true)])
            ->find($this->viewingTableId);
    }

    public function getViewingTableOrdersProperty()
    {
        if (!$this->viewingTableId) {
            return collect();
        }

        $table = RestaurantTable::find($this->viewingTableId);
        if (!$table) {
            return collect();
        }

        $activeSession = QrSession::where('restaurant_table_id', $table->id)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->first();

        $query = Order::with(['items.menuItem', 'statusLogs']);

        if ($activeSession) {
            $query->where(function($q) use ($activeSession, $table) {
                $q->where('qr_session_id', $activeSession->id)
                  ->orWhere(function($sub) use ($table, $activeSession) {
                      $sub->where('restaurant_table_id', $table->id)
                          ->where('created_at', '>=', $activeSession->created_at);
                  });
            });
        } else {
            $query->where('restaurant_table_id', $table->id)
                  ->whereDate('created_at', today());
        }

        return $query->where('status', '!=', 'cancelled')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getOrderPlacedBy(Order $order): array
    {
        // 1. Check ActivityLog for staff order placement
        $activity = ActivityLog::where('entity_type', Order::class)
            ->where('entity_id', $order->id)
            ->where('action', 'placed_order')
            ->latest()
            ->first();

        if ($activity && $activity->actor_type === 'staff' && $activity->actor_id) {
            $user = \App\Models\User::find($activity->actor_id);
            $roleName = ucfirst($user?->role?->name ?? ($activity->metadata['role'] ?? 'Staff'));
            $userName = $user?->name ?? ($activity->metadata['staff_name'] ?? 'Staff');
            return [
                'type' => 'staff',
                'name' => $userName,
                'role' => $roleName,
                'label' => "Placed by {$roleName}: {$userName}",
                'badge' => "👔 {$roleName}: {$userName}",
                'is_staff' => true,
            ];
        }

        // 2. Check OrderStatusLog for placement
        $initialLog = OrderStatusLog::where('order_id', $order->id)
            ->where(function($q) {
                $q->whereNull('from_status')
                  ->orWhere('to_status', 'placed')
                  ->orWhere('to_status', 'accepted');
            })
            ->whereNotNull('changed_by')
            ->first();

        if ($initialLog && $initialLog->changed_by) {
            $user = \App\Models\User::find($initialLog->changed_by);
            if ($user) {
                $roleName = ucfirst($user->role?->name ?? 'Staff');
                return [
                    'type' => 'staff',
                    'name' => $user->name,
                    'role' => $roleName,
                    'label' => "Placed by {$roleName}: {$user->name}",
                    'badge' => "👔 {$roleName}: {$user->name}",
                    'is_staff' => true,
                ];
            }
        }

        // 3. Fallback: Guest Customer Self-Order via QR Menu
        $custName = $order->customer_name ?: 'Guest';
        return [
            'type' => 'customer',
            'name' => $custName,
            'role' => 'Customer',
            'label' => "Self-Ordered by {$custName} (QR)",
            'badge' => "📱 Customer Self-Order (QR)",
            'is_staff' => false,
        ];
    }
}
