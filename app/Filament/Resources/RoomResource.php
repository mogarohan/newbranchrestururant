<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoomResource\Pages;
use App\Models\Room;
use App\Models\RoomSession;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Layout\Split;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection; // 👈 IMPORTANT FOR BULK DELETE

class RoomResource extends Resource
{
    protected static ?string $model = Room::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'Rooms & QR Setup';
    protected static ?string $navigationGroup = 'Hospitality Modules';

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

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery()->where('restaurant_id', $user->restaurant_id);

        if ($user->isRestaurantAdmin()) {
            $query->whereNull('branch_id');
        } else {
            $query->where('branch_id', $user->branch_id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('room_number')->required()->maxLength(20),
                Forms\Components\TextInput::make('room_name')->maxLength(255),
                Forms\Components\TextInput::make('max_guests')->numeric()->default(2)->minValue(1)->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        $bgImageUrl = asset('images/bg.png');

        return $table
            ->heading(new HtmlString('
                <style>
                    html, body, .fi-layout, .fi-main, .fi-page { background-color: transparent !important; background: transparent !important; }
                    body::before { content: ""; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-image: url("' . $bgImageUrl . '") !important; background-size: cover !important; background-position: center !important; background-attachment: fixed !important; opacity: 0.15 !important; z-index: -999 !important; pointer-events: none; }
                    .fi-ta-ctn { background: rgba(255, 255, 255, 0.55) !important; backdrop-filter: blur(18px) saturate(150%) !important; -webkit-backdrop-filter: blur(18px) saturate(150%) !important; border: 1.5px solid #000000 !important; border-radius: 1.25rem !important; box-shadow: 0 8px 32px rgba(42, 71, 149, 0.10) !important; overflow: hidden !important; }
                    .dark .fi-ta-ctn { background: rgba(15, 15, 20, 0.75) !important; border-color: #000 !important; }
                    .fi-ta-record { background: rgba(255, 255, 255, 0.40) !important; border: 1.5px solid #000000 !important; border-radius: 12px !important; transition: all 0.3s ease !important; cursor: pointer !important; margin: 0.5rem !important; }
                    .fi-ta-record:hover { transform: translateY(-4px) !important; border-color: #f16b3f !important; box-shadow: 0 10px 24px rgba(241, 107, 63, 0.18) !important; background: rgba(255, 255, 255, 0.60) !important; }
                    .fi-ta-header-toolbar { background: rgba(252, 236, 221, 0.45) !important; border-bottom: 1.5px solid #000000 !important; padding: 1rem !important; }
                </style>
                <span style="font-size: 1.5rem; font-weight: 900; color: #2a4795; font-family: Poppins, sans-serif; letter-spacing: 0.02em;">Rooms & QR Setup Dashboard</span>
            '))
            ->contentGrid(['md' => 2, 'xl' => 4, '2xl' => 5])
            ->columns([
                Stack::make([
                    Split::make([
                        Stack::make([
                            Tables\Columns\TextColumn::make('room_number')
                                ->formatStateUsing(function ($state, $record) {
                                    $statusColor = $record->status === 'occupied' ? '#ef4444' : ($record->status === 'available' ? '#10b981' : '#f59e0b');
                                    return new HtmlString("
                                        <div style='display: flex; flex-direction: column;'>
                                            <span style='font-size: 1.4rem; font-weight: 900; color: #2a4795;'>Room {$state}</span>
                                            <span style='font-size: 0.7rem; font-weight: 800; color: {$statusColor}; background: {$statusColor}15; padding: 3px 8px; border-radius: 99px; width: fit-content; border: 1px solid {$statusColor}40; margin-top: 4px;'>
                                                🛏️ {$record->max_guests} Guests | " . strtoupper($record->status) . "
                                            </span>
                                        </div>
                                    ");
                                }),
                        ]),
                    ]),
                    
                    ImageColumn::make('qr_path')
                        ->label('Active QR')
                        ->disk('public')
                        ->height(120)
                        ->width('100%')
                        ->placeholder('No Active Guest')
                        ->extraImgAttributes([
                            'style' => 'object-fit: contain; margin-top: 1rem; margin-bottom: 0.5rem; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1)); border: 1.5px solid #000; border-radius: 8px; background: white;',
                        ])
                        ->visibility('public'),
                ])->space(3),
            ])
            ->actions([
                Tables\Actions\Action::make('check_in')
                    ->iconButton()
                    ->icon('heroicon-o-key')
                    ->color('success')
                    ->tooltip('Check In Guest')
                    ->hidden(fn (Room $record) => $record->status !== 'available')
                    ->form([
                        Forms\Components\TextInput::make('guest_name')->required(),
                        Forms\Components\DateTimePicker::make('check_out_at')->default(now()->addDays(1)->setTime(11, 0))->required(),
                    ])
                    ->action(function (Room $record, array $data) {
                        $token = Str::uuid()->toString();
                        $restaurantSlug = Str::slug($record->restaurant->name);
                        $folder = "restaurants/{$restaurantSlug}/RoomsQR/active";
                        
                        Storage::disk('public')->makeDirectory($folder);
                        $filename = "room_{$record->room_number}_" . time() . ".svg";
                        $path = "{$folder}/{$filename}";

                        $appUrl = 'https://customer.annsathi.com';
                        $scanUrl = "{$appUrl}/?type=room&r={$record->restaurant_id}&t={$record->id}&token={$token}";
                        $qrImage = QrCode::format('svg')->size(300)->margin(1)->generate($scanUrl);
                        Storage::disk('public')->put($path, $qrImage);

                        $session = RoomSession::create([
                            'restaurant_id' => $record->restaurant_id,
                            'branch_id' => $record->branch_id,
                            'room_id' => $record->id,
                            'guest_name' => $data['guest_name'],
                            'session_token' => $token,
                            'check_in_at' => now(),
                            'check_out_at' => $data['check_out_at'],
                            'status' => 'active',
                        ]);

                        $record->update([
                            'status' => 'occupied',
                            'guest_name' => $data['guest_name'],
                            'check_in_at' => now(),
                            'check_out_at' => $data['check_out_at'],
                            'active_room_session_id' => $session->id,
                            'qr_token' => $token,
                            'qr_path' => $path,
                        ]);

                        Notification::make()->title('Guest Checked In & QR Generated')->success()->send();
                    }),

                Tables\Actions\Action::make('checkout')
                    ->iconButton()
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('danger')
                    ->tooltip('Checkout Room')
                    ->requiresConfirmation()
                    ->hidden(fn (Room $record) => $record->status !== 'occupied')
                    ->action(function (Room $record) {
                        if ($record->qr_path) {
                            Storage::disk('public')->delete($record->qr_path);
                        }

                        if ($record->activeSession) {
                            $record->activeSession->update(['status' => 'checked_out']);
                        }

                        $record->update([
                            'status' => 'cleaning',
                            'guest_name' => null,
                            'check_in_at' => null,
                            'check_out_at' => null,
                            'active_room_session_id' => null,
                            'qr_token' => null,
                            'qr_path' => null,
                        ]);

                        Notification::make()->title('Checked out & QR Deleted')->success()->send();
                    }),

                Tables\Actions\Action::make('mark_clean')
                    ->iconButton()
                    ->icon('heroicon-o-sparkles')
                    ->color('info')
                    ->tooltip('Mark as Clean')
                    ->hidden(fn (Room $record) => $record->status !== 'cleaning')
                    ->action(fn (Room $record) => $record->update(['status' => 'available'])),

                Tables\Actions\EditAction::make()->iconButton(),

                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->before(function ($record) {
                        if ($record->qr_path && Storage::disk('public')->exists($record->qr_path)) {
                            Storage::disk('public')->delete($record->qr_path);
                        }
                    }),
            ])

            // 👇 2. ADDED BULK ACTIONS 👇
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->before(function (Collection $records) {
                            foreach ($records as $record) {
                                // Clear the stored SVG files before deleting the rooms
                                if ($record->qr_path && Storage::disk('public')->exists($record->qr_path)) {
                                    Storage::disk('public')->delete($record->qr_path);
                                }
                            }
                        })
                ]),
            ])

            ->headerActions([
                Tables\Actions\Action::make('generateRooms')
                    ->label('Bulk Create Rooms')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->form([
                        Forms\Components\TextInput::make('total_rooms')
                            ->label('How many rooms to create?')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        Forms\Components\TextInput::make('max_guests')
                            ->label('Guests per room')
                            ->numeric()
                            ->default(2)
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $user = auth()->user();
                        $restaurant = $user->restaurant;
                        $branchId = ($user->isBranchAdmin() || $user->isManager()) ? $user->branch_id : null;

                        // 👇 1. FIX: ENFORCE ROOM LIMITS DURING BULK CREATION 👇
                        $currentCount = Room::where('restaurant_id', $restaurant->id)->count();
                        $remainingCapacity = max(0, $restaurant->rooms_limit - $currentCount);

                        if ($data['total_rooms'] > $remainingCapacity) {
                            Notification::make()
                                ->title('Room Limit Exceeded')
                                ->body("Your current plan has a limit of {$restaurant->rooms_limit} rooms. You have {$currentCount} existing rooms and can only create {$remainingCapacity} more.")
                                ->danger()
                                ->send();
                            return; // Stop execution
                        }
                        
                        $lastRoom = Room::where('restaurant_id', $restaurant->id)
                            ->orderByDesc('id')
                            ->first();
                            
                        $lastNum = $lastRoom ? (int) filter_var($lastRoom->room_number, FILTER_SANITIZE_NUMBER_INT) : 100;

                        for ($i = 1; $i <= $data['total_rooms']; $i++) {
                            Room::create([
                                'restaurant_id' => $restaurant->id,
                                'branch_id' => $branchId,
                                'room_number' => (string)($lastNum + $i),
                                'max_guests' => $data['max_guests'],
                                'status' => 'available',
                                'qr_token' => null, 
                                'qr_path' => null,
                            ]);
                        }
                        
                        Notification::make()->title("{$data['total_rooms']} Rooms Created Successfully")->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRooms::route('/'),
        ];
    }
}