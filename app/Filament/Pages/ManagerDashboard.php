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
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ManagerDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-command-line';
    protected static string $view = 'filament.pages.manager-dashboard';
    protected static ?string $navigationLabel = 'Manager Dashboard';
    protected static ?string $title = 'Manager Dashboard Control';
    protected static ?int $navigationSort = 1;

    public $currentTab = 'tables'; 

    public $selectedTableId = null;
    public $selectedParcelCounterId = null;
    public $selectedRoomId = null;
    public $selectedSessionId = null;

    public $discountAmount = 0;
    public $taxPercentage = 0;
    public $extraCharges = 0;

    public function mount(): void
    {
        $this->checkLowStockAlerts();
    }

    public function switchTab($tab): void
    {
        $this->currentTab = $tab;
        $this->closeReceiptModal();
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
            "echo-private:restaurant.{$restaurantId},.OrderCancelled" => '$refresh',
            "echo-private:restaurant.{$restaurantId},.GuestJoinRequested" => '$refresh',
            "echo-private:restaurant.{$restaurantId}.alerts,.TableStatusUpdated" => '$refresh',
            "echo-private:restaurant.{$restaurantId}.alerts,.WaiterCalled" => 'notifyWaiterCalled',
            "echo-private:restaurant.{$restaurantId}.alerts,.BillRequested" => 'notifyBillRequested',
            "echo-private:restaurant.{$restaurantId}.alerts,.PaymentMethodSelected" => 'notifyPaymentMethod',
            "echo-private:restaurant.{$restaurantId},.NewParcelOrder" => '$refresh',
        ];
    }

    public function handleOrderStatusUpdated($event)
    {
        $this->dispatch('$refresh');

        $order = $event['order'] ?? null;
        $status = $order['status'] ?? null;
        $serviceType = $order['service_type'] ?? 'dine_in';

        if ($status === 'placed') {
            if ($serviceType === 'parcel') {
                $counterName = $order['parcel_qr_session']['parcel_qr_code']['name'] ?? 'Parcel Counter';
                $this->dispatch('trigger-browser-notification', title: "🛍️ Action Required: New Parcel Order", body: "A new order was just placed at {$counterName}. Please confirm it.");
            } elseif ($serviceType === 'room_service') {
                $roomNum = $order['room_session']['room']['room_number'] ?? 'Unknown';
                $this->dispatch('trigger-browser-notification', title: "🚪 Action Required: New Room Order", body: "Room {$roomNum} just placed a new order. Please confirm it.");
            } else {
                $tableNum = $order['table_number'] ?? $order['restaurant_table_id'] ?? 'Unknown';
                $this->dispatch('trigger-browser-notification', title: "🛎️ Action Required: New Table Order", body: "Table {$tableNum} just placed a new order. Please confirm it.");
            }
        }
    }

    public function notifyWaiterCalled($event): void
    {
        $num = $event['table_number'] ?? '?';
        $customer = $event['customer_name'] ?? 'A customer';
        Notification::make()->title("Assistance Requested: {$num}")->body("{$customer} requires assistance.")->warning()->send();
        $this->dispatch('$refresh');
    }

    public function notifyBillRequested($event): void
    {
        $tableNum = $event['table_number'] ?? '?';
        $customer = $event['customer_name'] ?? 'A customer';
        $cacheKey = "bill_requested_alert_{$tableNum}";

        if (!Cache::has($cacheKey)) {
            Notification::make()->title("Bill Requested: {$tableNum}")->body("{$customer} has requested their final bill.")->warning()->persistent()->send();
            $this->dispatch('trigger-browser-notification', title: "💰 Bill Requested", body: "{$tableNum} ({$customer}) requested their bill.");
            Cache::put($cacheKey, true, now()->addSeconds(30));
        }
    }

    public function notifyPaymentMethod($event): void
    {
        $tableNum = $event['table_number'] ?? '?';
        $method = strtoupper($event['method'] ?? 'CASH');

        Notification::make()->title("Payment Update: {$tableNum}")->body("Customer selected {$method} for payment.")->info()->send();
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
        }
    }

    public function openParcelCounter($counterId): void
    {
        if ($this->selectedParcelCounterId === $counterId) {
            $this->closeReceiptModal();
        } else {
            $this->closeReceiptModal();
            $this->selectedParcelCounterId = $counterId;
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
        if (!$this->selectedSessionId) return null;

        if ($this->selectedParcelCounterId) {
            return ParcelQrSession::find($this->selectedSessionId);
        } elseif ($this->selectedRoomId) {
            return RoomSession::find($this->selectedSessionId);
        } else {
            return QrSession::find($this->selectedSessionId);
        }
    }

    // ── Original Room QR & Management Actions ──────────────────────────────────
    
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
                if(!$room) return;

                // 1. Generate unique stay token
                $token = Str::uuid()->toString();
                $restaurantSlug = Str::slug($room->restaurant->name);
                $folder = "restaurants/{$restaurantSlug}/RoomsQR";
                Storage::disk('public')->makeDirectory($folder);
                
                $path = "{$folder}/room_{$room->room_number}_stay.svg";
                $appUrl = 'https://customer.annsathi.com';
                $scanUrl = "{$appUrl}/?type=room&r={$room->restaurant_id}&t={$room->id}&token={$token}";
                // 2. Create physical QR
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
                if(!$room) return;
                
                // Delete physical QR file
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
                if($room) $room->update(['status' => 'available']);
                Notification::make()->title('Room available for next guest.')->success()->send();
            });
    }

    public function settleRoomBill()
    {
        if (!$this->selectedRoomId || !$this->selectedSessionId) return;

        $orders = Order::where('room_session_id', $this->selectedSessionId)->whereIn('status', ['placed', 'accepted', 'preparing', 'ready', 'served'])->get();
        if ($orders->isEmpty()) return;

        $subtotal = $orders->sum(fn($o) => $o->confirmed_total ?? $o->total_amount);
        $amountPaid = $orders->where('payment_status', 'paid')->sum(fn($o) => $o->confirmed_total ?? $o->total_amount);
        $amountDue = max(0, $subtotal - $amountPaid);
        $latestOrderId = $orders->pluck('id')->last();

        if ($amountDue <= 0) return;

        try {
            DB::transaction(function () use ($latestOrderId, $subtotal, $amountDue) {
                Payment::updateOrCreate(['order_id' => $latestOrderId], [
                    'restaurant_id' => auth()->user()->restaurant_id,
                    'subtotal' => $subtotal,
                    'amount' => $amountDue, 
                    'status' => 'paid',
                    'payment_method' => 'room_charge',
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
                                                    try { $url = $file->temporaryUrl(); } 
                                                    catch (\Exception $e) { $url = 'data:' . $file->getClientMimeType() . ';base64,' . base64_encode(file_get_contents($file->getRealPath())); }
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
                if (!$this->selectedRoomId) return;
                $room = Room::with('restaurant')->find($this->selectedRoomId);
                if (!$room || !$room->qr_path) return;

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

                // Base64 Background
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

                // Base64 Logo
                $logoBase64 = '';
                if ($restaurant && $restaurant->logo_path) {
                    $logoFullPath = Storage::disk('public')->path($restaurant->logo_path);
                    if (file_exists($logoFullPath)) {
                        $mime = mime_content_type($logoFullPath);
                        $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoFullPath));
                    }
                }

                // Base64 QR
                $qrPath = storage_path('app/public/' . $room->qr_path);
                $qrBase64 = file_exists($qrPath) ? 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($qrPath)) : '';

                // Build HTML
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

    // ── Table & General Actions ──────────────────────────────────────────────────
    
    public function cancelPendingBill(): void
    {
        $viewData = $this->getViewData();
        $pendingPayment = $viewData['pendingPayment'];

        if ($pendingPayment && $pendingPayment->status === 'pending') {
            $pendingPayment->delete();
            $session = $this->getActiveSession();
            if($session) {
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

        if (!$pendingPayment || !$this->selectedSessionId) {
            Notification::make()->title('No active bill found.')->danger()->send();
            return;
        }

        $session = $this->getActiveSession();
        if (!$session) return;
        
        $restaurant = auth()->user()->restaurant;
        
        $locationName = 'Counter';
        if ($this->selectedParcelCounterId) $locationName = ParcelQrCode::find($this->selectedParcelCounterId)->name ?? 'Parcel';
        elseif ($this->selectedTableId) $locationName = 'T-' . RestaurantTable::find($this->selectedTableId)->table_number;
        
        $gstIn = $restaurant->gst_no ?? '-';
        $phone = $restaurant->phone ?? '012345678910';
        $address = $restaurant->address ?? '-';

        $orders = $viewData['tableOrders']->whereIn('status', ['placed', 'accepted', 'partial_accepted', 'preparing', 'ready', 'served']);

        $itemsHtml = '';
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $displayQty = $item->confirmed_qty ?? $item->quantity;
                if ($displayQty <= 0) continue;
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

        $html = "
        <html><head><style>@page { margin: 0; size: 80mm auto; } body { margin: 5px; font-family: 'Courier New', Courier, monospace; font-size: 13px; color: #000; } table { width: 100%; border-collapse: collapse; }</style></head>
        <body>
            <div style='text-align:center;'>
                <h2 style='margin:0; font-size:20px; font-weight:bold; text-transform: uppercase;'>{$restaurant->name}</h2>
                <div style='font-size: 12px; margin-top: 5px;'>{$address}</div>
                <div style='font-size: 12px; margin-top: 2px;'>MOB: {$phone}</div>
                <div style='font-size: 12px; margin-top: 2px;'>GSTIN: {$gstIn}</div>
            </div>
            <hr style='border-top:1px dashed #000; margin:10px 0;'/>
            <div style='display: flex; justify-content: space-between; font-size: 12px; font-weight: bold;'>
                <span>Bill No: #{$pendingPayment->id}</span>
                <span>Date: " . now()->format('d/m/Y') . "</span>
            </div>
            <div style='display: flex; justify-content: space-between; font-size: 12px; font-weight: bold; margin-top: 5px;'>
                <span>Name: {$session->customer_name}</span>
                <span>Mode: " . strtoupper($pendingPayment->payment_method) . "</span>
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
                <div>Sub Total: ₹" . number_format($pendingPayment->subtotal, 2) . "</div>
                <div style='font-size: 18px; margin-top: 5px;'>Grand Total: ₹" . number_format($pendingPayment->amount, 2) . "</div>
            </div>
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
        
        if (!$this->selectedSessionId) return;

        $sessionCol = $this->selectedParcelCounterId ? 'parcel_qr_session_id' : 'qr_session_id';
        if (\App\Models\Invoice::where($sessionCol, $this->selectedSessionId)->exists()) {
            Notification::make()->title('Invoice already generated for this session.')->warning()->send();
            return;
        }

        $session = $this->getActiveSession();
        if (!$session) return;

        $orders = $viewData['tableOrders']->whereIn('status', ['placed', 'accepted', 'partial_accepted', 'preparing', 'ready', 'served']);
        if ($orders->isEmpty()) return;

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
                        'payment_method' => $billStatus === 'paid' ? 'online' : 'pending',
                        'transaction_reference' => $transactionRef,
                        'paid_at' => $billStatus === 'paid' ? now() : null,
                    ]
                );

                $upiId = auth()->user()->branch_id ? \App\Models\Branch::find(auth()->user()->branch_id)->upi_id : \App\Models\Restaurant::find(auth()->user()->restaurant_id)->upi_id;

                $paymentPayload = array_merge($payment->toArray(), ['upi_id' => $upiId, 'merchant_category_code' => '5812']);

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
        $viewData = $this->getViewData();
        $pendingPayment = $viewData['pendingPayment'];
        
        if (!$pendingPayment || !$this->selectedSessionId) return;

        $sessionCol = $this->selectedParcelCounterId ? 'parcel_qr_session_id' : 'qr_session_id';
        if (\App\Models\Invoice::where($sessionCol, $this->selectedSessionId)->exists()) {
            Notification::make()->title('Invoice already generated.')->warning()->send();
            $this->closeReceiptModal(); 
            return;
        }

        $session = $this->getActiveSession();
        if (!$session) return;

        try {
            DB::transaction(function () use ($pendingPayment, $session, &$paymentPayload) {
                $pendingPayment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'payment_method' => $pendingPayment->payment_method === 'pending' ? 'cash' : $pendingPayment->payment_method,
                ]);

                $invoice = \App\Services\Orders\InvoiceService::generateInvoice($session, $pendingPayment);
                $session->update(['status' => 'completed']);

                $upiId = auth()->user()->branch_id ? \App\Models\Branch::find(auth()->user()->branch_id)->upi_id : \App\Models\Restaurant::find(auth()->user()->restaurant_id)->upi_id;

                $paymentPayload = array_merge($pendingPayment->toArray(), [
                    'upi_id' => $upiId,
                    'merchant_category_code' => '5812',
                    'invoice_number' => $invoice->invoice_number,
                ]);
            });

            event(new \App\Events\BillGenerated($this->selectedSessionId, $paymentPayload));
            Notification::make()->title('Payment Confirmed & Invoice Generated')->success()->send();
            $this->closeReceiptModal(); 
        } catch (\Exception $e) {
            Notification::make()->title('Invoice Generation Failed')->body($e->getMessage())->danger()->send();
        }
    }

    public function placeOrderAction(): Action
    {
        return Action::make('placeOrderAction')
            ->label('Place Order')
            ->modalHeading('Place Order on Behalf of Customer')
            ->modalWidth(MaxWidth::TwoExtraLarge)
            ->form([
                Repeater::make('items')->schema([
                    Select::make('menu_item_id')->label('Menu Item')->options(MenuItem::where('restaurant_id', auth()->user()->restaurant_id)->pluck('name', 'id'))->searchable()->required()->live()->afterStateUpdated(fn($state, callable $set) => $set('unit_price', MenuItem::find($state)?->price ?? 0)),
                    TextInput::make('quantity')->numeric()->default(1)->minValue(1)->required(),
                    Hidden::make('unit_price'),
                    TextInput::make('notes')->nullable(),
                ])->columns(2)->defaultItems(1)->addActionLabel('Add Another Item'),
            ])
            ->action(function (array $data) {
                if (!$this->selectedSessionId) {
                    Notification::make()->title('No active session selected.')->danger()->send();
                    return;
                }

                $validatedItems = [];
                $outOfStockItemNames = [];

                DB::transaction(function () use ($data, &$validatedItems, &$outOfStockItemNames) {
                    foreach ($data['items'] as $item) {
                        $menuItem = MenuItem::where('id', $item['menu_item_id'])->lockForUpdate()->first();
                        if ($menuItem && $menuItem->track_stock && $menuItem->stock_quantity !== null) {
                            if ($menuItem->stock_quantity <= 0) {
                                $outOfStockItemNames[] = $menuItem->name;
                                continue;
                            } elseif ($menuItem->stock_quantity < $item['quantity']) {
                                $item['quantity'] = $menuItem->stock_quantity;
                            }
                        }
                        $validatedItems[] = $item;
                    }
                });

                if (!empty($outOfStockItemNames)) {
                    Notification::make()->title('Some Items Sold Out!')->body(implode(', ', $outOfStockItemNames))->warning()->send();
                }

                if (empty($validatedItems)) return;

                $totalAmount = collect($validatedItems)->sum(fn($i) => $i['unit_price'] * $i['quantity']);

                if ($this->selectedParcelCounterId) {
                    $orderData = ['parcel_qr_session_id' => $this->selectedSessionId, 'service_type' => 'parcel'];
                } elseif ($this->selectedRoomId) {
                    $orderData = ['room_session_id' => $this->selectedSessionId, 'service_type' => 'room_service'];
                } else {
                    $orderData = ['restaurant_table_id' => $this->selectedTableId, 'qr_session_id' => $this->selectedSessionId, 'service_type' => 'dine_in'];
                }

                $orderData = array_merge($orderData, [
                    'restaurant_id' => auth()->user()->restaurant_id,
                    'branch_id' => auth()->user()->branch_id,
                    'customer_name' => 'Manager (Dashboard)',
                    'total_amount' => $totalAmount,
                    'confirmed_total' => $totalAmount,
                    'status' => 'accepted',
                    'payment_status' => 'paid',
                ]);

                $order = Order::create($orderData);

                foreach ($validatedItems as $item) {
                    $menuItem = MenuItem::find($item['menu_item_id']);
                    if ($menuItem && $menuItem->track_stock && $menuItem->stock_quantity !== null) {
                        $menuItem->decrement('stock_quantity', $item['quantity']);
                        if ($menuItem->fresh()->stock_quantity <= 0) $menuItem->update(['is_available' => false]);
                    }
                    $order->items()->create([
                        'menu_item_id' => $item['menu_item_id'],
                        'item_name' => $menuItem ? $menuItem->name : 'Custom Item',
                        'quantity' => $item['quantity'],
                        'confirmed_qty' => $item['quantity'],
                        'requested_qty' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total_price' => $item['unit_price'] * $item['quantity'],
                        'notes' => $item['notes'] ?? null,
                    ]);
                }

                KitchenQueue::firstOrCreate(['order_id' => $order->id], ['current_status' => 'placed', 'priority' => 0]);
                OrderStatusUpdated::dispatch($order->fresh(['items.menuItem']));
                Notification::make()->title('Order placed.')->success()->send();
            });
    }

    public function editOrderAction(): Action
    {
        return Action::make('editOrderAction')
            ->label('Edit Order')
            ->modalHeading(fn(array $arguments) => 'Edit Order #' . ($arguments['orderId'] ?? ''))
            ->modalWidth(MaxWidth::TwoExtraLarge)
            ->form([
                Repeater::make('items')->schema([
                    Hidden::make('id'),
                    Select::make('menu_item_id')->label('Menu Item')->options(MenuItem::where('restaurant_id', auth()->user()->restaurant_id)->pluck('name', 'id'))->searchable()->required()->live()->afterStateUpdated(fn($state, callable $set) => $set('unit_price', MenuItem::find($state)?->price ?? 0)),
                    TextInput::make('quantity')->numeric()->minValue(1)->required(),
                    Hidden::make('unit_price'),
                    TextInput::make('notes')->nullable(),
                ])->columns(2)->addActionLabel('Add Item'),
            ])
            ->fillForm(function (array $arguments) {
                $order = Order::with('items')->find($arguments['orderId']);
                if (!$order) return [];
                return ['items' => $order->items->map(fn($item) => ['id' => $item->id, 'menu_item_id' => $item->menu_item_id, 'quantity' => $item->quantity, 'unit_price' => $item->unit_price, 'notes' => $item->notes])->toArray()];
            })
            ->action(function (array $data, array $arguments) {
                $order = Order::find($arguments['orderId']);
                if (!$order) return;

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
                                'menu_item_id' => $itemData['menu_item_id'], 'item_name' => $menuItem ? $menuItem->name : 'Custom Item', 'quantity' => $itemData['quantity'], 'confirmed_qty' => $itemData['quantity'], 'requested_qty' => $itemData['quantity'], 'unit_price' => $itemData['unit_price'], 'total_price' => $totalPrice, 'notes' => $itemData['notes'] ?? null,
                            ]);
                            $existingItemIds[] = $orderItem->id;
                        }
                    } else {
                        $newItem = $order->items()->create([
                            'menu_item_id' => $itemData['menu_item_id'], 'item_name' => $menuItem ? $menuItem->name : 'Custom Item', 'quantity' => $itemData['quantity'], 'confirmed_qty' => $itemData['quantity'], 'requested_qty' => $itemData['quantity'], 'unit_price' => $itemData['unit_price'], 'total_price' => $totalPrice, 'notes' => $itemData['notes'] ?? null,
                        ]);
                        $existingItemIds[] = $newItem->id;
                    }
                }

                $order->items()->whereNotIn('id', $existingItemIds)->delete();
                $order->update(['total_amount' => $totalAmount, 'confirmed_total' => $totalAmount]);

                OrderStatusUpdated::dispatch($order->fresh(['items.menuItem']));
                Notification::make()->title('Order updated.')->success()->send();
            });
    }

    public function toggleReservation($tableId): void
    {
        $user = auth()->user();
        $table = RestaurantTable::where('restaurant_id', $user->restaurant_id)->findOrFail($tableId);

        if (QrSession::where('restaurant_table_id', $table->id)->where('is_active', true)->count() > 0) {
            Notification::make()->title('Table is occupied')->danger()->send(); return;
        }

        $table->update(['status' => $table->status === 'reserved' ? 'available' : 'reserved']);
        Notification::make()->title("Table {$table->table_number} status updated")->success()->send();
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
        Notification::make()->title("Table {$table->table_number} Cleaned")->success()->send();
        $this->closeReceiptModal(); 
    }

    public function cleanParcelSession($sessionId): void
    {
        $user = auth()->user();
        $session = ParcelQrSession::where('restaurant_id', $user->restaurant_id)->find($sessionId);
        if (!$session) return;

        $session->update(['status' => 'completed', 'is_active' => false]);
        Order::where('parcel_qr_session_id', $session->id)->whereIn('status', ['placed', 'accepted', 'partial_accepted', 'preparing', 'ready'])->update(['status' => 'served']);
        event(new \App\Events\SessionEnded($session->id, null));
        
        Notification::make()->title("Parcel Session Completed & Cleared")->success()->send();
        if ($this->selectedSessionId === $sessionId) { $this->selectedSessionId = null; }
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
                    if (!$orderItem->menu_item_id) continue;
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
                        if ($menuItem->fresh()->stock_quantity <= 0) { $menuItem->update(['is_available' => false]); }

                        $orderItem->update(['quantity' => $newQty, 'confirmed_qty' => $newQty, 'total_price' => $newTotalPrice]);
                        $newTotal += $newTotalPrice;
                        $wasPartial = true;
                    } else {
                        $menuItem->decrement('stock_quantity', $requested);
                        if ($menuItem->fresh()->stock_quantity <= 0) { $menuItem->update(['is_available' => false]); }

                        $orderItem->update(['confirmed_qty' => $requested]);
                        $newTotal += $orderItem->total_price;
                    }
                }
                $order->update(['total_amount' => $newTotal, 'confirmed_total' => $newTotal, 'stock_note' => $wasPartial ? 'Adjusted: ' . implode(' | ', $stockNotes) : null]);
            });

            $order->update(['status' => $wasPartial ? 'partial_accepted' : 'accepted']);
            KitchenQueue::firstOrCreate(['order_id' => $order->id], ['current_status' => 'placed', 'priority' => 0]);
        } else {
            $order->update(['status' => $status]);
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
        $hasRoomsFacility = (bool) $user->restaurant->is_rooms_facility;

        $incomingTableOrders = Order::where('restaurant_id', $restaurantId)->when($branchId, fn($q) => $q->where('branch_id', $branchId), fn($q) => $q->whereNull('branch_id'))
            ->where('status', 'placed')->where(fn($q) => $q->where('service_type', 'dine_in')->orWhereNull('service_type'))
            ->with(['items.menuItem.category', 'restaurantTable'])->orderBy('created_at', 'asc')->get();

        $incomingParcelOrders = Order::where('restaurant_id', $restaurantId)->when($branchId, fn($q) => $q->where('branch_id', $branchId), fn($q) => $q->whereNull('branch_id'))
            ->where('service_type', 'parcel')->where('status', 'placed')
            ->with(['items.menuItem', 'parcelQrSession.parcelQrCode'])->orderBy('created_at', 'asc')->get();

        $incomingRoomOrders = Order::where('restaurant_id', $restaurantId)->when($branchId, fn($q) => $q->where('branch_id', $branchId), fn($q) => $q->whereNull('branch_id'))
            ->where('status', 'placed')->where('service_type', 'room_service')
            ->with(['items.menuItem.category', 'roomSession.room'])->orderBy('created_at', 'asc')->get();

        $data = [
            'incomingTableOrders' => $incomingTableOrders,
            'parcelOrders' => $incomingParcelOrders, // 👈 FIXED: Maps perfectly to Blade's parcelOrders variable
            'incomingRoomOrders' => $incomingRoomOrders,
            'hasRoomsFacility' => $hasRoomsFacility
        ];

        // ── LOAD GRID DATA BASED ON TAB ──
        if ($this->currentTab === 'tables') {
            $tablesQuery = RestaurantTable::where('restaurant_id', $restaurantId);
            if ($branchId) $tablesQuery->where('branch_id', $branchId); else $tablesQuery->whereNull('branch_id');
            
            $data['tables'] = $tablesQuery->with(['qrSessions' => fn($q) => $q->where('is_active', true)])
                ->withCount(['qrSessions as active_sessions_count' => fn($q) => $q->where('is_active', true)])->get()
                ->map(function ($table) {
                    $activeSessionIds = $table->qrSessions->pluck('id')->toArray();
                    $orders = Order::whereIn('qr_session_id', $activeSessionIds)->whereIn('status', ['placed', 'accepted', 'partial_accepted', 'preparing', 'ready', 'served'])->get();
                    $table->live_subtotal = $orders->sum(fn($o) => $o->confirmed_total ?? $o->total_amount);
                    $table->live_due = max(0, $table->live_subtotal - $orders->where('payment_status', 'paid')->sum(fn($o) => $o->confirmed_total ?? $o->total_amount));
                    $table->live_orders_count = $orders->count();
                    return $table;
                })->sortByDesc(fn($t) => $t->active_sessions_count > 0 ? 2 : ((($t->status ?? '') === 'reserved') ? 1 : 0))->values();

            $parcelQuery = ParcelQrCode::where('restaurant_id', $restaurantId)->where('is_active', true);
            if ($branchId) $parcelQuery->where('branch_id', $branchId); else $parcelQuery->whereNull('branch_id');
            
            $data['parcelCounters'] = $parcelQuery->with(['sessions' => fn($q) => $q->where('status', 'active')])
                ->withCount(['sessions as active_sessions_count' => fn($q) => $q->where('status', 'active')])->get()
                ->map(function ($counter) {
                    $activeSessionIds = $counter->sessions->pluck('id')->toArray();
                    $orders = Order::whereIn('parcel_qr_session_id', $activeSessionIds)->whereIn('status', ['placed', 'accepted', 'partial_accepted', 'preparing', 'ready'])->get();
                    $counter->live_subtotal = $orders->sum(fn($o) => $o->confirmed_total ?? $o->total_amount);
                    $counter->live_due = max(0, $counter->live_subtotal - $orders->where('payment_status', 'paid')->sum(fn($o) => $o->confirmed_total ?? $o->total_amount));
                    $counter->live_orders_count = $orders->count();
                    return $counter;
                })->values();

            $data['totalTables'] = $data['tables']->count() + ParcelQrCode::where('restaurant_id', $restaurantId)->count();
            $data['activeTables'] = $data['tables']->where('active_sessions_count', '>', 0)->count() + $data['parcelCounters']->where('active_sessions_count', '>', 0)->count();
            $data['occupancyRate'] = $data['totalTables'] > 0 ? round(($data['activeTables'] / $data['totalTables']) * 100) : 0;
            $data['activeSessions'] = $data['tables']->sum('active_sessions_count') + $data['parcelCounters']->sum('active_sessions_count');
        } else {
            $roomsQuery = Room::where('restaurant_id', $restaurantId);
            if ($branchId) $roomsQuery->where('branch_id', $branchId); else $roomsQuery->whereNull('branch_id');
            
            $data['rooms'] = $roomsQuery->with('activeSession')->get()->map(function ($room) {
                if ($room->activeSession) {
                    $orders = Order::where('room_session_id', $room->activeSession->id)->whereIn('status', ['placed', 'accepted', 'preparing', 'ready', 'served'])->get();
                    $room->live_due = max(0, $orders->sum('total_amount') - $orders->where('payment_status', 'paid')->sum('total_amount'));
                    $room->live_orders_count = $orders->count();
                }
                return $room;
            });
            $data['totalRooms'] = $data['rooms']->count();
            $data['occupiedRooms'] = $data['rooms']->where('status', 'occupied')->count();
            $data['occupancyRateRooms'] = $data['totalRooms'] > 0 ? round(($data['occupiedRooms'] / $data['totalRooms']) * 100) : 0;
        }

        // ── LOAD MODAL DATA ──
        $data['selectedEntityData'] = null;
        $data['activeDinersList'] = collect();
        $data['tableOrders'] = collect();
        $data['pendingPayment'] = null;

        if ($this->selectedTableId) {
            $data['selectedEntityData'] = RestaurantTable::find($this->selectedTableId);
            $data['activeDinersList'] = QrSession::where('restaurant_table_id', $this->selectedTableId)->where('is_active', true)->where('is_primary', true)->get();
            if ($this->selectedSessionId) {
                $groupIds = QrSession::where('host_session_id', $this->selectedSessionId)->orWhere('id', $this->selectedSessionId)->pluck('id')->toArray();
                $data['tableOrders'] = Order::with('items.menuItem.category')->whereIn('qr_session_id', $groupIds)->orderBy('created_at', 'desc')->get();
                $data['pendingPayment'] = Payment::whereIn('order_id', $data['tableOrders']->pluck('id'))->whereIn('status', ['pending', 'paid'])->latest()->first();
            }
        } elseif ($this->selectedParcelCounterId) {
            $data['selectedEntityData'] = ParcelQrCode::find($this->selectedParcelCounterId);
            $data['activeDinersList'] = ParcelQrSession::where('parcel_qr_code_id', $this->selectedParcelCounterId)->where('status', 'active')->get();
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

        return $data;
    }
}