<?php

namespace App\Filament\Pages;

use App\Models\Room;
use App\Models\Order;
use App\Models\RoomSession;
use App\Models\Payment;
use App\Models\KitchenQueue;
use App\Models\OrderStatusLog;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class RoomManagerDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home-modern';
    protected static string $view = 'filament.pages.room-manager-dashboard';
    protected static ?string $navigationLabel = 'Room Service Dashboard';
    protected static ?string $title = 'Hotel Room Management';
    protected static ?string $navigationGroup = 'Hospitality Modules';

    public $selectedRoomId = null;

    public function getListeners(): array
    {
        $user = auth()->user();
        if (!$user || !$user->restaurant_id) return [];
        $restaurantId = $user->restaurant_id;

        return [
            "echo-private:restaurant.{$restaurantId},.OrderStatusUpdated" => '$refresh',
            "echo-private:restaurant.{$restaurantId},.OrderCancelled" => '$refresh',
            "echo-private:restaurant.{$restaurantId},.GuestJoinRequested" => '$refresh',
            "echo-private:restaurant.{$restaurantId}.alerts,.WaiterCalled" => 'notifyWaiterCalled',
            "echo-private:restaurant.{$restaurantId}.alerts,.BillRequested" => 'notifyBillRequested',
        ];
    }

    public function notifyWaiterCalled($event)
    {
        $roomNum = $event['table_number'] ?? '?';
        $customer = $event['customer_name'] ?? 'A customer';
        Notification::make()->title("Room Service Requested: Room {$roomNum}")->body("{$customer} requires assistance.")->warning()->send();
        $this->dispatch('$refresh');
    }

    public function notifyBillRequested($event)
    {
        $roomNum = $event['table_number'] ?? '?';
        $customer = $event['customer_name'] ?? 'A customer';
        Notification::make()->title("Bill Requested: Room {$roomNum}")->body("{$customer} wants to settle their bill.")->warning()->send();
        $this->dispatch('$refresh');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) auth()->user()?->restaurant?->is_rooms_facility;
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->restaurant_id !== null 
            && in_array($user->role->name ?? '', ['restaurant_admin', 'branch_admin', 'manager']);
    }

    public function openRoom($roomId)
    {
        $this->selectedRoomId = ($this->selectedRoomId === $roomId) ? null : $roomId;
    }

    public function acceptOrder($orderId)
    {
        $user = auth()->user();
        $order = Order::where('restaurant_id', $user->restaurant_id)->findOrFail($orderId);
        $oldStatus = $order->status;
        $order->update(['status' => 'accepted']);
        KitchenQueue::firstOrCreate(['order_id' => $order->id], ['current_status' => 'placed', 'priority' => 0]);
        OrderStatusLog::create(['order_id' => $order->id, 'from_status' => $oldStatus, 'to_status' => 'accepted', 'changed_by' => $user->id]);
        \App\Events\OrderStatusUpdated::dispatch($order);
        Notification::make()->title('Room Service Sent to Kitchen')->success()->send();
    }

    public function rejectOrder($orderId)
    {
        $user = auth()->user();
        $order = Order::where('restaurant_id', $user->restaurant_id)->findOrFail($orderId);
        $oldStatus = $order->status;
        $order->update(['status' => 'rejected']);
        OrderStatusLog::create(['order_id' => $order->id, 'from_status' => $oldStatus, 'to_status' => 'rejected', 'changed_by' => $user->id]);
        \App\Events\OrderStatusUpdated::dispatch($order);
        Notification::make()->title('Room Service Order Rejected')->danger()->send();
    }

    public function updateStatus($orderId, $status)
    {
        $user = auth()->user();
        $order = Order::where('restaurant_id', $user->restaurant_id)->findOrFail($orderId);
        $oldStatus = $order->status;
        $order->update(['status' => $status]);
        if ($status === 'accepted') {
            KitchenQueue::firstOrCreate(['order_id' => $order->id], ['current_status' => 'placed', 'priority' => 0]);
        }
        OrderStatusLog::create(['order_id' => $order->id, 'from_status' => $oldStatus, 'to_status' => $status, 'changed_by' => $user->id]);
        \App\Events\OrderStatusUpdated::dispatch($order);
    }

    public function settleRoomBill()
    {
        $viewData = $this->getViewData();
        $activeSessionId = $viewData['activeSessionId'];
        if (!$activeSessionId) return;

        $session = RoomSession::find($activeSessionId);
        $orders = collect($viewData['roomOrders'])->whereIn('status', ['placed', 'accepted', 'preparing', 'ready', 'served']);
        if ($orders->isEmpty()) return;

        $subtotal = $orders->sum('total_amount');
        $amountPaid = $orders->where('payment_status', 'paid')->sum('total_amount');
        $amountDue = max(0, $subtotal - $amountPaid);
        $latestOrderId = $orders->pluck('id')->last();

        try {
            DB::transaction(function () use ($latestOrderId, $subtotal, $amountDue, $session) {
                Payment::updateOrCreate(['order_id' => $latestOrderId], [
                    'restaurant_id' => auth()->user()->restaurant_id,
                    'subtotal' => $subtotal,
                    'amount' => $amountDue, 
                    'status' => 'paid',
                    'payment_method' => 'room_charge',
                    'paid_at' => now(),
                ]);
                Order::where('room_session_id', $session->id)->update(['payment_status' => 'paid']);
                $session->update(['is_billed' => true]);
            });
            Notification::make()->title('Room Service Bill Settled.')->success()->send();
        } catch (\Exception $e) {
            Notification::make()->title('Error settling bill')->body($e->getMessage())->danger()->send();
        }
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

    protected function getViewData(): array
    {
        $user = auth()->user();
        $restaurantId = $user->restaurant_id;
        $roomsQuery = Room::where('restaurant_id', $restaurantId);
        if ($user->branch_id) $roomsQuery->where('branch_id', $user->branch_id);

        $rooms = $roomsQuery->with('activeSession')->get()->map(function ($room) {
            if ($room->activeSession) {
                $orders = Order::where('room_session_id', $room->activeSession->id)
                    ->whereIn('status', ['placed', 'accepted', 'preparing', 'ready', 'served'])->get();
                $room->live_due = max(0, $orders->sum('total_amount') - $orders->where('payment_status', 'paid')->sum('total_amount'));
                $room->live_orders_count = $orders->count();
            }
            return $room;
        });

        $totalRooms = $rooms->count();
        $occupiedRooms = $rooms->where('status', 'occupied')->count();
        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0;

        $selectedRoomData = null;
        $roomOrders = collect();
        $activeSessionId = null;

        if ($this->selectedRoomId) {
            $selectedRoomData = $rooms->firstWhere('id', $this->selectedRoomId);
            if ($selectedRoomData && $selectedRoomData->activeSession) {
                $activeSessionId = $selectedRoomData->activeSession->id;
                $roomOrders = Order::with('items.menuItem.category')->where('room_session_id', $activeSessionId)->orderBy('created_at', 'desc')->get();
            }
        }

        $incomingOrders = Order::where('restaurant_id', $restaurantId)->whereNotNull('room_session_id')->where('status', 'placed')->with(['items.menuItem.category', 'roomSession.room'])->orderBy('created_at', 'asc')->get();
        $pendingJoinRequests = RoomSession::whereIn('room_id', $rooms->pluck('id'))->where('status', 'pending')->with('room')->orderBy('created_at', 'asc')->get();

        return compact('rooms', 'totalRooms', 'occupiedRooms', 'occupancyRate', 'incomingOrders', 'pendingJoinRequests', 'selectedRoomData', 'roomOrders', 'activeSessionId');
    }
}