<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MenuResource\Pages;
use App\Models\MenuItem;
use App\Models\Category;
use App\Models\Branch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class MenuResource extends Resource
{
    protected static ?string $model = MenuItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Menu Dashboard';
    protected static ?string $navigationGroup = 'Menu Management';
    protected static ?int $navigationSort = 1;

    /* -----------------------------------------------------------
       PERMISSIONS
    ------------------------------------------------------------*/
    public static function canAccess(): bool
    {
        return auth()->check()
            && auth()->user()->restaurant_id !== null
            && in_array(auth()->user()->role->name, ['restaurant_admin', 'manager', 'branch_admin']);
    }

    public static function canCreate(): bool
    {
        return true; 
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return true; 
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = auth()->user();
        if ($user->branch_id === null) return $record->branch_id === null;
        return $record->branch_id === $user->branch_id;
    }

    /* -----------------------------------------------------------
       DATA ISOLATION
    ------------------------------------------------------------*/
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery()->withoutGlobalScopes();
        
        $query->where('restaurant_id', $user->restaurant_id)
              ->where(function($q) use ($user) {
                  $q->whereNull('branch_id'); 
                  if ($user->branch_id) {
                      $q->orWhere('branch_id', $user->branch_id); 
                  }
              });

        return $query;
    }

    /* -----------------------------------------------------------
       CARD GRID LAYOUT (GLASS + BLACK BORDER)
    ------------------------------------------------------------*/
    public static function table(Table $table): Table
    {
        $bgImageUrl = asset('images/bg.png');

        return $table
            ->heading(new HtmlString('
                <style>
                    /* --- 🌟 MAKE FILAMENT WRAPPERS TRANSPARENT --- */
                    html, body, .fi-layout, .fi-main, .fi-page {
                        background-color: transparent !important;
                        background: transparent !important;
                    }

                    /* --- 🌟 BACKGROUND IMAGE WITH 0.15 OPACITY --- */
                    body::before {
                        content: "";
                        position: fixed;
                        top: 0; left: 0; right: 0; bottom: 0;
                        background-image: url("' . $bgImageUrl . '") !important;
                        background-size: cover !important;
                        background-position: center !important;
                        background-attachment: fixed !important;
                        opacity: 0.15 !important;
                        z-index: -999 !important;
                        pointer-events: none;
                    }

                    /* --- 🎨 CARD BASE (GLASS + BLACK BORDER) --- */
                    .fi-ta-record {
                        border-radius: 16px !important;
                        background: rgba(255, 255, 255, 0.45) !important;
                        backdrop-filter: blur(16px) saturate(140%) !important;
                        -webkit-backdrop-filter: blur(16px) saturate(140%) !important;
                        border: 1.5px solid #000000 !important; /* BLACK BORDER */
                        box-shadow: 0 8px 32px rgba(42, 71, 149, 0.08) !important;
                        transition: all 0.3s ease !important;
                        overflow: hidden !important;
                        position: relative !important;
                    }
                    .dark .fi-ta-record { background: rgba(15, 15, 20, 0.7) !important; }
                    
                    .fi-ta-record:hover {
                        transform: translateY(-5px) !important;
                        box-shadow: 0 12px 40px rgba(42, 71, 149, 0.15) !important;
                    }

                    /* Category Badge */
                    .fi-ta-record .fi-badge {
                        color: #2a4795 !important; 
                        background-color: rgba(42, 71, 149, 0.1) !important;
                        border: 1px solid rgba(42, 71, 149, 0.2) !important;
                    }

                    /* Veg/Non-Veg Badges */
                    .type-badge-veg {
                        display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 99px;
                        font-size: 0.65rem; font-weight: 800; background: rgba(34, 197, 94, 0.15); color: #16a34a; border: 1px solid rgba(34, 197, 94, 0.3);
                        margin-top: 4px;
                    }
                    .type-badge-non-veg {
                        display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 99px;
                        font-size: 0.65rem; font-weight: 800; background: rgba(239, 68, 68, 0.15); color: #dc2626; border: 1px solid rgba(239, 68, 68, 0.3);
                        margin-top: 4px;
                    }

                    /* Price & Toggle Switch */
                    .custom-price-text { color: #2a4795 !important; font-size: 1.1rem !important; }
                    .fi-ta-record button[role="switch"][aria-checked="true"] { background-color: #f16b3f !important; }

                    /* Edit Button (Blue Style) */
                    .fi-ta-record .fi-ta-actions button:nth-of-type(1) {
                        background-color: #2a4795 !important; border: 1px solid #000000 !important; color: white !important; border-radius: 8px !important; padding: 4px 12px !important;
                    }
                    .fi-ta-record .fi-ta-actions button:nth-of-type(1):hover { background-color: #456aba !important; }

                    /* Delete Button (Red Style) */
                    .fi-ta-record .fi-ta-actions button:nth-of-type(2) {
                        color: #ef4444 !important; border: 1px solid #ef4444 !important; border-radius: 8px !important; background: transparent !important;
                    }
                    .fi-ta-record .fi-ta-actions button:nth-of-type(2):hover { background: rgba(239, 68, 68, 0.1) !important; }

                    .branch-badge {
                        position: absolute; top: 10px; right: 10px; background: #f16b3f; color: white; font-size: 0.6rem; font-weight: 900; padding: 2px 8px; border-radius: 10px; z-index: 20; border: 1px solid #000000;
                    }
                </style>
            '))
            ->contentGrid([
                'md' => 3, 
                'lg' => 4, 
                'xl' => 5, 
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\TextColumn::make('branch_id')
                        ->formatStateUsing(fn ($state) => $state ? 'Branch Item' : '')
                        ->extraAttributes(['class' => 'branch-badge'])
                        ->visible(fn ($record) => $record && $record->branch_id !== null),

                    Tables\Columns\ImageColumn::make('image_path')
                        ->height('140px') 
                        ->width('100%')
                        ->extraImgAttributes(['class' => 'object-cover w-full h-full border-b border-black']),

                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('name')
                            ->weight('black')
                            ->size('lg')
                            ->searchable(), 

                        Tables\Columns\TextColumn::make('type')
                            ->formatStateUsing(fn (string $state): HtmlString => new HtmlString(
                                $state === 'veg' 
                                    ? '<span class="type-badge-veg">● VEG</span>' 
                                    : '<span class="type-badge-non-veg">● NON-VEG</span>'
                            )),

                        Tables\Columns\TextColumn::make('category.name')
                            ->badge()
                            ->size('xs'), 

                        Tables\Columns\TextColumn::make('price')
                            ->money('INR')
                            ->weight('black')
                            ->extraAttributes(['class' => 'custom-price-text']), 

                        Tables\Columns\ToggleColumn::make('is_available')
                            ->label('Stock Status')
                            ->getStateUsing(function ($record) {
                                if (!$record) return false;
                                $user = auth()->user();
                                if ($user->branch_id !== null && $record->branch_id === null) {
                                    $status = DB::table('branch_menu_item_status')
                                        ->where('menu_item_id', $record->id)
                                        ->where('branch_id', $user->branch_id)
                                        ->first();
                                    return $status ? (bool) $status->is_available : (bool) $record->is_available;
                                }
                                return (bool) $record->is_available;
                            })
                            ->updateStateUsing(function ($record, $state) {
                                if (!$record) return;
                                $user = auth()->user();
                                if ($user->branch_id !== null && $record->branch_id === null) {
                                    DB::table('branch_menu_item_status')->updateOrInsert(
                                        ['menu_item_id' => $record->id, 'branch_id' => $user->branch_id],
                                        ['is_available' => $state, 'updated_at' => now()]
                                    );
                                } else {
                                    $record->update(['is_available' => $state]);
                                }
                            }),
                    ])->space(2)->extraAttributes(['class' => 'p-4']), 
                ])->space(0),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Filter by Category')
                    ->options(function () {
                        $user = auth()->user();
                        if (!$user || !$user->restaurant_id) return [];
                        return Category::withoutGlobalScopes()
                            ->where('restaurant_id', $user->restaurant_id)
                            ->where(function($q) use ($user) {
                                $q->whereNull('branch_id');
                                if ($user->branch_id) $q->orWhere('branch_id', $user->branch_id);
                            })
                            ->pluck('name', 'id')->toArray();
                    })->searchable(),
                
                Tables\Filters\SelectFilter::make('type')
                    ->label('Filter by Type')
                    ->options(['veg' => 'Veg', 'non-veg' => 'Non-Veg']),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->button()
                    ->size('xs')
                    ->form([
                        Forms\Components\Select::make('category_id')
                            ->label('Category')
                            ->required()
                            ->disabled(fn($record) => $record && auth()->user()->branch_id !== null && $record->branch_id === null)
                            ->options(function () {
                                $user = auth()->user();
                                return Category::withoutGlobalScopes()
                                    ->where('restaurant_id', $user->restaurant_id)
                                    ->where(function($q) use ($user) {
                                        $q->whereNull('branch_id');
                                        if ($user->branch_id) $q->orWhere('branch_id', $user->branch_id);
                                    })->pluck('name', 'id');
                            }),
                        
                        Forms\Components\TextInput::make('name')
                            ->label('Item Name')
                            ->required()
                            ->maxLength(150)
                            ->disabled(fn($record) => $record && auth()->user()->branch_id !== null && $record->branch_id === null),
                        
                        Forms\Components\TextInput::make('price')
                            ->numeric()->required()->prefix('₹')
                            ->disabled(fn($record) => $record && auth()->user()->branch_id !== null && $record->branch_id === null),
                        
                        Forms\Components\Select::make('type')
                            ->options(['veg' => 'Veg', 'non-veg' => 'Non-Veg'])
                            ->required()
                            ->disabled(fn($record) => $record && auth()->user()->branch_id !== null && $record->branch_id === null),
                            
                        Forms\Components\FileUpload::make('image_path')
                            ->image()->disk('public')->required()
                            ->disabled(fn($record) => $record && auth()->user()->branch_id !== null && $record->branch_id === null),
                            
                        Forms\Components\Textarea::make('description')
                            ->maxLength(500)
                            ->columnSpanFull()
                            ->disabled(fn($record) => $record && auth()->user()->branch_id !== null && $record->branch_id === null),
                    ]),

                Tables\Actions\DeleteAction::make()
                    ->button()
                    ->size('xs')
                    ->outlined()
                    ->visible(fn($record) => $record && (auth()->user()->branch_id === null ? $record->branch_id === null : $record->branch_id === auth()->user()->branch_id)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageMenus::route('/'),
        ];
    }
}