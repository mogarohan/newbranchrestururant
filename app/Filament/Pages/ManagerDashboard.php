<?php

namespace App\Filament\Pages;

use App\Models\RestaurantTable;
use App\Models\Room;
use App\Models\Order;
use App\Models\KitchenQueue;
use App\Models\OrderStatusLog;
use App\Models\Payment;
use App\Models\QrSession;
use App\Models\RoomSession;
use App\Models\ParcelQrSession;
use App\Models\ParcelQrCode;
use App\Models\MenuItem;
use App\Models\Category;
use Filament\Pages\Page;
use App\Models\ActivityLog;
use Filament\Support\Enums\MaxWidth;
use App\Events\OrderStatusUpdated;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use App\Services\InventoryService;

class ManagerDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-command-line';
    protected static string $view = 'filament.pages.manager-dashboard';
    protected static ?string $navigationLabel = 'Manager Dashboard';
    protected static ?string $title = 'Manager Dashboard Control';
    protected static ?int $navigationSort = 1;

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return '';
    }

    public $currentTab = 'tables';

    public $selectedTableId = null;
    public $selectedParcelCounterId = null;
    public $selectedRoomId = null;
    public $selectedSessionId = null;

    public $discountAmount = 0;
    public $taxPercentage = 0;
    public $extraCharges = 0;

    // 🌟 Filter & Search Properties (Stitch UI) 🌟
    public $tableFilter = 'all'; // 'all', 'occupied', 'available', 'bill_pending', 'cleaning'
    public $searchQuery = '';

    // 🌟 Place Order / KOT Visual Menu Properties (Stitch UI 1a515631d2ff4021b0346acbd2a7a53c) 🌟
    public $isPlaceOrderOpen = false;
    public $placeOrderTableId = null;
    public $placeOrderCounterId = null;
    public $placeOrderRoomId = null;
    public $placeOrderSessionId = null;
    public $placeOrderCustomerName = 'Guest';
    public $placeOrderPaymentStatus = 'pending'; // 'pending', 'paid_upi', 'paid_card', 'paid_cash'
    public $placeOrderNotes = '';
    public $placeOrderPriority = 'normal'; // 'normal', 'urgent', 'hold'
    public $placeOrderCategory = 'all';
    public $placeOrderSearch = '';
    public $placeOrderVegFilter = 'all'; // 'all', 'veg', 'non-veg'
    public $placeOrderAutoPrint = true;
    public $placeOrderCart = [];

    // 🌟 ALL-IN-ONE Feature Property 🌟
    public $activeAlerts = []; 

    public function mount(): void
    {
        $this->checkLowStockAlerts();
    }

    public function switchTab($tab): void
    {
        $this->currentTab = $tab;
        $this->closeReceiptModal();
    }

    public function setTableFilter($filter): void
    {
        $this->tableFilter = $filter;
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->restaurant_id !== null
            && in_array($user->role->name ?? '', ['restaurant_admin', 'branch_admin', 'manager']);
    }

    public function checkLowStockAlerts(): void
    {
        $user = auth()->user();

        $base = MenuItem::where('restaurant_id', $user->restaurant_id)
            ->where('track_stock', true)
            ->when(
                $user->branch_id,
                fn($q) => $q->where(fn($q2) => $q2->where('branch_id', $user->branch_id)->orWhereNull('branch_id')),
                fn($q) => $q->whereNull('branch_id')
            );

        $outItems = (clone $base)->where('stock_quantity', '<=', 0)->pluck('name');
        $lowItems = (clone $base)
            ->where('stock_quantity', '>', 0)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->pluck('name');

        if ($outItems->isNotEmpty()) {
            Notification::make()->title('❌ Out of Stock: ' . $outItems->take(3)->implode(', ') . ($outItems->count() > 3 ? '...' : ''))->body('These items are hidden from the customer menu automatically.')->danger()->persistent()->send();
        }

        if ($lowItems->isNotEmpty()) {
            Notification::make()->title('⚠️ Low Stock: ' . $lowItems->take(3)->implode(', ') . ($lowItems->count() > 3 ? '...' : ''))->body('Restock soon from Inventory → Stock Management.')->warning()->send();
        }
    }

    public function getListeners(): array
    {
        $restaurantId = auth()->user()->restaurant_id;

        return [
            "echo-private:restaurant.{$restaurantId},.OrderStatusUpdated" => 'handleOrderStatusUpdated',
            "echo-private:restaurant.{$restaurantId},.OrderCancelled" => 'handleOrderCancelled',
            "echo-private:restaurant.{$restaurantId},.GuestJoinRequested" => 'handleGuestJoinRequested',
            "echo-private:restaurant.{$restaurantId}.alerts,.TableStatusUpdated" => 'handleTableStatusUpdated',
            "echo-private:restaurant.{$restaurantId}.alerts,.WaiterCalled" => 'notifyWaiterCalled', 
            "echo-private:restaurant.{$restaurantId}.alerts,.BillRequested" => 'notifyBillRequested',
            "echo-private:restaurant.{$restaurantId}.alerts,.PaymentMethodSelected" => 'notifyPaymentMethod',
            "echo-private:restaurant.{$restaurantId},.NewParcelOrder" => 'handleNewOrder',
            "echo-private:restaurant.{$restaurantId},.NewOrderPlaced" => 'handleNewOrder',
        ];
    }

    // ── TENANT / BRANCH ISOLATION HELPERS ─────────────────────────
    protected function shouldHandleBranchEvent(?int $eventBranchId): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        // 1. If this manager is assigned to a branch: ONLY process events for their own branch!
        if (!empty($user->branch_id)) {
            return (int) $eventBranchId === (int) $user->branch_id;
        }

        // 2. If this is a Main Restaurant user and the restaurant has branches:
        // Main restaurant only handles tables/entities with branch_id == null.
        $restaurant = $user->restaurant;
        if ($restaurant && $restaurant->has_branches) {
            return empty($eventBranchId);
        }

        // 3. Single-outlet restaurant (no branches configured): handles all events for this restaurant.
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
            return \App\Models\RestaurantTable::where('id', $event['table_id'])->value('branch_id');
        }

        if (!empty($event['tableId'])) {
            return \App\Models\RestaurantTable::where('id', $event['tableId'])->value('branch_id');
        }

        if (!empty($event['order']['restaurant_table_id'])) {
            return \App\Models\RestaurantTable::where('id', $event['order']['restaurant_table_id'])->value('branch_id');
        }

        return null;
    }

    // 🌟 FORMAT HELPER FOR CONSISTENT "Table-01" ALL OVER 🌟
    private function formatTableNumber($tableNum): string
    {
        if (!$tableNum) return 'Table-Unknown';
        // "T-01" k "Table 01" badhu automatically "Table-01" ma convert thay jase
        $cleanNum = str_replace(['Table-', 'Table - ', 'Table ', 'T-', 't-'], '', $tableNum);
        return 'Table-' . trim($cleanNum);
    }

    // 🌟 ALL-IN-ONE Feature Functions 🌟
    public function notifyWaiterCalled($event): void
    {
        // Tenant isolation: ignore events from room service or other branches
        if (($event['service_type'] ?? 'dine_in') === 'room') {
            return;
        }

        if (!$this->shouldHandleBranchEvent($this->resolveEventBranchId($event))) {
            return;
        }

        $rawNum = $event['table_number'] ?? '?';
        $displayNum = $this->formatTableNumber($rawNum); // EX: Table-01
        $customer = $event['customer_name'] ?? 'A customer';
        
        $cleanSpeechNum = str_replace(['Table-', 'Table - ', 'Table ', 'T-', 't-'], '', $rawNum);
        $numSpeech = is_numeric($cleanSpeechNum) ? (int)$cleanSpeechNum : ltrim($cleanSpeechNum, '0');

        $this->activeAlerts[] = [
            'id' => uniqid(),
            'table_number' => $displayNum, // Now visual is always Table-01
            'customer_name' => $customer,
            'time' => now()->format('h:i A')
        ];

        Notification::make()->title("Assistance Requested: {$displayNum}")->body("{$customer} requires assistance.")->warning()->send();
        $this->dispatch('trigger-browser-notification', title: "🔔 Waiter Called!", body: "{$displayNum} needs assistance.");
        
        $this->dispatch('speak-notification', text: "Table number {$numSpeech} needs assistance.");

        $this->dispatch('$refresh');
    }

    public function resolveAlert($alertId)
    {
        $this->activeAlerts = array_filter($this->activeAlerts, fn($alert) => $alert['id'] !== $alertId);
    }

    public function changeTableStatus($tableId, $status)
    {
        $table = RestaurantTable::find($tableId);
        if ($table) {
            $table->update(['status' => $status]);
            event(new \App\Events\TableStatusUpdated($table->id, $status, $table->restaurant_id));
            Notification::make()->title("Table marked as " . ucfirst($status))->success()->send();
            $this->closeReceiptModal();
        }
    }
    // 🌟 END ALL-IN-ONE Feature Functions 🌟

    public function handleNewOrder($event)
    {
        if (!$this->shouldHandleBranchEvent($this->resolveEventBranchId($event))) {
            return;
        }

        $this->dispatch('$refresh');

        $order = $event['order'] ?? null;
        if (!$order)
            return;

        $serviceType = $order['service_type'] ?? 'dine_in';
        $orderId = $order['id'] ?? '?';
        $speechText = ""; 
        
        $customerName = $order['customer_name'] ?? 'Customer';

        if ($serviceType === 'parcel') {
            $counterName = $order['parcel_qr_session']['parcel_qr_code']['name'] ?? 'Parcel Counter';
            $this->dispatch('trigger-browser-notification', title: "🛍️ New Parcel Order #{$orderId}", body: "Placed by {$customerName} at {$counterName}. Please confirm it.");
            Notification::make()->title("New Parcel Order #{$orderId}")->body("Customer: {$customerName} | Location: {$counterName}")->warning()->send();
            
            $speechText = "New parcel order received for {$customerName}.";
            
        } elseif ($serviceType === 'room_service') {
            $roomNum = $order['room_session']['room']['room_number'] ?? 'Unknown';
            $roomNumSpeech = is_numeric($roomNum) ? (int)$roomNum : ltrim($roomNum, '0');
            
            $this->dispatch('trigger-browser-notification', title: "🚪 New Room Order #{$orderId}", body: "Room {$roomNum} placed a new order. Please confirm it.");
            Notification::make()->title("New Room Order #{$orderId}")->body("Room: {$roomNum}")->warning()->send();
            
            $speechText = "New order received from Room number {$roomNumSpeech}.";
        } else {
            $tableNum = $order['table_number'] ?? null;
            
            if (!$tableNum && isset($order['restaurant_table_id'])) {
                $table = \App\Models\RestaurantTable::find($order['restaurant_table_id']);
                $tableNum = $table ? $table->table_number : 'Unknown';
            }
            
            $tableNum = $tableNum ?? 'Unknown';
            $displayNum = $this->formatTableNumber($tableNum); // EX: Table-01
            
            $cleanSpeechNum = str_replace(['Table-', 'Table - ', 'Table ', 'T-', 't-'], '', $tableNum);
            $tableNumSpeech = is_numeric($cleanSpeechNum) ? (int)$cleanSpeechNum : ltrim($cleanSpeechNum, '0');

            $this->dispatch('trigger-browser-notification', title: "🛎️ New Order #{$orderId}", body: "{$displayNum} placed a new order. Please confirm it.");
            Notification::make()->title("New Order #{$orderId}")->body("Location: {$displayNum}")->warning()->send();
            
            $speechText = "New order received from Table number {$tableNumSpeech}.";
        }

        $this->dispatch('speak-notification', text: $speechText);
    }

    public function handleOrderStatusUpdated($event)
    {
        if (!$this->shouldHandleBranchEvent($this->resolveEventBranchId($event))) {
            return;
        }

        $this->dispatch('$refresh');

        $order = $event['order'] ?? null;
        $status = $order['status'] ?? null;

        if ($status === 'placed') {
            $this->handleNewOrder($event);
        }
    }

    public function handleTableStatusUpdated($event)
    {
        if (!$this->shouldHandleBranchEvent($this->resolveEventBranchId($event))) {
            return;
        }

        $this->dispatch('$refresh');
    }

    public function handleOrderCancelled($event)
    {
        if (!$this->shouldHandleBranchEvent($this->resolveEventBranchId($event))) {
            return;
        }

        $this->dispatch('$refresh');
    }

    public function handleGuestJoinRequested($event)
    {
        $guest = $event['guest'] ?? null;
        $branchId = $guest['branch_id'] ?? null;
        if ($branchId === null && !empty($guest['restaurant_table_id'])) {
            $branchId = \App\Models\RestaurantTable::where('id', $guest['restaurant_table_id'])->value('branch_id');
        }

        if (!$this->shouldHandleBranchEvent($branchId ? (int)$branchId : null)) {
            return;
        }

        $this->dispatch('$refresh');
    }

    public function notifyBillRequested($event): void
    {
        // Tenant isolation: ignore room service or other branches
        if (($event['service_type'] ?? 'dine_in') === 'room') {
            return;
        }

        if (!$this->shouldHandleBranchEvent($this->resolveEventBranchId($event))) {
            return;
        }

        $rawNum = $event['table_number'] ?? '?';
        $displayNum = $this->formatTableNumber($rawNum); // EX: Table-01
        $customer = $event['customer_name'] ?? 'A customer';
        $cacheKey = "bill_requested_alert_{$rawNum}";
        
        $cleanSpeechNum = str_replace(['Table-', 'Table - ', 'Table ', 'T-', 't-'], '', $rawNum);
        $tableNumSpeech = is_numeric($cleanSpeechNum) ? (int)$cleanSpeechNum : ltrim($cleanSpeechNum, '0');

        if (!Cache::has($cacheKey)) {
            Notification::make()->title("Bill Requested: {$displayNum}")->body("{$customer} has requested their final bill.")->warning()->persistent()->send();
            $this->dispatch('trigger-browser-notification', title: "💰 Bill Requested", body: "{$displayNum} ({$customer}) requested their bill.");
            
            $this->dispatch('speak-notification', text: "Bill requested at Table number {$tableNumSpeech}.");

            Cache::put($cacheKey, true, now()->addSeconds(30));
        }
    }

    public function notifyPaymentMethod($event): void
    {
        if (!$this->shouldHandleBranchEvent($this->resolveEventBranchId($event))) {
            return;
        }

        $rawNum = $event['table_number'] ?? '?';
        $displayNum = $this->formatTableNumber($rawNum); // EX: Table-01
        $method = strtoupper($event['method'] ?? 'CASH');

        Notification::make()->title("Payment Update: {$displayNum}")->body("Customer selected {$method} for payment.")->info()->send();
        $this->dispatch('$refresh');
    }

    public function closeReceiptModal(): void
    {
        $this->selectedTableId = null;
        $this->selectedParcelCounterId = null;
        $this->selectedRoomId = null;
        $this->selectedSessionId = null;
        $this->discountAmount = 0;
        $this->taxPercentage = 0;
        $this->extraCharges = 0;
    }

    public function openTable($tableId): void
    {
        if ($this->selectedTableId === $tableId) {
            $this->closeReceiptModal();
        } else {
            $this->closeReceiptModal();
            $this->selectedTableId = $tableId;
            $firstDiner = QrSession::where('restaurant_table_id', $tableId)
                ->where('is_active', true)
                ->orderBy('is_primary', 'desc')
                ->orderBy('created_at', 'asc')
                ->first();

            if ($firstDiner) {
                $this->selectedSessionId = $firstDiner->id;
            }
        }
    }

    public function openParcelCounter($counterId): void
    {
        if ($this->selectedParcelCounterId === $counterId) {
            $this->closeReceiptModal();
        } else {
            $this->closeReceiptModal();
            $this->selectedParcelCounterId = $counterId;

            $firstDiner = ParcelQrSession::where('parcel_qr_code_id', $counterId)
                ->where('status', 'active')
                ->orderBy('created_at', 'asc')
                ->first();

            if ($firstDiner) {
                $this->selectedSessionId = $firstDiner->id;
            }
        }
    }

    public function openRoom($roomId): void
    {
        if ($this->selectedRoomId === $roomId) {
            $this->closeReceiptModal();
        } else {
            $this->closeReceiptModal();
            $this->selectedRoomId = $roomId;
            $room = Room::with('activeSession')->find($roomId);
            if ($room && $room->activeSession) {
                $this->selectedSessionId = $room->activeSession->id;
            }
        }
    }

    public function selectCustomerSession($sessionId): void
    {
        $this->selectedSessionId = $sessionId;
        $this->discountAmount = 0;
        $this->taxPercentage = 0;
        $this->extraCharges = 0;
    }

    protected function getActiveSession()
    {
        if (!$this->selectedSessionId)
            return null;

        if ($this->selectedParcelCounterId) {
            return ParcelQrSession::find($this->selectedSessionId);
        } elseif ($this->selectedRoomId) {
            return RoomSession::find($this->selectedSessionId);
        } else {
            return QrSession::find($this->selectedSessionId);
        }
    }

    public function assignGuestAction(): Action
    {
        return Action::make('assignGuestAction')
            ->label('Assign Guest')
            ->modalHeading(function (array $arguments) {
                $tableId = $arguments['table_id'] ?? $this->selectedTableId;
                if ($tableId) {
                    $table = RestaurantTable::find($tableId);
                    if ($table) {
                        return 'Assign Guest to ' . $this->formatTableNumber($table->table_number);
                    }
                }
                if ($this->selectedParcelCounterId || !empty($arguments['counter_id'])) {
                    return 'Add Customer to Parcel Queue';
                }
                return 'Assign Guest to Table';
            })
            ->modalWidth(MaxWidth::Medium)
            ->fillForm(function (array $arguments) {
                return [
                    'table_id' => $arguments['table_id'] ?? $this->selectedTableId,
                    'counter_id' => $arguments['counter_id'] ?? $this->selectedParcelCounterId,
                    'customer_name' => '',
                    'notes' => '',
                ];
            })
            ->form([
                Hidden::make('counter_id'),

                Select::make('table_id')
                    ->label('Select Table')
                    ->options(function () {
                        $user = auth()->user();
                        return RestaurantTable::where('restaurant_id', $user->restaurant_id)
                            ->when($user->branch_id, fn($q) => $q->where('branch_id', $user->branch_id)->orWhereNull('branch_id'))
                            ->get()
                            ->mapWithKeys(fn($t) => [$t->id => $this->formatTableNumber($t->table_number) . " (Cap: {$t->seating_capacity}) - " . ucfirst($t->status ?? 'available')]);
                    })
                    ->required(fn(\Filament\Forms\Get $get) => empty($get('counter_id')) && !$this->selectedParcelCounterId)
                    ->visible(fn(\Filament\Forms\Get $get) => empty($get('counter_id')) && !$this->selectedParcelCounterId)
                    ->live(),

                Select::make('party_role')
                    ->label('Party / Seating Role')
                    ->options(function (\Filament\Forms\Get $get) {
                        $tableId = $get('table_id') ?: $this->selectedTableId;
                        if (!$tableId) {
                            return ['new_host' => '👑 Start New Host / Independent Party'];
                        }
                        $activeHosts = QrSession::where('restaurant_table_id', $tableId)
                            ->where('is_active', true)
                            ->where(fn($q) => $q->where('is_primary', true)->orWhereNull('host_session_id'))
                            ->get();
                        $options = ['new_host' => '👑 Start New Host / Independent Party'];
                        foreach ($activeHosts as $h) {
                            $options['join_' . $h->id] = "👥 Join {$h->customer_name}'s Party (Host #{$h->id})";
                        }
                        return $options;
                    })
                    ->default('new_host')
                    ->visible(fn(\Filament\Forms\Get $get) => empty($get('counter_id')) && !$this->selectedParcelCounterId)
                    ->helperText('Choose whether this customer starts their own host party or joins an existing dining group on this table.'),

                TextInput::make('customer_name')
                    ->label('Customer Name')
                    ->placeholder('Enter customer name (optional, defaults to "Guest")')
                    ->helperText('If the customer\'s name is known, enter it here. Otherwise, leave blank to use "Guest".')
                    ->maxLength(255),

                TextInput::make('notes')
                    ->label('Notes / Party Info')
                    ->placeholder('e.g. Window seat, Birthday, 3 guests (optional)')
                    ->nullable(),
            ])
            ->action(function (array $data, array $arguments) {
                $counterId = $data['counter_id'] ?? $arguments['counter_id'] ?? $this->selectedParcelCounterId;

                if ($counterId) {
                    $parcelCounter = ParcelQrCode::where('restaurant_id', auth()->user()->restaurant_id)->findOrFail($counterId);
                    $customerName = trim($data['customer_name'] ?? '');
                    if (empty($customerName)) {
                        $activeCount = ParcelQrSession::where('parcel_qr_code_id', $parcelCounter->id)->where('status', 'active')->count();
                        $customerName = 'Takeaway Customer ' . ($activeCount + 1);
                    }

                    $session = ParcelQrSession::create([
                        'restaurant_id' => $parcelCounter->restaurant_id,
                        'branch_id' => $parcelCounter->branch_id,
                        'parcel_qr_code_id' => $parcelCounter->id,
                        'customer_name' => $customerName,
                        'session_token' => Str::uuid()->toString(),
                        'status' => 'active',
                        'last_activity_at' => now(),
                    ]);

                    $this->selectedParcelCounterId = $parcelCounter->id;
                    $this->selectedSessionId = $session->id;

                    Notification::make()->title("Customer '{$customerName}' added to queue.")->success()->send();
                    return;
                }

                $tableId = $data['table_id'] ?? $arguments['table_id'] ?? $this->selectedTableId;
                $table = RestaurantTable::where('restaurant_id', auth()->user()->restaurant_id)->findOrFail($tableId);

                $customerName = trim($data['customer_name'] ?? '');
                $tableDisplay = $this->formatTableNumber($table->table_number);

                if (empty($customerName)) {
                    $activeCount = QrSession::where('restaurant_table_id', $table->id)->where('is_active', true)->count();
                    $customerName = $activeCount > 0 ? "Guest " . ($activeCount + 1) : "Guest ({$tableDisplay})";
                }

                $partyRole = $data['party_role'] ?? 'new_host';
                $isPrimary = true;
                $hostSessionId = null;
                $joinedHostName = null;

                if (str_starts_with($partyRole, 'join_')) {
                    $targetHostId = (int) str_replace('join_', '', $partyRole);
                    $targetHost = QrSession::where('restaurant_table_id', $table->id)->where('is_active', true)->find($targetHostId);
                    if ($targetHost) {
                        $isPrimary = false;
                        $hostSessionId = $targetHost->id;
                        $joinedHostName = $targetHost->customer_name;
                    }
                }

                $token = Str::uuid()->toString();

                $session = QrSession::create([
                    'restaurant_id' => $table->restaurant_id,
                    'branch_id' => $table->branch_id,
                    'restaurant_table_id' => $table->id,
                    'customer_name' => $customerName,
                    'session_token' => $token,
                    'is_primary' => $isPrimary,
                    'join_status' => 'active',
                    'is_active' => true,
                    'host_session_id' => $hostSessionId,
                    'expires_at' => now()->addHours(4),
                ]);

                if ($table->status !== 'occupied') {
                    $table->update(['status' => 'occupied']);
                    event(new \App\Events\TableStatusUpdated($table->id, 'occupied', $table->restaurant_id));
                }

                $this->selectedTableId = $table->id;
                $this->selectedSessionId = $session->id;

                $roleText = $isPrimary ? "as Primary Host" : "joined to {$joinedHostName}'s party";
                Notification::make()
                    ->title("Guest '{$customerName}' assigned to {$tableDisplay} ({$roleText}).")
                    ->body("You can now place orders on behalf of {$customerName}.")
                    ->success()
                    ->send();
            });
    }

    public function editCustomerNameAction(): Action
    {
        return Action::make('editCustomerNameAction')
            ->label('Edit Customer Name')
            ->modalHeading('Update Customer Name')
            ->modalWidth(MaxWidth::Medium)
            ->form([
                TextInput::make('customer_name')
                    ->label('Customer Name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Enter the customer name')
                    ->helperText('Update the customer name for this active bill/session.'),
            ])
            ->fillForm(function (array $arguments) {
                $sessionId = $arguments['session_id'] ?? $this->selectedSessionId;
                if (!$sessionId) return [];

                if ($this->selectedParcelCounterId || !empty($arguments['is_parcel'])) {
                    $session = ParcelQrSession::find($sessionId);
                    return ['customer_name' => $session?->customer_name ?? ''];
                } elseif ($this->selectedRoomId || !empty($arguments['is_room'])) {
                    $session = RoomSession::find($sessionId);
                    return ['customer_name' => $session?->guest_name ?? ''];
                } else {
                    $session = QrSession::find($sessionId);
                    return ['customer_name' => $session?->customer_name ?? ''];
                }
            })
            ->action(function (array $data, array $arguments) {
                $sessionId = $arguments['session_id'] ?? $this->selectedSessionId;
                $newName = trim($data['customer_name']);

                if ($this->selectedParcelCounterId || !empty($arguments['is_parcel'])) {
                    $session = ParcelQrSession::where('restaurant_id', auth()->user()->restaurant_id)->find($sessionId);
                    if ($session) {
                        $session->update(['customer_name' => $newName]);
                        Order::where('parcel_qr_session_id', $session->id)->update(['customer_name' => $newName]);
                    }
                } elseif ($this->selectedRoomId || !empty($arguments['is_room'])) {
                    $session = RoomSession::where('restaurant_id', auth()->user()->restaurant_id)->find($sessionId);
                    if ($session) {
                        $session->update(['guest_name' => $newName]);
                        if ($session->room) {
                            $session->room->update(['guest_name' => $newName]);
                        }
                        Order::where('room_session_id', $session->id)->update(['customer_name' => $newName]);
                    }
                } else {
                    $session = QrSession::where('restaurant_id', auth()->user()->restaurant_id)->find($sessionId);
                    if ($session) {
                        $session->update(['customer_name' => $newName]);
                        Order::where('qr_session_id', $session->id)->update(['customer_name' => $newName]);
                    }
                }

                Notification::make()->title("Customer name updated to '{$newName}'.")->success()->send();
            });
    }

    public function checkInAction(): Action
    {
        return Action::make('checkInAction')
            ->modalHeading('Check In Guest')
            ->form([
                TextInput::make('guest_name')->label('Guest Full Name')->required(),
                DateTimePicker::make('check_out_at')
                    ->label('Expected Checkout')
                    ->default(now()->addDays(1)->setTime(11, 0))
                    ->required(),
            ])
            ->action(function (array $data, array $arguments) {
                $room = Room::find($arguments['room_id']);
                if (!$room)
                    return;

                $token = Str::uuid()->toString();
                $restaurantSlug = Str::slug($room->restaurant->name);
                $folder = "restaurants/{$restaurantSlug}/RoomsQR";
                Storage::disk('public')->makeDirectory($folder);

                $path = "{$folder}/room_{$room->room_number}_stay.svg";
                $appUrl = 'https://customer.annsathi.com';
                $scanUrl = "{$appUrl}/?type=room&r={$room->restaurant_id}&t={$room->id}&token={$token}";

                $qrImage = QrCode::format('svg')->size(300)->margin(1)->generate($scanUrl);
                Storage::disk('public')->put($path, $qrImage);

                $session = RoomSession::create([
                    'restaurant_id' => $room->restaurant_id,
                    'branch_id' => $room->branch_id,
                    'room_id' => $room->id,
                    'guest_name' => $data['guest_name'],
                    'session_token' => $token,
                    'check_in_at' => now(),
                    'check_out_at' => $data['check_out_at'],
                    'status' => 'active',
                ]);

                $room->update([
                    'status' => 'occupied',
                    'guest_name' => $data['guest_name'],
                    'check_in_at' => now(),
                    'check_out_at' => $data['check_out_at'],
                    'active_room_session_id' => $session->id,
                    'qr_token' => $token,
                    'qr_path' => $path,
                ]);

                $this->selectedSessionId = $session->id;
                Notification::make()->title("Guest checked in. QR is now active.")->success()->send();
            });
    }

    public function checkoutAction(): Action
    {
        return Action::make('checkoutAction')
            ->action(function (array $arguments) {
                $room = Room::find($arguments['room_id']);
                if (!$room)
                    return;

                if ($room->qr_path) {
                    Storage::disk('public')->delete($room->qr_path);
                }

                if ($room->activeSession) {
                    $room->activeSession->update(['status' => 'checked_out']);
                    event(new \App\Events\SessionEnded($room->activeSession->session_token, $room->id));
                }

                $room->update([
                    'status' => 'cleaning',
                    'guest_name' => null,
                    'check_in_at' => null,
                    'check_out_at' => null,
                    'active_room_session_id' => null,
                    'qr_token' => null,
                    'qr_path' => null,
                ]);

                $this->closeReceiptModal();
                Notification::make()->title('Checkout complete. Stay QR has been disabled.')->success()->send();
            });
    }

    public function markCleanAction(): Action
    {
        return Action::make('markCleanAction')
            ->action(function (array $arguments) {
                $room = Room::find($arguments['room_id']);
                if ($room)
                    $room->update(['status' => 'available']);
                Notification::make()->title('Room available for next guest.')->success()->send();
            });
    }

    // 🌟 HELPER TO GENERATE UNIQUE BILL NUMBER
    private function generateBillNumber(): string
    {
        $lastPayment = Payment::where('restaurant_id', auth()->user()->restaurant_id)
            ->whereDate('created_at', now()->toDateString())
            ->whereNotNull('bill_number')
            ->orderBy('id', 'desc')
            ->first();

        $nextSeq = 1;
        if ($lastPayment && preg_match('/-(\d+)$/', $lastPayment->bill_number, $matches)) {
            $nextSeq = (int) $matches[1] + 1;
        }

        // Format: B-240826-0001 (B-DDMMYY-XXXX)
        return 'B-' . now()->format('dmy') . '-' . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
    }

    public function settleRoomBill()
    {
        if (!$this->selectedRoomId || !$this->selectedSessionId)
            return;

        $orders = Order::where('room_session_id', $this->selectedSessionId)->whereIn('status', ['placed', 'accepted', 'preparing', 'ready', 'served'])->get();
        if ($orders->isEmpty())
            return;

        $subtotal = $orders->sum(fn($o) => $o->confirmed_total ?? $o->total_amount);
        $amountPaid = $orders->where('payment_status', 'paid')->sum(fn($o) => $o->confirmed_total ?? $o->total_amount);
        $amountDue = max(0, $subtotal - $amountPaid);
        $latestOrderId = $orders->pluck('id')->last();

        if ($amountDue <= 0)
            return;

        try {
            DB::transaction(function () use ($latestOrderId, $subtotal, $amountDue) {
                $existingPayment = Payment::where('order_id', $latestOrderId)->first();
                $billNumber = $existingPayment->bill_number ?? $this->generateBillNumber();

                Payment::updateOrCreate(['order_id' => $latestOrderId], [
                    'restaurant_id' => auth()->user()->restaurant_id,
                    'subtotal' => $subtotal,
                    'amount' => $amountDue,
                    'status' => 'paid',
                    'payment_method' => 'room_charge',
                    'bill_number' => $billNumber,
                    'paid_at' => now(),
                ]);
                Order::where('room_session_id', $this->selectedSessionId)->update(['payment_status' => 'paid']);
                RoomSession::where('id', $this->selectedSessionId)->update(['is_billed' => true]);
            });
            Notification::make()->title('Room Service Bill Settled.')->success()->send();
        } catch (\Exception $e) {
            Notification::make()->title('Error settling bill')->body($e->getMessage())->danger()->send();
        }
    }

    public function printStayQrAction(): Action
    {
        return Action::make('printStayQrAction')
            ->label('Print Stay QR')
            ->icon('heroicon-o-printer')
            ->modalWidth('5xl')
            ->modalHeading('Design & Print Stay QR')
            ->stickyModalHeader()
            ->stickyModalFooter()
            ->modalSubmitActionLabel('Print Now')
            ->form([
                \Filament\Forms\Components\Grid::make(12)
                    ->schema([
                        \Filament\Forms\Components\Group::make()
                            ->columnSpan(['default' => 12, 'lg' => 7])
                            ->schema([
                                \Filament\Forms\Components\Section::make('Background Setup')
                                    ->schema([
                                        \Filament\Forms\Components\Radio::make('bg_type')
                                            ->label('Background Type')
                                            ->options(['image' => 'Background Image', 'color' => 'Solid Color'])
                                            ->default('image')->inline()->live(),
                                        \Filament\Forms\Components\FileUpload::make('bg_image')
                                            ->label('Upload Background')
                                            ->helperText('Leave empty to use default app background')
                                            ->image()->directory('qr_backgrounds')->live()
                                            ->visible(fn(\Filament\Forms\Get $get) => $get('bg_type') === 'image'),
                                        \Filament\Forms\Components\ColorPicker::make('bg_color')
                                            ->label('Solid Color')
                                            ->default('#E2F0CB')->live()
                                            ->visible(fn(\Filament\Forms\Get $get) => $get('bg_type') === 'color'),
                                    ])->columns(1),

                                \Filament\Forms\Components\Section::make('Color Customization')
                                    ->schema([
                                        \Filament\Forms\Components\ColorPicker::make('name_color')->label('Restaurant Name')->default('#9A3B2A')->live(),
                                        \Filament\Forms\Components\ColorPicker::make('address_color')->label('Guest Name Text')->default('#333333')->live(),
                                        \Filament\Forms\Components\ColorPicker::make('table_color')->label('Room Number')->default('#32402A')->live(),
                                        \Filament\Forms\Components\ColorPicker::make('subtitle_color')->label('Subtitles & Labels')->default('#4B5320')->live(),
                                        \Filament\Forms\Components\ColorPicker::make('accent_color')->label('Divider Lines & Borders')->default('#E47A33')->live(),
                                        \Filament\Forms\Components\ColorPicker::make('pill_bg_color')->label('Scan Pill Background')->default('#B85C4A')->live(),
                                    ])->columns(2),
                            ]),

                        \Filament\Forms\Components\Group::make()
                            ->columnSpan(['default' => 12, 'lg' => 5])
                            ->extraAttributes(['class' => 'lg:sticky lg:top-4'])
                            ->schema([
                                \Filament\Forms\Components\Placeholder::make('pdf_preview')
                                    ->label('Live Design Preview')
                                    ->content(function (\Filament\Forms\Get $get) {
                                        $restaurant = auth()->user()->restaurant;

                                        $bgType = $get('bg_type') ?? 'image';
                                        $bgImage = $get('bg_image');
                                        $bgColor = $get('bg_color') ?? '#E2F0CB';
                                        $nameColor = $get('name_color') ?? '#9A3B2A';
                                        $addressColor = $get('address_color') ?? '#333333';
                                        $tableColor = $get('table_color') ?? '#32402A';
                                        $subtitleColor = $get('subtitle_color') ?? '#4B5320';
                                        $accentColor = $get('accent_color') ?? '#E47A33';
                                        $pillBgColor = $get('pill_bg_color') ?? '#B85C4A';

                                        $bgStyle = '';
                                        if ($bgType === 'image') {
                                            $url = asset('images/b.png');
                                            if (!empty($bgImage)) {
                                                $file = is_array($bgImage) ? reset($bgImage) : $bgImage;
                                                if ($file instanceof TemporaryUploadedFile) {
                                                    try {
                                                        $url = $file->temporaryUrl();
                                                    } catch (\Exception $e) {
                                                        $url = 'data:' . $file->getClientMimeType() . ';base64,' . base64_encode(file_get_contents($file->getRealPath()));
                                                    }
                                                } elseif (is_string($file)) {
                                                    $url = Storage::disk('public')->url($file);
                                                }
                                            }
                                            $bgStyle = "background-image: url('{$url}'); background-size: cover; background-position: center;";
                                        } else {
                                            $bgStyle = "background-color: {$bgColor};";
                                        }

                                        $restName = strtoupper($restaurant->name ?? 'HOTEL');
                                        $logoUrl = ($restaurant && $restaurant->logo_path) ? Storage::disk('public')->url($restaurant->logo_path) : null;
                                        $logoHtml = $logoUrl ? "<img src='{$logoUrl}' style='max-width: 50px; max-height: 50px; object-fit: contain; margin-bottom: 5px;' />" : "";
                                        $guestHtml = "<div style='font-size: 11px; color: {$addressColor}; margin: 4px 10px; line-height: 1.2; font-weight: bold;'>Welcome, Guest Name</div>";

                                        return new HtmlString("
                                            <div style='width: 100%; max-width: 320px; height: 420px; border: 1px dashed #ccc; border-radius: 8px; padding: 20px; text-align: center; margin: 0 auto; {$bgStyle} box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);'>
                                                {$logoHtml}
                                                <div style='font-family: Times, serif; font-size: 18px; font-weight: bold; color: {$nameColor}; text-transform: uppercase; letter-spacing: 1px;'>{$restName}</div>
                                                {$guestHtml}
                                                <div style='border-top: 3px solid {$accentColor}; width: 35px; margin: 8px auto;'></div>
                                                <div style='font-size: 9px; color: {$subtitleColor}; font-weight: bold; letter-spacing: 1px;'>EXQUISITE ROOM SERVICE</div>

                                                <div style='display: flex; justify-content: center; align-items: center; margin-top: 15px;'>
                                                    <div style='border-top: 3px solid {$accentColor}; border-left: 3px solid {$accentColor}; width: 20px; height: 20px; position: absolute; transform: translate(-55px, -55px);'></div>
                                                    <div style='border-bottom: 3px solid {$accentColor}; border-right: 3px solid {$accentColor}; width: 20px; height: 20px; position: absolute; transform: translate(55px, 55px);'></div>

                                                    <div style='background: white; padding: 6px; border-radius: 8px; border: 2px solid #8B5CF6; z-index: 10;'>
                                                        <img src='https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=LivePreview' style='width: 100px; height: 100px; display: block;' />
                                                    </div>
                                                </div>

                                                <div style='margin-top: 15px;'>
                                                    <span style='background-color: {$pillBgColor}; color: white; padding: 6px 20px; border-radius: 15px; font-size: 10px; font-weight: bold; letter-spacing: 1px;'>SCAN TO ORDER</span>
                                                </div>

                                                <div style='margin-top: 12px; font-size: 9px; color: {$subtitleColor}; font-weight: bold; letter-spacing: 0.5px;'>GUEST ROOM</div>
                                                <div style='font-family: Times, serif; font-size: 26px; font-style: italic; font-weight: bold; color: {$tableColor}; margin-top: 2px;'>Room 101</div>
                                            </div>
                                        ");
                                    }),
                            ]),
                    ]),
            ])
            ->action(function (array $data) {
                if (!$this->selectedRoomId)
                    return;
                $room = Room::with('restaurant')->find($this->selectedRoomId);
                if (!$room || !$room->qr_path)
                    return;

                $restaurant = $room->restaurant;
                $bgType = $data['bg_type'] ?? 'image';
                $bgColor = $data['bg_color'] ?? '#E2F0CB';
                $bgImage = $data['bg_image'] ?? null;
                $nameColor = $data['name_color'] ?? '#9A3B2A';
                $addressColor = $data['address_color'] ?? '#333333';
                $tableColor = $data['table_color'] ?? '#32402A';
                $subtitleColor = $data['subtitle_color'] ?? '#4B5320';
                $accentColor = $data['accent_color'] ?? '#E47A33';
                $pillBgColor = $data['pill_bg_color'] ?? '#B85C4A';

                $cardBackgroundStyle = '';
                if ($bgType === 'image') {
                    $bgImagePath = public_path('images/b.png');
                    if (!empty($bgImage)) {
                        $path = is_array($bgImage) ? reset($bgImage) : $bgImage;
                        $bgImagePath = Storage::disk('public')->path($path);
                    }
                    if (file_exists($bgImagePath)) {
                        $mime = mime_content_type($bgImagePath);
                        $bgBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($bgImagePath));
                        $cardBackgroundStyle = 'background-image: url("' . $bgBase64 . '"); background-size: cover; background-position: center; background-repeat: no-repeat;';
                    }
                } else {
                    $cardBackgroundStyle = 'background-color: ' . $bgColor . ';';
                }

                $logoBase64 = '';
                if ($restaurant && $restaurant->logo_path) {
                    $logoFullPath = Storage::disk('public')->path($restaurant->logo_path);
                    if (file_exists($logoFullPath)) {
                        $mime = mime_content_type($logoFullPath);
                        $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoFullPath));
                    }
                }

                $qrPath = storage_path('app/public/' . $room->qr_path);
                $qrBase64 = file_exists($qrPath) ? 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($qrPath)) : '';

                $restName = strtoupper($restaurant->name ?? 'HOTEL');
                $logoHtml = $logoBase64 ? '<img src="' . $logoBase64 . '" style="max-width: 55px; max-height: 55px; object-fit: contain; margin-bottom: 5px;" />' : '';
                $guestHtml = '<div style="font-size: 12px; color: ' . $addressColor . '; margin: 4px 15px; line-height: 1.2; font-weight: bold;">Welcome, ' . htmlspecialchars($room->guest_name) . '</div>';

                $html = '<!DOCTYPE html><html><head><style>
                    body { display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; background-color: #f8fafc; font-family: "Helvetica", "Arial", sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                    .card { width: 340px; height: 480px; padding: 25px; border: 1px dashed #cbd5e1; border-radius: 12px; box-sizing: border-box; text-align: center; ' . $cardBackgroundStyle . ' box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
                    .title { font-family: "Times", serif; font-size: 24px; font-weight: bold; color: ' . $nameColor . '; margin: 0; text-transform: uppercase; letter-spacing: 1px; }
                    .orange-line { border-top: 3px solid ' . $accentColor . '; width: 40px; margin: 10px auto; } 
                    .subtitle { font-size: 10px; color: ' . $subtitleColor . '; font-weight: bold; letter-spacing: 1px; margin-bottom: 15px; }
                    .qr-bracket-table { margin: 0 auto 15px auto; border-collapse: collapse; }
                    .qr-bracket-table td { padding: 0; }
                    .br-tl { border-top: 3px solid ' . $accentColor . '; border-left: 3px solid ' . $accentColor . '; width: 25px; height: 25px; }
                    .br-br { border-bottom: 3px solid ' . $accentColor . '; border-right: 3px solid ' . $accentColor . '; width: 25px; height: 25px; }
                    .qr-img { width: 140px; height: 140px; border: 2px solid #8B5CF6; border-radius: 8px; padding: 4px; background-color: #ffffff; display: block; margin: 6px; }
                    .btn-wrapper { margin-bottom: 15px; } 
                    .scan-pill { background-color: ' . $pillBgColor . '; color: #ffffff; padding: 6px 25px; border-radius: 15px; font-size: 11px; font-weight: bold; display: inline-block; letter-spacing: 1px;}
                    .loc-label { font-size: 10px; color: ' . $subtitleColor . '; font-weight: bold; margin-bottom: 2px; letter-spacing: 0.5px; } 
                    .table-number { font-family: "Times", serif; font-size: 32px; font-style: italic; font-weight: bold; color: ' . $tableColor . '; margin: 0; }
                    @media print { body { background-color: #fff; } .card { border: none; box-shadow: none; margin: 0 auto; } }
                </style></head><body>
                    <div class="card">
                        ' . $logoHtml . '
                        <div class="title">' . $restName . '</div>
                        ' . $guestHtml . '
                        <div class="orange-line"></div><div class="subtitle">EXQUISITE ROOM SERVICE</div>
                        <table class="qr-bracket-table"><tr><td class="br-tl"></td><td></td><td></td></tr><tr><td></td><td><img src="' . $qrBase64 . '" class="qr-img" /></td><td></td></tr><tr><td></td><td></td><td class="br-br"></td></tr></table>
                        <div class="btn-wrapper"><div class="scan-pill">SCAN TO ORDER</div></div>
                        <div class="loc-label">GUEST ROOM</div><div class="table-number">Room ' . $room->room_number . '</div>
                    </div>
                </body></html>';

                $escapedHtml = json_encode($html);
                $this->js("
                    const p = window.open('', '_blank', 'width=600,height=800');
                    p.document.write({$escapedHtml});
                    p.document.close();
                    setTimeout(() => { p.focus(); p.print(); p.onafterprint = () => p.close(); }, 500);
                ");
            });
    }

    public function cancelPendingBill(): void
    {
        $viewData = $this->getViewData();
        $pendingPayment = $viewData['pendingPayment'];

        if ($pendingPayment && $pendingPayment->status === 'pending') {
            $pendingPayment->delete();
            $session = $this->getActiveSession();
            if ($session) {
                event(new \App\Events\BillGenerated($session->id, null));
            }
            $this->discountAmount = 0;
            $this->taxPercentage = 0;
            $this->extraCharges = 0;

            Notification::make()->title('Bill Cancelled')->warning()->send();
        }
    }

    public function printPendingBill(): void
    {
        $viewData = $this->getViewData();
        $pendingPayment = $viewData['pendingPayment'];
        $upiId = $viewData['upiId'];
        $restaurantName = $viewData['restaurantName'];

        if (!$pendingPayment || !$this->selectedSessionId) {
            Notification::make()->title('No active bill found.')->danger()->send();
            return;
        }

        $session = $this->getActiveSession();
        if (!$session)
            return;

        $restaurant = auth()->user()->restaurant;
        $user = auth()->user();
        $branch = null;
        if ($session && $session->branch_id) {
            $branch = \App\Models\Branch::find($session->branch_id);
        } elseif ($user->branch_id) {
            $branch = \App\Models\Branch::find($user->branch_id);
        }

        $locationName = 'Counter';
        if ($this->selectedParcelCounterId) {
            $locationName = ParcelQrCode::find($this->selectedParcelCounterId)->name ?? 'Parcel';
        } elseif ($this->selectedTableId) {
            // 🌟 Applying our FORMAT TABLE HELPER HERE for Bill Print 🌟
            $rawNum = RestaurantTable::find($this->selectedTableId)->table_number ?? '';
            $locationName = $this->formatTableNumber($rawNum); // Results in Table-01
        }

        $brandName = $branch ? $branch->name : ($restaurant->name ?? 'Restaurant');
        $gstIn = ($branch && $branch->gst_no) ? $branch->gst_no : ($restaurant->gst_no ?? '-');
        $phone = ($branch && $branch->phone) ? $branch->phone : ($restaurant->phone ?? '012345678910');
        $address = ($branch && $branch->address) ? $branch->address : ($restaurant->address ?? '-');

        $orders = $viewData['tableOrders']->whereIn('status', ['placed', 'accepted', 'partial_accepted', 'preparing', 'ready', 'served']);

        $itemsHtml = '';
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $displayQty = $item->confirmed_qty ?? $item->quantity;
                if ($displayQty <= 0)
                    continue;
                $rate = $item->unit_price;
                $amount = $rate * $displayQty;
                $itemsHtml .= "<tr>
                    <td style='padding: 5px 0; border-bottom: 1px dashed #000;'>{$item->item_name}</td>
                    <td style='padding: 5px 0; border-bottom: 1px dashed #000; text-align: center;'>{$displayQty}</td>
                    <td style='padding: 5px 0; border-bottom: 1px dashed #000; text-align: right;'>" . number_format($rate, 2) . "</td>
                    <td style='padding: 5px 0; border-bottom: 1px dashed #000; text-align: right;'>" . number_format($amount, 2) . "</td>
                </tr>";
            }
        }

        $qrHtml = '';
        if (!empty($upiId)) {
            $upiUrl = "upi://pay?pa={$upiId}&pn=" . urlencode($brandName) . "&am={$pendingPayment->amount}&cu=INR&tr={$pendingPayment->transaction_reference}";
            $qrSvg = (string) \SimpleSoftwareIO\QrCode\Facades\QrCode::size(120)->margin(0)->generate($upiUrl);

            $qrHtml = "
            <div style='text-align: center; margin-top: 15px; padding-top: 10px; border-top: 1px dashed #000;'>
                <div style='font-size: 11px; font-weight: bold; margin-bottom: 8px;'>SCAN TO PAY VIA UPI: ₹" . number_format($pendingPayment->amount, 0) . "</div>
                <div style='display: block; margin: 0 auto;'>{$qrSvg}</div>
            </div>";
        }

        $taxable = max(0, (float) $pendingPayment->subtotal - (float) $pendingPayment->discount_amount);
        $taxPercent = ($taxable > 0 && (float) $pendingPayment->tax_amount > 0) ? round(((float) $pendingPayment->tax_amount / $taxable) * 100, 1) : 0;

        $breakdownHtml = "<div>Sub Total: ₹" . number_format($pendingPayment->subtotal, 2) . "</div>";
        if ((float) $pendingPayment->discount_amount > 0) {
            $breakdownHtml .= "<div style='font-size: 13px; margin-top: 3px;'>Discount: -₹" . number_format($pendingPayment->discount_amount, 2) . "</div>";
        }
        if ((float) $pendingPayment->tax_amount > 0) {
            $halfTax = (float) $pendingPayment->tax_amount / 2;
            $halfPercent = round($taxPercent / 2, 2);
            $breakdownHtml .= "<div style='font-size: 12px; margin-top: 3px;'>CGST " . ($halfPercent > 0 ? "({$halfPercent}%)" : "") . ": ₹" . number_format($halfTax, 2) . "</div>";
            $breakdownHtml .= "<div style='font-size: 12px; margin-top: 2px;'>SGST " . ($halfPercent > 0 ? "({$halfPercent}%)" : "") . ": ₹" . number_format($halfTax, 2) . "</div>";
            $breakdownHtml .= "<div style='font-size: 12px; margin-top: 2px; font-weight: bold;'>Total GST " . ($taxPercent > 0 ? "({$taxPercent}%)" : "") . ": ₹" . number_format($pendingPayment->tax_amount, 2) . "</div>";
        }
        if ((float) $pendingPayment->extra_charges > 0) {
            $breakdownHtml .= "<div style='font-size: 13px; margin-top: 3px;'>Extra Charges: +₹" . number_format($pendingPayment->extra_charges, 2) . "</div>";
        }
        $breakdownHtml .= "<div style='font-size: 18px; margin-top: 6px; border-top: 1px dashed #000; padding-top: 4px;'>Grand Total: ₹" . number_format($pendingPayment->amount, 2) . "</div>";

        $paymentModeText = strtoupper($pendingPayment->payment_method ?: 'CASH');
        if ($paymentModeText === 'PENDING') {
            $paymentModeText = 'CASH';
        }

        $html = "
        <html><head><style>@page { margin: 0; size: 80mm auto; } body { margin: 5px; font-family: 'Courier New', Courier, monospace; font-size: 13px; color: #000; } table { width: 100%; border-collapse: collapse; }</style></head>
        <body>
            <div style='text-align:center;'>
                <h2 style='margin:0; font-size:20px; font-weight:bold; text-transform: uppercase;'>{$brandName}</h2>
                <div style='font-size: 12px; margin-top: 5px;'>{$address}</div>
                <div style='font-size: 12px; margin-top: 2px;'>MOB: {$phone}</div>
                <div style='font-size: 12px; margin-top: 2px;'>GSTIN: {$gstIn}</div>
            </div>
            <hr style='border-top:1px dashed #000; margin:10px 0;'/>
            <div style='display: flex; justify-content: space-between; font-size: 12px; font-weight: bold;'>
                <span>Bill No: {$pendingPayment->bill_number}</span>
                <span>Date: " . now()->format('d/m/Y') . "</span>
            </div>
            <div style='display: flex; justify-content: space-between; font-size: 12px; font-weight: bold; margin-top: 5px;'>
                <span>Name: {$session->customer_name}</span>
                <span>Mode: {$paymentModeText}</span>
            </div>
            <div style='font-size: 13px; font-weight: bold; margin-top: 5px;'>LOCATION: {$locationName}</div>
            <hr style='border-top:1px dashed #000; margin:10px 0;'/>
            <table>
                <thead>
                    <tr style='text-transform: uppercase; font-size: 11px; border-bottom: 1px dashed #000;'>
                        <th style='text-align:left; padding-bottom:5px;'>Description</th><th style='text-align:center; padding-bottom:5px;'>Qty</th><th style='text-align:right; padding-bottom:5px;'>Rate</th><th style='text-align:right; padding-bottom:5px;'>Amount</th>
                    </tr>
                </thead>
                <tbody>{$itemsHtml}</tbody>
            </table>
            <div style='text-align:right; margin-top: 10px; font-size: 14px; font-weight: bold;'>
                {$breakdownHtml}
            </div>

            {$qrHtml}

            <div style='text-align:center; margin-top:20px; font-size:12px; font-weight:bold;'>*** THANK YOU FOR DINING WITH US! ***</div>
        </body></html>";

        $escapedHtml = json_encode($html);

        $this->js("
            const printWindow = window.open('', '_blank', 'width=400,height=600');
            printWindow.document.write({$escapedHtml});
            printWindow.document.close();
            setTimeout(() => { printWindow.focus(); printWindow.print(); printWindow.onafterprint = () => printWindow.close(); }, 250);
        ");
    }

    public function sendBillToCustomer(): void
    {
        $viewData = $this->getViewData();

        if (!$this->selectedSessionId)
            return;

        $sessionCol = $this->selectedParcelCounterId ? 'parcel_qr_session_id' : 'qr_session_id';
        if (\App\Models\Invoice::where($sessionCol, $this->selectedSessionId)->exists()) {
            Notification::make()->title('Invoice already generated for this session.')->warning()->send();
            return;
        }

        $session = $this->getActiveSession();
        if (!$session)
            return;

        $orders = $viewData['tableOrders']->whereIn('status', ['placed', 'accepted', 'partial_accepted', 'preparing', 'ready', 'served']);
        if ($orders->isEmpty())
            return;

        $subtotal = $orders->sum(fn($order) => $order->confirmed_total ?? $order->total_amount);
        $amountAlreadyPaid = $orders->where('payment_status', 'paid')->sum(fn($order) => $order->confirmed_total ?? $order->total_amount);

        $taxable = max(0, $subtotal - (float) $this->discountAmount);
        $taxAmt = $taxable * ((float) $this->taxPercentage / 100);
        $extra = (float) $this->extraCharges;
        $invoiceGrandTotal = $taxable + $taxAmt + $extra;
        $amountDue = max(0, round($invoiceGrandTotal - $amountAlreadyPaid, 2));
        $billStatus = $amountDue > 0 ? 'pending' : 'paid';
        $latestOrderId = $orders->pluck('id')->last();
        $transactionRef = 'ORD' . $latestOrderId . '_' . Str::random(10);

        try {
            DB::transaction(function () use ($latestOrderId, $subtotal, $taxAmt, $extra, $amountDue, $billStatus, $transactionRef, $session) {

                $existingPayment = Payment::where('order_id', $latestOrderId)->first();
                $billNumber = $existingPayment->bill_number ?? $this->generateBillNumber();

                $payment = Payment::updateOrCreate(
                    ['order_id' => $latestOrderId],
                    [
                        'restaurant_id' => auth()->user()->restaurant_id,
                        'branch_id' => auth()->user()->branch_id,
                        'subtotal' => $subtotal,
                        'discount_amount' => $this->discountAmount,
                        'tax_amount' => $taxAmt,
                        'extra_charges' => $extra,
                        'amount' => $amountDue,
                        'status' => $billStatus,
                        'payment_method' => $billStatus === 'paid' ? 'online' : 'cash',
                        'transaction_reference' => $transactionRef,
                        'bill_number' => $billNumber,
                        'paid_at' => $billStatus === 'paid' ? now() : null,
                    ]
                );

                $upiId = auth()->user()->branch_id ? \App\Models\Branch::find(auth()->user()->branch_id)->upi_id : \App\Models\Restaurant::find(auth()->user()->restaurant_id)->upi_id;

                $paymentPayload = array_merge($payment->toArray(), [
                    'upi_id' => $upiId,
                    'merchant_category_code' => '5812',
                    'tax_percentage' => (float) $this->taxPercentage,
                ]);

                if ($billStatus === 'paid') {
                    $invoice = \App\Services\Orders\InvoiceService::generateInvoice($session, $payment);
                    $session->update(['status' => 'completed']);
                    $paymentPayload['invoice_number'] = $invoice->invoice_number;
                }

                event(new \App\Events\BillGenerated($session->id, $paymentPayload));
            });

            if ($billStatus === 'paid') {
                Notification::make()->title('Bill Auto-Settled & Invoice Generated')->success()->send();
                $this->closeReceiptModal();
            } else {
                Notification::make()->title('Final Bill Sent!')->success()->send();
            }
        } catch (\Exception $e) {
            Notification::make()->title('Error generating bill')->body($e->getMessage())->danger()->send();
        }
    }

    public function confirmPayment(): void
    {
        $this->mountAction('confirmPaymentAction');
    }

    public function confirmPaymentAction(): Action
    {
        return Action::make('confirmPaymentAction')
            ->label('Confirm Payment')
            ->modalHeading(function () {
                $session = $this->getActiveSession();
                $custName = $session?->customer_name ?? 'Guest';
                $viewData = $this->getViewData();
                $pendingPayment = $viewData['pendingPayment'];
                $amount = $pendingPayment ? '₹' . number_format($pendingPayment->amount, 2) : '';
                return "Confirm Bill Payment — {$amount} ({$custName})";
            })
            ->modalSubheading('Select the payment method received from the customer to settle the bill and generate the invoice.')
            ->modalWidth(MaxWidth::Medium)
            ->modalSubmitActionLabel('Confirm Payment & Settle')
            ->modalIcon('heroicon-o-check-circle')
            ->modalIconColor('success')
            ->fillForm(function (array $arguments) {
                $sessionId = $arguments['session_id'] ?? $this->selectedSessionId;
                if ($sessionId && $this->selectedSessionId !== $sessionId) {
                    $this->selectedSessionId = $sessionId;
                }

                $session = $this->getActiveSession();
                $viewData = $this->getViewData();
                $pendingPayment = $viewData['pendingPayment'];

                $defaultMethod = 'cash';
                if ($pendingPayment && in_array(strtolower($pendingPayment->payment_method), ['cash', 'upi', 'card', 'other'])) {
                    $defaultMethod = strtolower($pendingPayment->payment_method);
                }

                return [
                    'payment_method' => $defaultMethod,
                    'transaction_reference' => $pendingPayment?->transaction_reference ?? '',
                ];
            })
            ->form([
                Placeholder::make('bill_overview')
                    ->hiddenLabel()
                    ->content(function () {
                        $session = $this->getActiveSession();
                        $viewData = $this->getViewData();
                        $pendingPayment = $viewData['pendingPayment'];
                        $amount = $pendingPayment ? number_format($pendingPayment->amount, 2) : '0.00';
                        $subtotal = $pendingPayment ? number_format($pendingPayment->subtotal, 2) : '0.00';
                        $tax = $pendingPayment ? number_format($pendingPayment->tax_amount, 2) : '0.00';
                        $discount = $pendingPayment ? number_format($pendingPayment->discount_amount, 2) : '0.00';
                        $custName = e($session?->customer_name ?? 'Guest');
                        $billNo = $pendingPayment?->bill_number ?? '-';

                        $locationName = 'Counter';
                        if ($this->selectedParcelCounterId) {
                            $locationName = \App\Models\ParcelQrCode::find($this->selectedParcelCounterId)->name ?? 'Parcel';
                        } elseif ($this->selectedTableId) {
                            $rawNum = \App\Models\RestaurantTable::find($this->selectedTableId)->table_number ?? '';
                            $locationName = $this->formatTableNumber($rawNum);
                        }

                        $choiceBadge = '';
                        if ($pendingPayment && $pendingPayment->payment_method === 'upi') {
                            $choiceBadge = '<span style="display:inline-flex; align-items:center; gap:4px; background:#dbeafe; color:#1e40af; border:1px solid #bfdbfe; padding:2px 8px; border-radius:9999px; font-size:11px; font-weight:700;">📱 Customer chose UPI</span>';
                        } elseif ($pendingPayment && $pendingPayment->payment_method === 'cash') {
                            $choiceBadge = '<span style="display:inline-flex; align-items:center; gap:4px; background:#fef3c7; color:#92400e; border:1px solid #fde68a; padding:2px 8px; border-radius:9999px; font-size:11px; font-weight:700;">💵 Customer chose Cash</span>';
                        }

                        return new \Illuminate\Support\HtmlString("
                            <div style='background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 14px 16px; margin-bottom: 6px;'>
                                <div style='display:flex; justify-content:space-between; align-items:flex-start; margin-bottom: 10px;'>
                                    <div>
                                        <div style='display:flex; align-items:center; gap:8px;'>
                                            <span style='background:#1e3a8a; color:#ffffff; padding:3px 10px; border-radius:8px; font-size:11px; font-weight:800; letter-spacing:0.02em;'>
                                                {$locationName}
                                            </span>
                                            <span style='color:#64748b; font-size:12px; font-weight:600;'>Bill #{$billNo}</span>
                                            {$choiceBadge}
                                        </div>
                                        <div style='font-size:14px; font-weight:700; color:#1e293b; margin-top:6px; display:flex; align-items:center; gap:6px;'>
                                            <span style='color:#64748b;'>👤 Customer:</span>
                                            <strong style='color:#0f172a;'>{$custName}</strong>
                                        </div>
                                    </div>
                                    <div style='text-align:right;'>
                                        <span style='font-size:10px; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.05em; display:block;'>Total Due</span>
                                        <span style='font-size:24px; font-weight:900; color:#ea580c; font-family:monospace; line-height:1;'>₹{$amount}</span>
                                    </div>
                                </div>
                                <div style='display:flex; justify-content:space-between; align-items:center; padding-top:8px; border-top:1px dashed #cbd5e1; font-size:12px; color:#475569;'>
                                    <div style='display:flex; gap:12px; font-size:11px;'>
                                        <span>Subtotal: <strong>₹{$subtotal}</strong></span>
                                        " . ((float) $discount > 0 ? "<span style='color:#16a34a;'>Discount: <strong>-₹{$discount}</strong></span>" : "") . "
                                        " . ((float) $tax > 0 ? "<span>Tax/GST: <strong>₹{$tax}</strong></span>" : "") . "
                                    </div>
                                </div>
                            </div>
                        ");
                    }),

                ToggleButtons::make('payment_method')
                    ->label('Select Settlement Method')
                    ->options([
                        'cash' => 'Cash',
                        'upi' => 'UPI / QR',
                        'card' => 'Card Swipe',
                        'other' => 'Other / Comp',
                    ])
                    ->icons([
                        'cash' => 'heroicon-m-banknotes',
                        'upi' => 'heroicon-m-qr-code',
                        'card' => 'heroicon-m-credit-card',
                        'other' => 'heroicon-m-sparkles',
                    ])
                    ->colors([
                        'cash' => 'warning',
                        'upi' => 'success',
                        'card' => 'info',
                        'other' => 'gray',
                    ])
                    ->columns(2)
                    ->gridDirection('row')
                    ->default('cash')
                    ->required(),

                TextInput::make('transaction_reference')
                    ->label('Transaction Reference / Note (Optional)')
                    ->placeholder('e.g. UTR / Card Auth Code / Cheque No. / Note')
                    ->prefixIcon('heroicon-m-hashtag')
                    ->maxLength(255)
                    ->nullable(),
            ])
            ->action(function (array $data, array $arguments) {
                $sessionId = $arguments['session_id'] ?? $this->selectedSessionId;
                if ($sessionId && $this->selectedSessionId !== $sessionId) {
                    $this->selectedSessionId = $sessionId;
                }

                $session = $this->getActiveSession();
                if (!$session) {
                    Notification::make()->title('Active customer session not found.')->danger()->send();
                    return;
                }

                $sessionCol = $this->selectedParcelCounterId ? 'parcel_qr_session_id' : 'qr_session_id';
                if (\App\Models\Invoice::where($sessionCol, $session->id)->exists()) {
                    Notification::make()->title('Invoice already generated for this session.')->warning()->send();
                    $this->closeReceiptModal();
                    return;
                }

                $orderIds = Order::where($sessionCol, $session->id)->pluck('id');
                $pendingPayment = Payment::whereIn('order_id', $orderIds)->where('status', 'pending')->latest()->first();

                if (!$pendingPayment) {
                    $viewData = $this->getViewData();
                    $pendingPayment = $viewData['pendingPayment'];
                }

                if (!$pendingPayment) {
                    Notification::make()->title('No pending bill found to confirm.')->warning()->send();
                    return;
                }

                $method = $data['payment_method'] ?? 'cash';
                $txRef = !empty($data['transaction_reference']) ? trim($data['transaction_reference']) : ($pendingPayment->transaction_reference ?? ('TXN-' . strtoupper(Str::random(8))));

                try {
                    DB::transaction(function () use ($pendingPayment, $session, $method, $txRef, $sessionCol, &$paymentPayload) {
                        $pendingPayment->update([
                            'status' => 'paid',
                            'paid_at' => now(),
                            'payment_method' => $method,
                            'transaction_reference' => $txRef,
                        ]);

                        Order::where($sessionCol, $session->id)->update(['payment_status' => 'paid']);

                        $invoice = \App\Services\Orders\InvoiceService::generateInvoice($session, $pendingPayment);
                        $session->update(['status' => 'completed']);

                        $upiId = auth()->user()->branch_id ? \App\Models\Branch::find(auth()->user()->branch_id)->upi_id : \App\Models\Restaurant::find(auth()->user()->restaurant_id)->upi_id;

                        $paymentPayload = array_merge($pendingPayment->toArray(), [
                            'upi_id' => $upiId,
                            'merchant_category_code' => '5812',
                            'invoice_number' => $invoice->invoice_number,
                        ]);
                    });

                    event(new \App\Events\BillGenerated($session->id, $paymentPayload));
                    
                    $methodLabel = strtoupper($method);
                    Notification::make()->title("Payment Confirmed ({$methodLabel}) & Invoice Generated")->success()->send();
                    $this->closeReceiptModal();
                } catch (\Exception $e) {
                    Notification::make()->title('Invoice Generation Failed')->body($e->getMessage())->danger()->send();
                }
            });
    }

    public function openPlaceOrder($tableId = null, $sessionId = null, $counterId = null, $roomId = null): void
    {
        $this->placeOrderTableId = $tableId ?? $this->selectedTableId;
        $this->placeOrderCounterId = $counterId ?? $this->selectedParcelCounterId;
        $this->placeOrderRoomId = $roomId ?? $this->selectedRoomId;
        $this->placeOrderSessionId = $sessionId ?? $this->selectedSessionId;

        $activeSession = null;
        if ($this->placeOrderSessionId) {
            if ($this->placeOrderCounterId) {
                $activeSession = ParcelQrSession::find($this->placeOrderSessionId);
            } elseif ($this->placeOrderRoomId) {
                $activeSession = RoomSession::find($this->placeOrderSessionId);
            } else {
                $activeSession = QrSession::find($this->placeOrderSessionId);
            }
        }

        // Default to Manager for orders placed from the Manager Dashboard
        $this->placeOrderCustomerName = 'Manager';

        $this->placeOrderPaymentStatus = 'pending';
        $this->placeOrderNotes = '';
        $this->placeOrderPriority = 'normal';
        $this->placeOrderCategory = 'all';
        $this->placeOrderSearch = '';
        $this->placeOrderVegFilter = 'all';
        $this->placeOrderCart = [];
        $this->isPlaceOrderOpen = true;
    }

    public function closePlaceOrder(): void
    {
        $this->isPlaceOrderOpen = false;
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
        $customerName = trim($this->placeOrderCustomerName);

        if (!$this->placeOrderSessionId) {
            if ($this->placeOrderTableId) {
                $table = RestaurantTable::where('restaurant_id', $user->restaurant_id)->findOrFail($this->placeOrderTableId);
                if (empty($customerName)) {
                    $customerName = 'Guest (' . $this->formatTableNumber($table->table_number) . ')';
                }

                $session = QrSession::create([
                    'restaurant_id' => $table->restaurant_id,
                    'branch_id' => $table->branch_id,
                    'restaurant_table_id' => $table->id,
                    'customer_name' => $customerName,
                    'session_token' => Str::uuid()->toString(),
                    'is_primary' => true,
                    'join_status' => 'active',
                    'is_active' => true,
                    'host_session_id' => null,
                    'expires_at' => now()->addHours(4),
                ]);

                if ($table->status !== 'occupied') {
                    $table->update(['status' => 'occupied']);
                    event(new \App\Events\TableStatusUpdated($table->id, 'occupied', $table->restaurant_id));
                }

                $this->placeOrderSessionId = $session->id;
                if (!$this->selectedTableId) {
                    $this->selectedTableId = $table->id;
                }
                if (!$this->selectedSessionId) {
                    $this->selectedSessionId = $session->id;
                }
            } elseif ($this->placeOrderCounterId) {
                $parcelCounter = ParcelQrCode::where('restaurant_id', $user->restaurant_id)->findOrFail($this->placeOrderCounterId);
                if (empty($customerName)) {
                    $customerName = 'Takeaway Customer';
                }

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
                if (!$this->selectedParcelCounterId) {
                    $this->selectedParcelCounterId = $parcelCounter->id;
                }
                if (!$this->selectedSessionId) {
                    $this->selectedSessionId = $session->id;
                }
            } else {
                Notification::make()->title('Please select a table or parcel counter first.')->danger()->send();
                return;
            }
        } else {
            // Keep the active table/room session owner intact. The specific order being placed
            // by the manager will be tagged with $customerName (defaulting to 'Manager').
        }

        if (empty($customerName)) {
            $customerName = 'Manager';
        }

        $validatedItems = [];
        $outOfStockItemNames = [];

        DB::transaction(function () use (&$validatedItems, &$outOfStockItemNames) {
            foreach ($this->placeOrderCart as $cartItem) {
                $menuItem = MenuItem::where('id', $cartItem['id'])->lockForUpdate()->first();
                if ($menuItem && $menuItem->track_stock && $menuItem->stock_quantity !== null) {
                    if ($menuItem->stock_quantity <= 0) {
                        $outOfStockItemNames[] = $menuItem->name;
                        continue;
                    } elseif ($menuItem->stock_quantity < $cartItem['quantity']) {
                        $cartItem['quantity'] = $menuItem->stock_quantity;
                    }
                }
                $validatedItems[] = $cartItem;
            }
        });

        if (!empty($outOfStockItemNames)) {
            Notification::make()->title('Some Items Sold Out!')->body(implode(', ', $outOfStockItemNames))->warning()->send();
        }

        if (empty($validatedItems)) {
            return;
        }

        $totalAmount = collect($validatedItems)->sum(fn($i) => $i['unit_price'] * $i['quantity']);

        if ($this->placeOrderCounterId) {
            $orderData = ['parcel_qr_session_id' => $this->placeOrderSessionId, 'service_type' => 'parcel'];
        } elseif ($this->placeOrderRoomId) {
            $orderData = ['room_session_id' => $this->placeOrderSessionId, 'service_type' => 'room_service'];
        } else {
            $orderData = ['restaurant_table_id' => $this->placeOrderTableId, 'qr_session_id' => $this->placeOrderSessionId, 'service_type' => 'dine_in'];
        }

        $paymentStatus = match ($this->placeOrderPaymentStatus) {
            'paid_upi', 'paid_card', 'paid_cash', 'paid' => 'paid',
            default => 'pending',
        };

        $orderData = array_merge($orderData, [
            'restaurant_id' => $user->restaurant_id,
            'branch_id' => $user->branch_id,
            'customer_name' => $customerName,
            'notes' => $this->placeOrderNotes,
            'total_amount' => $totalAmount,
            'confirmed_total' => $totalAmount,
            'status' => 'accepted',
            'payment_status' => $paymentStatus,
        ]);

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

        KitchenQueue::firstOrCreate(['order_id' => $order->id], ['current_status' => 'placed', 'priority' => $priorityVal]);
        OrderStatusUpdated::dispatch($order->fresh(['items.menuItem']));
        
        Notification::make()->title("KOT #{$order->id} Sent to Kitchen for {$customerName}!")->success()->send();

        $this->closePlaceOrder();
    }

    public function placeOrderAction(): Action
    {
        return Action::make('placeOrderAction')
            ->label('Place Order')
            ->action(function (array $arguments) {
                $this->openPlaceOrder(
                    $arguments['table_id'] ?? $this->selectedTableId,
                    $arguments['session_id'] ?? $this->selectedSessionId,
                    $arguments['counter_id'] ?? $this->selectedParcelCounterId,
                    $arguments['room_id'] ?? $this->selectedRoomId
                );
            });
    }

    public function editOrderAction(): Action
    {
        return Action::make('editOrderAction')
            ->label('Edit Order')
            ->modalHeading(fn(array $arguments) => 'Edit Order #' . ($arguments['orderId'] ?? ''))
            ->modalWidth(MaxWidth::TwoExtraLarge)
            ->form([
                TextInput::make('customer_name')
                    ->label('Customer Name')
                    ->required(),
                Repeater::make('items')->schema([
                    Hidden::make('id'),
                    Select::make('menu_item_id')
                        ->label('Menu Item')
                        ->options(function () {
                            $user = auth()->user();
                            $branch = $user->branch_id ? \App\Models\Branch::find($user->branch_id) : null;
                            return MenuItem::where('restaurant_id', $user->restaurant_id)
                                ->when(
                                    $user->branch_id,
                                    function ($q) use ($branch, $user) {
                                        if ($branch && !$branch->inherit_restaurant_menu) {
                                            return $q->where('branch_id', $user->branch_id);
                                        }
                                        return $q->where(fn($q2) => $q2->where('branch_id', $user->branch_id)->orWhereNull('branch_id'));
                                    },
                                    fn($q) => $q->whereNull('branch_id')
                                )
                                ->where('is_available', true)
                                ->get()
                                ->mapWithKeys(fn($item) => [$item->id => "{$item->name} (₹" . number_format($item->price, 0) . ")"]);
                        })
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn($state, callable $set) => $set('unit_price', MenuItem::find($state)?->price ?? 0)),
                    TextInput::make('quantity')->numeric()->minValue(1)->required(),
                    Hidden::make('unit_price'),
                    TextInput::make('notes')->nullable(),
                ])->columns(2)->addActionLabel('Add Item'),
            ])
            ->fillForm(function (array $arguments) {
                $order = Order::with('items')->find($arguments['orderId']);
                if (!$order)
                    return [];
                return [
                    'customer_name' => $order->customer_name,
                    'items' => $order->items->map(fn($item) => ['id' => $item->id, 'menu_item_id' => $item->menu_item_id, 'quantity' => $item->quantity, 'unit_price' => $item->unit_price, 'notes' => $item->notes])->toArray(),
                ];
            })
            ->action(function (array $data, array $arguments) {
                $order = Order::find($arguments['orderId']);
                if (!$order)
                    return;

                $totalAmount = 0;
                $existingItemIds = [];

                foreach ($data['items'] as $itemData) {
                    $totalPrice = $itemData['unit_price'] * $itemData['quantity'];
                    $totalAmount += $totalPrice;
                    $menuItem = MenuItem::find($itemData['menu_item_id']);

                    if (!empty($itemData['id'])) {
                        $orderItem = $order->items()->find($itemData['id']);
                        if ($orderItem) {
                            $orderItem->update([
                                'menu_item_id' => $itemData['menu_item_id'],
                                'item_name' => $menuItem ? $menuItem->name : 'Custom Item',
                                'quantity' => $itemData['quantity'],
                                'confirmed_qty' => $itemData['quantity'],
                                'requested_qty' => $itemData['quantity'],
                                'unit_price' => $itemData['unit_price'],
                                'total_price' => $totalPrice,
                                'notes' => $itemData['notes'] ?? null,
                            ]);
                            $existingItemIds[] = $orderItem->id;
                        }
                    } else {
                        $newItem = $order->items()->create([
                            'menu_item_id' => $itemData['menu_item_id'],
                            'item_name' => $menuItem ? $menuItem->name : 'Custom Item',
                            'quantity' => $itemData['quantity'],
                            'confirmed_qty' => $itemData['quantity'],
                            'requested_qty' => $itemData['quantity'],
                            'unit_price' => $itemData['unit_price'],
                            'total_price' => $totalPrice,
                            'notes' => $itemData['notes'] ?? null,
                        ]);
                        $existingItemIds[] = $newItem->id;
                    }
                }

                $order->items()->whereNotIn('id', $existingItemIds)->delete();
                $order->update([
                    'customer_name' => $data['customer_name'] ?? $order->customer_name,
                    'total_amount' => $totalAmount,
                    'confirmed_total' => $totalAmount,
                ]);

                OrderStatusUpdated::dispatch($order->fresh(['items.menuItem']));
                Notification::make()->title('Order updated.')->success()->send();
            });
    }

    public function toggleReservation($tableId): void
    {
        $user = auth()->user();
        $table = RestaurantTable::where('restaurant_id', $user->restaurant_id)->findOrFail($tableId);

        if (QrSession::where('restaurant_table_id', $table->id)->where('is_active', true)->count() > 0) {
            Notification::make()->title('Table is occupied')->danger()->send();
            return;
        }

        $table->update(['status' => $table->status === 'reserved' ? 'available' : 'reserved']);
        
        $displayNum = $this->formatTableNumber($table->table_number);
        Notification::make()->title("{$displayNum} status updated")->success()->send();
        
        $this->closeReceiptModal();
    }

    public function cleanTable($tableId): void
    {
        $user = auth()->user();
        $table = RestaurantTable::where('restaurant_id', $user->restaurant_id)->findOrFail($tableId);
        $activeSessions = QrSession::where('restaurant_table_id', $table->id)->where('is_active', true)->get();

        foreach ($activeSessions as $session) {
            $session->update(['is_active' => false]);
            event(new \App\Events\SessionEnded($session->id, $table->id));
        }

        $table->update(['status' => 'available']);
        event(new \App\Events\TableStatusUpdated($table->id, 'available', $table->restaurant_id));
        
        $displayNum = $this->formatTableNumber($table->table_number);
        Notification::make()->title("{$displayNum} Cleaned")->success()->send();
        
        $this->closeReceiptModal();
    }

    public function cleanDineInSession($sessionId, bool $includeGuests = true): void
    {
        $user = auth()->user();
        $session = QrSession::where('restaurant_id', $user->restaurant_id)->find($sessionId);
        if (!$session) {
            Notification::make()->title('Session not found.')->danger()->send();
            return;
        }

        $table = $session->restaurantTable ?? RestaurantTable::find($session->restaurant_table_id);
        $tableId = $table?->id;
        $customerName = $session->customer_name ?? 'Guest';

        $sessionsToClose = collect([$session]);
        if ($includeGuests && ($session->is_primary || empty($session->host_session_id))) {
            // If this is a host, also close their active joined guests
            $guests = QrSession::where('host_session_id', $session->id)->where('is_active', true)->get();
            $sessionsToClose = $sessionsToClose->merge($guests);
        }

        foreach ($sessionsToClose as $s) {
            $s->update(['is_active' => false, 'status' => 'completed']);
            Order::where('qr_session_id', $s->id)
                ->whereIn('status', ['placed', 'accepted', 'partial_accepted', 'preparing', 'ready'])
                ->update(['status' => 'served']);
            event(new \App\Events\SessionEnded($s->id, $tableId));
        }

        // Check remaining active sessions on this table
        $remainingSessions = $tableId
            ? QrSession::where('restaurant_table_id', $tableId)->where('is_active', true)->get()
            : collect();

        $displayNum = $table ? $this->formatTableNumber($table->table_number) : 'Table';

        if ($remainingSessions->count() === 0) {
            if ($table) {
                $table->update(['status' => 'available']);
                event(new \App\Events\TableStatusUpdated($table->id, 'available', $table->restaurant_id));
            }
            Notification::make()->title("All diners vacated. {$displayNum} is now Available.")->success()->send();
            $this->closeReceiptModal();
        } else {
            // Other sessions still dining on this table!
            $remainingCount = $remainingSessions->count();
            $nextSession = $remainingSessions->sortByDesc('is_primary')->first();
            $this->selectedSessionId = $nextSession?->id;

            Notification::make()
                ->title("Session for '{$customerName}' ended.")
                ->body("{$remainingCount} guest(s) still dining at {$displayNum}.")
                ->success()
                ->send();
        }
    }

    public function cleanParcelSession($sessionId): void
    {
        $user = auth()->user();
        $session = ParcelQrSession::where('restaurant_id', $user->restaurant_id)->find($sessionId);
        if (!$session)
            return;

        $session->update(['status' => 'completed', 'is_active' => false]);
        Order::where('parcel_qr_session_id', $session->id)->whereIn('status', ['placed', 'accepted', 'partial_accepted', 'preparing', 'ready'])->update(['status' => 'served']);
        event(new \App\Events\SessionEnded($session->id, null));

        Notification::make()->title("Parcel Session Completed & Cleared")->success()->send();
        if ($this->selectedSessionId === $sessionId) {
            $this->selectedSessionId = null;
        }
    }

    public function updateStatus($orderId, $status)
    {
        $user = auth()->user();
        $order = Order::with('items')->where('restaurant_id', $user->restaurant_id)->findOrFail($orderId);
        $oldStatus = $order->status;
        $stockNotes = [];
        $wasPartial = false;

        if ($status === 'accepted') {
            DB::transaction(function () use ($order, &$wasPartial, &$stockNotes) {
                $newTotal = 0;
                foreach ($order->items as $orderItem) {
                    if (!$orderItem->menu_item_id)
                        continue;
                    $menuItem = MenuItem::where('id', $orderItem->menu_item_id)->lockForUpdate()->first();

                    if (!$menuItem || !$menuItem->track_stock || $menuItem->stock_quantity === null) {
                        $newTotal += $orderItem->total_price;
                        $orderItem->update(['confirmed_qty' => $orderItem->quantity]);
                        continue;
                    }

                    $available = (int) max(0, $menuItem->stock_quantity);
                    $requested = (int) $orderItem->quantity;

                    if ($available <= 0) {
                        $stockNotes[] = '"' . $menuItem->name . '" was out of stock.';
                        $orderItem->update(['quantity' => 0, 'confirmed_qty' => 0, 'total_price' => 0]);
                        $wasPartial = true;
                    } elseif ($available < $requested) {
                        $newQty = $available;
                        $newTotalPrice = $orderItem->unit_price * $newQty;
                        $stockNotes[] = '"' . $menuItem->name . '": ordered ' . $requested . ', only ' . $available . ' available.';

                        $menuItem->decrement('stock_quantity', $newQty);
                        if ($menuItem->fresh()->stock_quantity <= 0) {
                            $menuItem->update(['is_available' => false]);
                        }

                        $orderItem->update(['quantity' => $newQty, 'confirmed_qty' => $newQty, 'total_price' => $newTotalPrice]);
                        $newTotal += $newTotalPrice;
                        $wasPartial = true;
                    } else {
                        $menuItem->decrement('stock_quantity', $requested);
                        if ($menuItem->fresh()->stock_quantity <= 0) {
                            $menuItem->update(['is_available' => false]);
                        }

                        $orderItem->update(['confirmed_qty' => $requested]);
                        $newTotal += $orderItem->total_price;
                    }
                }
                $order->update(['total_amount' => $newTotal, 'confirmed_total' => $newTotal, 'stock_note' => $wasPartial ? 'Adjusted: ' . implode(' | ', $stockNotes) : null]);

                if ($order->restaurant && $order->restaurant->has_detailed_inventory) {
                    $inventoryWarnings = InventoryService::deductForOrder($order);
                    if (!empty($inventoryWarnings)) {
                        $stockNotes = array_merge($stockNotes, $inventoryWarnings);
                    }
                }
            });

            $order->update(['status' => $wasPartial ? 'partial_accepted' : 'accepted']);
            KitchenQueue::firstOrCreate(['order_id' => $order->id], ['current_status' => 'placed', 'priority' => 0]);

            Notification::make()
                ->title($wasPartial ? "Order #{$orderId} Partially Accepted ⚠️" : "Order #{$orderId} Accepted ✅")
                ->body($wasPartial ? 'Some items were out of stock.' : 'Order ready for preparation.')
                ->success()
                ->send();

        } else {
            $order->update(['status' => $status]);
            if (in_array($status, ['preparing', 'ready', 'served'])) {
                KitchenQueue::where('order_id', $order->id)->update(['current_status' => $status]);
            }

            if ($status === 'preparing') {
                Notification::make()->title("Order #{$orderId} is Preparing 🍳")->success()->send();
            } elseif ($status === 'ready') {
                Notification::make()->title("Order #{$orderId} is Ready 🛎️")->success()->send();
            } elseif ($status === 'served') {
                Notification::make()->title("Order #{$orderId} Delivered ✔️")->success()->send();
            } elseif ($status === 'rejected') {
                Notification::make()->title("Order #{$orderId} Rejected ❌")->danger()->send();
            }
        }

        OrderStatusLog::create(['order_id' => $order->id, 'from_status' => $oldStatus, 'to_status' => $order->fresh()->status, 'changed_by' => $user->id]);
        OrderStatusUpdated::dispatch($order->fresh(['items.menuItem']));
        event(new \App\Events\StockUpdated($order->restaurant_id));
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $restaurantId = $user->restaurant_id;
        $branchId = $user->branch_id;
        $branch = $branchId ? \App\Models\Branch::find($branchId) : null;
        $hasRoomsFacility = $branch ? (bool) $branch->is_rooms_facility : ((bool) ($user->restaurant->is_rooms_facility ?? false));

        $data['upiId'] = $branch ? ($branch->upi_id ?? ($user->restaurant->upi_id ?? '')) : ($user->restaurant->upi_id ?? '');

        $isAllInOne = $branch ? (bool) $branch->is_all_in_one_cafe : ((bool) ($user->restaurant->is_all_in_one_cafe ?? false));
        $data['isAllInOne'] = $isAllInOne;

        $data['restaurantName'] = $branch ? $branch->name : ($user->restaurant->name ?? 'Restaurant');

        $activeStatuses = $isAllInOne ? ['placed', 'accepted', 'partial_accepted', 'preparing', 'ready'] : ['placed'];

        $incomingTableOrders = Order::where('restaurant_id', $restaurantId)->when($branchId, fn($q) => $q->where('branch_id', $branchId), fn($q) => $q->whereNull('branch_id'))
            ->whereIn('status', $activeStatuses)->where(fn($q) => $q->where('service_type', 'dine_in')->orWhereNull('service_type'))
            ->with(['items.menuItem.category', 'restaurantTable'])->orderBy('created_at', 'asc')->get();

        $incomingParcelOrders = Order::where('restaurant_id', $restaurantId)->when($branchId, fn($q) => $q->where('branch_id', $branchId), fn($q) => $q->whereNull('branch_id'))
            ->where('service_type', 'parcel')->whereIn('status', $activeStatuses)
            ->with(['items.menuItem', 'parcelQrSession.parcelQrCode'])->orderBy('created_at', 'asc')->get();

        $incomingRoomOrders = Order::where('restaurant_id', $restaurantId)->when($branchId, fn($q) => $q->where('branch_id', $branchId), fn($q) => $q->whereNull('branch_id'))
            ->where('service_type', 'room_service')->whereIn('status', $activeStatuses)
            ->with(['items.menuItem.category', 'roomSession.room'])->orderBy('created_at', 'asc')->get();

        $data['incomingTableOrders'] = $incomingTableOrders;
        $data['parcelOrders'] = $incomingParcelOrders;
        $data['incomingRoomOrders'] = $incomingRoomOrders;
        $data['hasRoomsFacility'] = $hasRoomsFacility;

        if ($this->currentTab === 'tables') {
            $tablesQuery = RestaurantTable::where('restaurant_id', $restaurantId);
            if ($branchId)
                $tablesQuery->where('branch_id', $branchId);
            else
                $tablesQuery->whereNull('branch_id');

            $rawTables = $tablesQuery->with(['qrSessions' => fn($q) => $q->where('is_active', true)])
                ->withCount(['qrSessions as active_sessions_count' => fn($q) => $q->where('is_active', true)])->get()
                ->map(function ($table) {
                    $activeSessionIds = $table->qrSessions->pluck('id')->toArray();
                    $orders = Order::whereIn('qr_session_id', $activeSessionIds)->whereIn('status', ['placed', 'accepted', 'partial_accepted', 'preparing', 'ready', 'served'])->get();
                    $table->live_subtotal = $orders->sum(fn($o) => $o->confirmed_total ?? $o->total_amount);
                    $table->live_due = max(0, $table->live_subtotal - $orders->where('payment_status', 'paid')->sum(fn($o) => $o->confirmed_total ?? $o->total_amount));
                    $table->live_orders_count = $orders->count();
                    $table->pending_payment = Payment::whereIn('order_id', $orders->pluck('id'))->where('status', 'pending')->latest()->first();
                    $table->is_bill_pending = ($table->pending_payment !== null);

                    $primaryDiner = $table->qrSessions->sortByDesc('is_primary')->first();
                    $table->primary_guest_name = $primaryDiner?->customer_name ?? null;
                    $table->seated_at = $primaryDiner?->created_at ?? null;

                    return $table;
                });

            $totalTables = $rawTables->count();
            $data['totalTablesCount'] = $totalTables;
            $data['occupiedCount'] = $rawTables->filter(fn($t) => $t->active_sessions_count > 0 && !$t->is_bill_pending)->count();
            $data['availableCount'] = $rawTables->filter(fn($t) => $t->active_sessions_count === 0 && ($t->status ?? 'available') !== 'cleaning' && ($t->status ?? 'available') !== 'reserved')->count();
            $data['billPendingCount'] = $rawTables->filter(fn($t) => $t->active_sessions_count > 0 && $t->is_bill_pending)->count();
            $data['cleaningCount'] = $rawTables->where('status', 'cleaning')->count();
            $data['occupancyRate'] = $totalTables > 0 ? round(($data['occupiedCount'] / $totalTables) * 100) : 0;

            // Shift revenue (today's paid orders)
            $todayPaidOrders = Order::where('restaurant_id', $restaurantId)
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId), fn($q) => $q->whereNull('branch_id'))
                ->where('payment_status', 'paid')
                ->whereDate('created_at', today())
                ->get();
            $data['shiftRevenue'] = $todayPaidOrders->sum(fn($o) => $o->confirmed_total ?? $o->total_amount);
            $data['shiftPaidCount'] = $todayPaidOrders->count();

            // Apply filter if selected
            $filteredTables = $rawTables;
            if ($this->tableFilter === 'occupied') {
                $filteredTables = $filteredTables->filter(fn($t) => $t->active_sessions_count > 0 && !$t->is_bill_pending);
            } elseif ($this->tableFilter === 'available') {
                $filteredTables = $filteredTables->filter(fn($t) => $t->active_sessions_count === 0 && ($t->status ?? 'available') !== 'cleaning' && ($t->status ?? 'available') !== 'reserved');
            } elseif ($this->tableFilter === 'bill_pending') {
                $filteredTables = $filteredTables->filter(fn($t) => $t->active_sessions_count > 0 && $t->is_bill_pending);
            } elseif ($this->tableFilter === 'cleaning') {
                $filteredTables = $filteredTables->where('status', 'cleaning');
            }

            // Apply search query if present
            if (!empty($this->searchQuery)) {
                $q = strtolower(trim($this->searchQuery));
                $filteredTables = $filteredTables->filter(function($t) use ($q) {
                    return str_contains(strtolower($t->table_number), $q) ||
                           str_contains(strtolower($t->primary_guest_name ?? ''), $q);
                });
            }

            $data['tables'] = $filteredTables->sortByDesc(fn($t) => $t->active_sessions_count > 0 ? 2 : ((($t->status ?? '') === 'reserved') ? 1 : 0))->values();

            $parcelQuery = ParcelQrCode::where('restaurant_id', $restaurantId)->where('is_active', true);
            if ($branchId)
                $parcelQuery->where('branch_id', $branchId);
            else
                $parcelQuery->whereNull('branch_id');

            $data['parcelCounters'] = $parcelQuery->with(['sessions' => fn($q) => $q->where('status', 'active')])
                ->withCount(['sessions as active_sessions_count' => fn($q) => $q->where('status', 'active')])->get()
                ->map(function ($counter) {
                    $activeSessionIds = $counter->sessions->pluck('id')->toArray();
                    $orders = Order::whereIn('parcel_qr_session_id', $activeSessionIds)->whereIn('status', ['placed', 'accepted', 'partial_accepted', 'preparing', 'ready'])->get();
                    $counter->live_subtotal = $orders->sum(fn($o) => $o->confirmed_total ?? $o->total_amount);
                    $counter->live_due = max(0, $counter->live_subtotal - $orders->where('payment_status', 'paid')->sum(fn($o) => $o->confirmed_total ?? $o->total_amount));
                    $counter->live_orders_count = $orders->count();
                    $counter->pending_payment = Payment::whereIn('order_id', $orders->pluck('id'))->where('status', 'pending')->latest()->first();
                    return $counter;
                })->values();

            $data['totalTables'] = $rawTables->count() + ParcelQrCode::where('restaurant_id', $restaurantId)->count();
            $data['activeTables'] = $rawTables->where('active_sessions_count', '>', 0)->count() + $data['parcelCounters']->where('active_sessions_count', '>', 0)->count();
            $data['occupancyRate'] = $data['totalTables'] > 0 ? round(($data['activeTables'] / $data['totalTables']) * 100) : 0;
            $data['activeSessions'] = $rawTables->sum('active_sessions_count') + $data['parcelCounters']->sum('active_sessions_count');
        } else {
            $roomsQuery = Room::where('restaurant_id', $restaurantId);
            if ($branchId)
                $roomsQuery->where('branch_id', $branchId);
            else
                $roomsQuery->whereNull('branch_id');

            $data['rooms'] = $roomsQuery->with('activeSession')->get()->map(function ($room) {
                if ($room->activeSession) {
                    $orders = Order::where('room_session_id', $room->activeSession->id)->whereIn('status', ['placed', 'accepted', 'preparing', 'ready', 'served'])->get();
                    $room->live_due = max(0, $orders->sum('total_amount') - $orders->where('payment_status', 'paid')->sum('total_amount'));
                    $room->live_orders_count = $orders->count();
                    $room->pending_payment = Payment::whereIn('order_id', $orders->pluck('id'))->where('status', 'pending')->latest()->first();
                }
                return $room;
            });
            $data['totalRooms'] = $data['rooms']->count();
            $data['occupiedRooms'] = $data['rooms']->where('status', 'occupied')->count();
            $data['occupancyRateRooms'] = $data['totalRooms'] > 0 ? round(($data['occupiedRooms'] / $data['totalRooms']) * 100) : 0;
        }

        $data['selectedEntityData'] = null;
        $data['activeDinersList'] = collect();
        $data['tableOrders'] = collect();
        $data['pendingPayment'] = null;

        if ($this->selectedTableId) {
            $data['selectedEntityData'] = RestaurantTable::find($this->selectedTableId);
            $activeSessions = QrSession::where('restaurant_table_id', $this->selectedTableId)
                ->where('is_active', true)
                ->orderBy('created_at', 'asc')
                ->get();
            $data['activeDinersList'] = $activeSessions;

            if ($this->selectedSessionId && !$activeSessions->contains('id', $this->selectedSessionId)) {
                $this->selectedSessionId = $activeSessions->first()?->id;
            }
            if (!$this->selectedSessionId && $activeSessions->isNotEmpty()) {
                $this->selectedSessionId = $activeSessions->first()->id;
            }

            // Identify hosts: primary, or null host_session_id, or orphan (host not in activeSessions)
            $hosts = $activeSessions->filter(function($s) use ($activeSessions) {
                return $s->is_primary || empty($s->host_session_id) || !$activeSessions->contains('id', $s->host_session_id);
            });

            $hostParties = [];
            $partyNum = 1;
            foreach ($hosts as $host) {
                $guests = $activeSessions->where('host_session_id', $host->id)->where('id', '!=', $host->id)->values();
                $partySessionIds = collect([$host->id])->merge($guests->pluck('id'))->toArray();

                $partyOrders = Order::whereIn('qr_session_id', $partySessionIds)
                    ->whereIn('status', ['placed', 'accepted', 'partial_accepted', 'preparing', 'ready', 'served'])
                    ->with('items')
                    ->get();

                $partySubtotal = $partyOrders->sum(fn($o) => $o->confirmed_total ?? $o->total_amount);
                $partyItemsCount = $partyOrders->sum(fn($o) => $o->items->sum('quantity'));

                $hostOrders = $partyOrders->where('qr_session_id', $host->id);
                $hostSubtotal = $hostOrders->sum(fn($o) => $o->confirmed_total ?? $o->total_amount);
                $hostItemsCount = $hostOrders->sum(fn($o) => $o->items->sum('quantity'));

                $guestsWithStats = $guests->map(function($g) use ($partyOrders) {
                    $gOrders = $partyOrders->where('qr_session_id', $g->id);
                    $g->orders_count = $gOrders->count();
                    $g->subtotal = $gOrders->sum(fn($o) => $o->confirmed_total ?? $o->total_amount);
                    $g->items_count = $gOrders->sum(fn($o) => $o->items->sum('quantity'));
                    return $g;
                });

                $hostParties[] = [
                    'party_number' => $partyNum++,
                    'host' => $host,
                    'host_subtotal' => $hostSubtotal,
                    'host_orders_count' => $hostOrders->count(),
                    'host_items_count' => $hostItemsCount,
                    'guests' => $guestsWithStats,
                    'total_diners_count' => 1 + $guests->count(),
                    'party_subtotal' => $partySubtotal,
                    'party_items_count' => $partyItemsCount,
                    'party_orders_count' => $partyOrders->count(),
                    'all_session_ids' => $partySessionIds,
                    'is_host_selected' => ($this->selectedSessionId === $host->id),
                    'is_any_member_selected' => in_array($this->selectedSessionId, $partySessionIds),
                ];
            }
            $data['hostParties'] = $hostParties;

            if ($this->selectedSessionId) {
                $groupIds = QrSession::where('host_session_id', $this->selectedSessionId)->orWhere('id', $this->selectedSessionId)->pluck('id')->toArray();
                $data['tableOrders'] = Order::with('items.menuItem.category')->whereIn('qr_session_id', $groupIds)->orderBy('created_at', 'desc')->get();
                $data['pendingPayment'] = Payment::whereIn('order_id', $data['tableOrders']->pluck('id'))->whereIn('status', ['pending', 'paid'])->latest()->first();
            }
        } elseif ($this->selectedParcelCounterId) {
            $data['selectedEntityData'] = ParcelQrCode::find($this->selectedParcelCounterId);
            $data['activeDinersList'] = ParcelQrSession::where('parcel_qr_code_id', $this->selectedParcelCounterId)
                ->where('status', 'active')
                ->orderBy('created_at', 'asc')
                ->get();
            if ($this->selectedSessionId) {
                $data['tableOrders'] = Order::with('items.menuItem.category')->where('parcel_qr_session_id', $this->selectedSessionId)->orderBy('created_at', 'desc')->get();
                $data['pendingPayment'] = Payment::whereIn('order_id', $data['tableOrders']->pluck('id'))->whereIn('status', ['pending', 'paid'])->latest()->first();
            }
        } elseif ($this->selectedRoomId) {
            $data['selectedEntityData'] = Room::with('activeSession')->find($this->selectedRoomId);
            if ($this->selectedSessionId) {
                $data['tableOrders'] = Order::with('items.menuItem.category')->where('room_session_id', $this->selectedSessionId)->orderBy('created_at', 'desc')->get();
            }
        }

        $data['activeSession'] = $this->getActiveSession();

        // 🌟 KOT Visual Menu Data (Stitch UI 1a515631d2ff4021b0346acbd2a7a53c) 🌟
        $branch = $branchId ? \App\Models\Branch::find($branchId) : null;
        $categories = Category::where('restaurant_id', $restaurantId)
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

        $menuItemsQuery = MenuItem::where('restaurant_id', $restaurantId)
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
            ->with('category');

        if ($this->placeOrderCategory !== 'all') {
            $menuItemsQuery->where('category_id', $this->placeOrderCategory);
        }

        if (!empty($this->placeOrderSearch)) {
            $q = trim($this->placeOrderSearch);
            $menuItemsQuery->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        if ($this->placeOrderVegFilter === 'veg') {
            $menuItemsQuery->where('type', 'veg');
        } elseif ($this->placeOrderVegFilter === 'non-veg') {
            $menuItemsQuery->where('type', 'non-veg');
        }

        $data['placeOrderCategories'] = $categories;
        $data['placeOrderMenuItems'] = $menuItemsQuery->orderBy('name', 'asc')->get();
        $data['totalMenuItemsCount'] = MenuItem::where('restaurant_id', $restaurantId)
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

        return $data;
    }
}