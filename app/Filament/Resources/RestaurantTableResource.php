<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RestaurantTableResource\Pages;
use App\Filament\Resources\RestaurantTableResource\RelationManagers;
use App\Models\RestaurantTable;
use App\Models\Branch; // 👈 Import Branch model
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use App\Services\Restaurant\QrCodeService;
use Filament\Tables\Columns\ImageColumn;
use App\Services\Restaurant\QrZipService;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Layout\Split;
use Illuminate\Support\HtmlString;

class RestaurantTableResource extends Resource
{
    protected static ?string $model = RestaurantTable::class;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';
    protected static ?string $navigationLabel = 'Tables & QR Setup';
    protected static ?string $navigationGroup = 'Restaurant Table Setup';

    public static function canAccess(): bool
    {
        return auth()->check()
            && auth()->user()->restaurant_id !== null
            && in_array(auth()->user()->role->name, ['restaurant_admin', 'manager', 'branch_admin']);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery()
            ->where('restaurant_id', $user->restaurant_id);

        // 👇 FIX: Branch isolation. Main Restaurant Admin only sees branch_id NULL.
        if ($user->isRestaurantAdmin()) {
            $query->whereNull('branch_id');
        } elseif ($user->isBranchAdmin() || $user->isManager()) {
            $query->where('branch_id', $user->branch_id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // 👇 Dropdown removed as per request for Main Admin
                Forms\Components\TextInput::make('table_number')
                    ->label('Table Number')
                    ->required()
                    ->maxLength(20),

                Forms\Components\TextInput::make('seating_capacity')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->required(),

                Forms\Components\Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading(new HtmlString('
                <style>
                    /* Main Table Container */
                    .fi-ta-ctn {
                        background-color: transparent !important;
                        box-shadow: none !important;
                        border: none !important; 
                    }
                    /* Toolbars */
                    .fi-ta-header-toolbar, .fi-ta-footer {
                        background-color: transparent !important;
                        border-color: rgba(156, 163, 175, 0.2) !important;
                    }
                    /* Inner Content wrapper */
                    .fi-ta-content {
                        background-color: transparent !important;
                    }
                    /* Individual Grid Cards */
                    .fi-ta-record {
                        background-color: transparent !important;
                        border: 1px solid rgba(156, 163, 175, 0.2) !important;
                        border-radius: 16px !important;
                        box-shadow: none !important;
                        transition: all 0.2s ease;
                    }
                    /* Card Hover Effect */
                    .fi-ta-record:hover {
                        background-color: rgba(234, 88, 12, 0.05) !important;
                        border-color: rgba(234, 88, 12, 0.4) !important;
                        transform: translateY(-3px);
                        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1) !important;
                    }
                </style>
                <span style="font-size: 1.25rem; font-weight: 800;">Tables & QR Codes</span>
            '))
            ->contentGrid([
                'md' => 2,
                'xl' => 4,
                '2xl' => 5,
            ])
            ->columns([
                Stack::make([
                    Split::make([
                        Stack::make([
                            Tables\Columns\TextColumn::make('table_number')
                                ->label('Table No')
                                ->weight(\Filament\Support\Enums\FontWeight::Bold)
                                ->size('lg'),

                            // Show branch name ONLY if it's not the Main Restaurant admin (optional)
                            Tables\Columns\TextColumn::make('branch.name')
                                ->size('sm')
                                ->color('gray')
                                ->visible(fn() => auth()->user()->isRestaurantAdmin() && false), // Hidden as per request
                        ]),
                        Tables\Columns\IconColumn::make('is_active')
                            ->boolean()
                            ->grow(false),
                    ]),

                    ImageColumn::make('qr_path')
                        ->label('QR')
                        ->disk('public')
                        ->height(200)
                        ->width('100%')
                        ->extraImgAttributes([
                            'style' => 'background-color: #e8c08d; padding: 2rem; border-radius: 0.5rem; object-fit: contain; margin-top: 1rem; margin-bottom: 0.5rem;',
                        ])
                        ->visibility('public'),
                ])->space(3),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->button()
                    ->outlined()
                    ->color('warning'),

                Tables\Actions\DeleteAction::make()
                    ->button()
                    ->outlined()
                    ->color('danger')
                    ->visible(fn() => in_array(auth()->user()->role->name, ['restaurant_admin', 'branch_admin'])),
            ])
            ->headerActions([
                Tables\Actions\Action::make('download_all_qr')
                    ->label('Download All Table QRs')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->color('gray')
                    ->outlined()
                    ->action(function () {
                        $user = auth()->user();
                        $restaurant = $user->restaurant;
                        // Passing the user object to the service to isolate QRs if needed
                        $zipPath = app(QrZipService::class)->createForRestaurant($restaurant, $user);

                        return response()
                            ->download($zipPath)
                            ->deleteFileAfterSend(true);
                    }),

                Tables\Actions\Action::make('generateTables')
                    ->label('Generate Tables')
                    ->icon('heroicon-o-qr-code')
                    ->color('warning')
                    ->form(function () {
                        // 👇 Form updated: Select Branch removed for all
                        return [
                            \Filament\Forms\Components\TextInput::make('total_tables')
                                ->numeric()
                                ->minValue(1)
                                ->required(),

                            \Filament\Forms\Components\TextInput::make('seating_capacity')
                                ->numeric()
                                ->default(1),
                        ];
                    })
                    ->action(function (array $data) {
                        $user = auth()->user();
                        $restaurant = $user->restaurant;

                        // 1. Identify current branch scope
                        $branchId = ($user->isBranchAdmin() || $user->isManager()) ? $user->branch_id : null;

                        // 2. Count tables specifically for THIS branch or MAIN restaurant
                        $startQuery = \App\Models\RestaurantTable::where('restaurant_id', $restaurant->id);

                        if ($branchId) {
                            $startQuery->where('branch_id', $branchId);
                        } else {
                            // Agar branch admin nahi hai, toh main restaurant ke null branch wale count karo
                            $startQuery->whereNull('branch_id');
                        }

                        $currentCount = $startQuery->count();

                        $qrService = app(\App\Services\Restaurant\QrCodeService::class);

                        // 3. Generate new tables with unique prefix per branch scope
                        for ($i = 1; $i <= $data['total_tables']; $i++) {
                            $table = \App\Models\RestaurantTable::create([
                                'restaurant_id' => $restaurant->id,
                                'branch_id' => $branchId,
                                'table_number' => 'T' . ($currentCount + $i),
                                'seating_capacity' => $data['seating_capacity'],
                            ]);

                            $qrService->generate($table);
                        }
                    })
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRestaurantTables::route('/'),
            'edit' => Pages\EditRestaurantTable::route('/{record}/edit'),
        ];
    }
}