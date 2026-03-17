<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MenuItemResource\Pages;
use App\Models\MenuItem;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\DB; // 👈 Import DB for Toggle Logic

class MenuItemResource extends Resource
{
    protected static ?string $model = MenuItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Menu Items';
    protected static ?string $navigationGroup = 'Menu Management';
    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return auth()->check()
            && auth()->user()->restaurant_id !== null
            && in_array(auth()->user()->role->name, [
                'restaurant_admin',
                'manager',
                'branch_admin',
            ]);
    }

    // 👇 Branch Admin naya nahi bana sakte
    public static function canCreate(): bool
    {
        return !auth()->user()->isBranchAdmin() && !auth()->user()->isManager();
    }

    // 👇 Edit ko TRUE karna zaroori hai, warna Filament Toggle button ko disable kar dega!
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return true; 
    }

    // 👇 Branch Admin delete nahi kar sakte
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return !auth()->user()->isBranchAdmin() && !auth()->user()->isManager();
    }

    /* -----------------------------------------------------------
       DATA ISOLATION (FIXED)
    ------------------------------------------------------------*/
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        // 👇 FIX: withoutGlobalScopes() lagaya taaki koi background filter na lage
        $query = parent::getEloquentQuery()->withoutGlobalScopes();

        $query->where('restaurant_id', $user->restaurant_id);

        // 👇 Sirf Main Restaurant (null branch_id) ke menu items list mein aayenge
        if ($user->isBranchAdmin() || $user->isManager()) {
            $query->whereNull('branch_id');
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Hidden::make('restaurant_id')
                ->default(fn() => auth()->user()->restaurant_id)
                ->required(),

            Forms\Components\Hidden::make('branch_id')
                ->default(fn() => auth()->user()->branch_id),

            Forms\Components\Select::make('category_id')
                ->label('Category')
                ->required()
                ->options(function () {
                    $user = auth()->user();
                    // Dropdown me bhi withoutGlobalScopes() lagana zaroori hai
                    $query = Category::withoutGlobalScopes()
                        ->where('restaurant_id', $user->restaurant_id)
                        ->where('is_active', true);

                    if ($user->isBranchAdmin() || $user->isManager()) {
                        $query->whereNull('branch_id');
                    }

                    return $query->pluck('name', 'id');
                })
                ->searchable(),

            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(150),

            Forms\Components\Textarea::make('description')
                ->maxLength(500)
                ->columnSpanFull(),

            Forms\Components\TextInput::make('price')
                ->numeric()
                ->minValue(0)
                ->required(),

            Forms\Components\FileUpload::make('image_path')
                ->label('Item Image')
                ->image()
                ->disk('public')
                ->directory(function (callable $get) {
                    $user = auth()->user();
                    $restaurantSlug = $user->restaurant->slug;
                    $branchId = $get('branch_id');

                    if ($branchId) {
                        return "restaurants/{$restaurantSlug}/branches/branch-{$branchId}/items";
                    }

                    return "restaurants/{$restaurantSlug}/items";
                })
                ->imageEditor()
                ->maxSize(2048),

            Forms\Components\Toggle::make('is_available')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading(new HtmlString('
                <style>
                    .fi-ta-ctn { background-color: transparent !important; box-shadow: none !important; border: 1px solid rgba(156, 163, 175, 0.2) !important; }
                    .fi-ta-header-toolbar, .fi-ta-footer, .fi-ta-content, .fi-ta-table thead, .fi-ta-table th { background-color: transparent !important; border-color: rgba(156, 163, 175, 0.2) !important; }
                    .fi-ta-record { background-color: transparent !important; border-bottom: 1px solid rgba(156, 163, 175, 0.2) !important; transition: background-color 0.2s ease; }
                    .fi-ta-record:hover { background-color: rgba(234, 88, 12, 0.05) !important; }
                </style>
                <span style="font-size: 1.25rem; font-weight: 800;">Restaurant Menu Items</span>
            '))
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('IMAGE')
                    ->circular()
                    ->size(50)
                    ->defaultImageUrl('https://ui-avatars.com/api/?name=Food&color=FFFFFF&background=111827'),

                Tables\Columns\TextColumn::make('name')
                    ->label('ITEM NAME')
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->description(fn(MenuItem $record): string => Str::limit($record->description ?? '', 40)),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('CATEGORY')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Appetizers' => 'info',
                        'Main Course' => 'warning',
                        'Desserts' => 'danger',
                        'Beverages' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('branch.name')
                    ->label('SOURCE')
                    ->default('Main Restaurant')
                    ->sortable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('price')
                    ->label('PRICE')
                    ->money('INR')
                    ->weight('bold')
                    ->color('primary'),

                // 👇 YAHAN JADOO HAI: Independent Toggle Backend
                Tables\Columns\ToggleColumn::make('is_available')
                    ->label('AVAILABILITY')
                    ->onColor('warning')
                    ->getStateUsing(function (MenuItem $record) {
                        $user = auth()->user();
                        // Agar Branch Admin dekhe toh DB se uski branch ka status nikalo
                        if ($user->isBranchAdmin() || $user->isManager()) {
                            $status = DB::table('branch_menu_item_status')
                                ->where('menu_item_id', $record->id)
                                ->where('branch_id', $user->branch_id)
                                ->first();
                            return $status ? (bool) $status->is_available : (bool) $record->is_available;
                        }
                        // Main Restaurant Admin ko normal status dikhega
                        return (bool) $record->is_available;
                    })
                    ->updateStateUsing(function (MenuItem $record, $state) {
                        $user = auth()->user();
                        // Agar Branch admin toggle click kare toh naye table me entry maro!
                        if ($user->isBranchAdmin() || $user->isManager()) {
                            DB::table('branch_menu_item_status')->updateOrInsert(
                                ['menu_item_id' => $record->id, 'branch_id' => $user->branch_id],
                                ['is_available' => $state, 'updated_at' => now()]
                            );
                        } else {
                            // Main admin toggle kare toh purane table me update karo
                            $record->update(['is_available' => $state]);
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->color('warning')
                    ->button()
                    ->outlined()
                    // 👇 Edit permission ON hone ke bawajood, button ko HIDE kar diya Branch Admin ke liye
                    ->visible(fn() => !auth()->user()->isBranchAdmin() && !auth()->user()->isManager()),

                Tables\Actions\DeleteAction::make()
                    ->color('danger')
                    ->button()
                    ->outlined()
                    // 👇 Delete button ko bhi hide kar diya Branch Admin ke liye
                    ->visible(fn() => !auth()->user()->isBranchAdmin() && !auth()->user()->isManager()),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn() => !auth()->user()->isBranchAdmin() && !auth()->user()->isManager()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMenuItems::route('/'),
            'create' => Pages\CreateMenuItem::route('/create'),
            'edit' => Pages\EditMenuItem::route('/{record}/edit'),
        ];
    }
}