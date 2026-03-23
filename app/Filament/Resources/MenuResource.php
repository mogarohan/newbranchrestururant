<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MenuResource\Pages;
use App\Models\MenuItem;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString; // 👈 Added for CSS rendering

class MenuResource extends Resource
{
    protected static ?string $model = MenuItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Menu Dashboard';
    protected static ?string $navigationGroup = 'Menu Management';
    protected static ?int $navigationSort = 1;

    /* -----------------------------------------------------------
       PERMISSIONS (Main Admin adds/edits, Branch only toggles)
    ------------------------------------------------------------*/
    public static function canAccess(): bool
    {
        return auth()->check()
            && auth()->user()->restaurant_id !== null
            && in_array(auth()->user()->role->name, ['restaurant_admin', 'manager', 'branch_admin']);
    }

    public static function canCreate(): bool
    {
        return !auth()->user()->isBranchAdmin() && !auth()->user()->isManager();
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return true; // Required for the toggle to work
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return !auth()->user()->isBranchAdmin() && !auth()->user()->isManager();
    }

    /* -----------------------------------------------------------
       DATA ISOLATION
    ------------------------------------------------------------*/
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery()->withoutGlobalScopes();
        $query->where('restaurant_id', $user->restaurant_id);

        if ($user->isBranchAdmin() || $user->isManager()) {
            $query->whereNull('branch_id'); // Branches see the main restaurant's menu
        }

        return $query;
    }

    /* -----------------------------------------------------------
       MODAL FORM (Item Creation)
    ------------------------------------------------------------*/
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
                ->reactive() // 👈 Reactive so the Image directory updates when selected
                ->options(function () {
                    $user = auth()->user();
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
                ->reactive() // 👈 Reactive so the Image filename updates
                ->maxLength(150),

            Forms\Components\TextInput::make('price')
                ->numeric()
                ->minValue(0)
                ->required()
                ->prefix('₹'),

            Forms\Components\Textarea::make('description')
                ->maxLength(500)
                ->columnSpanFull(),

            // 👇 STRICT STORAGE ARCHITECTURE 
            Forms\Components\FileUpload::make('image_path')
                ->label('Item Image')
                ->image()
                ->disk('public')
                ->directory(function (callable $get) {
                    $user = auth()->user();
                    $restaurantSlug = Str::slug($user->restaurant->name ?? 'restaurant');

                    // Fetch category name
                    $categoryId = $get('category_id');
                    $categoryName = Category::find($categoryId)?->name ?? 'uncategorized';
                    $categorySlug = Str::slug($categoryName);

                    // Output: restaurants/Restaurant1/Categories/Category1
                    return "restaurants/{$restaurantSlug}/Categories/{$categorySlug}";
                })
                ->getUploadedFileNameForStorageUsing(function (\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file, callable $get): string {
                    $itemName = Str::slug($get('name') ?? 'item');
                    $extension = $file->getClientOriginalExtension();

                    // Output: Item1.png
                    return "{$itemName}.{$extension}";
                })
                ->imageEditor()
                ->required()
                ->maxSize(2048),

            Forms\Components\Toggle::make('is_available')
                ->default(true),
        ]);
    }

    /* -----------------------------------------------------------
       CARD GRID LAYOUT & FILTERS (COMPACT)
    ------------------------------------------------------------*/
    public static function table(Table $table): Table
    {
        return $table
            // 👇 ADDED CUSTOM CSS FOR UNIFORM BLUE STYLE (NO ALTERNATING, NO ORANGE EFFECT) 👇
            ->heading(new HtmlString('
                <style>
                    /* Card Base - All cards have Blue border and White background */
                    .fi-ta-record {
                        border-radius: 12px !important;
                        background-color: #ffffff !important;
                        border: 2px solid #3B82F6 !important; /* Solid Blue Border for ALL cards */
                        box-shadow: none !important; /* 👈 Kills the orange glow/shadow effect */
                        outline: none !important; /* 👈 Kills default focus rings */
                    }
                    .dark .fi-ta-record {
                        background-color: #1e293b !important;
                        border-color: rgba(59, 130, 246, 0.5) !important;
                    }
                    
                    /* Category Badge - Blue */
                    .fi-ta-record .fi-badge {
                        color: #2563eb !important; 
                        background-color: rgba(59, 130, 246, 0.1) !important;
                    }
                    
                    /* Price Text - Blue */
                    .fi-ta-record .custom-price-text,
                    .fi-ta-record .custom-price-text * {
                        color: #3B82F6 !important;
                    }
                    
                    /* Toggle Switch (Checked state) - Orange */
                    .fi-ta-record button[role="switch"][aria-checked="true"] {
                        background-color: #F47D20 !important;
                    }
                    
                    /* Edit Button (1st button) - Solid Blue with White Text */
                    .fi-ta-record .fi-ta-actions button:nth-of-type(1) {
                        background-color: #3B82F6 !important;
                        color: #ffffff !important;
                        border: none !important;
                        transition: 0.2s;
                    }
                    /* Ensure SVG Icon and Span text inside Edit button are also white */
                    .fi-ta-record .fi-ta-actions button:nth-of-type(1) * {
                        color: #ffffff !important;
                    }
                    .fi-ta-record .fi-ta-actions button:nth-of-type(1):hover {
                        background-color: #2563eb !important; /* Darker blue on hover */
                    }

                    /* Delete Button (2nd button) - Red */
                    .fi-ta-record .fi-ta-actions button:nth-of-type(2) {
                        color: #ef4444 !important;
                        border-color: rgba(239, 68, 68, 0.4) !important;
                        background-color: transparent !important;
                    }
                    .fi-ta-record .fi-ta-actions button:nth-of-type(2):hover {
                        background-color: rgba(239, 68, 68, 0.1) !important;
                    }
                </style>
            '))
            ->contentGrid([
                'md' => 3, // Increased columns from 2 to 3 for medium screens
                'lg' => 4, // Increased columns to 4 for large screens
                'xl' => 5, // 5 cards per row on very large screens
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\ImageColumn::make('image_path')
                        ->height('120px') // Reduced image height
                        ->width('100%')
                        ->extraImgAttributes(['class' => 'object-cover w-full h-full rounded-t-xl']),

                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('name')
                            ->weight('bold')
                            ->size('md') // Reduced font size
                            ->searchable(), // Searchable Name

                        Tables\Columns\TextColumn::make('category.name')
                            ->badge()
                            ->size('xs'), // Styled via CSS above

                        Tables\Columns\TextColumn::make('price')
                            ->money('INR')
                            ->weight('bold')
                            ->size('sm') // Smaller price text
                            ->extraAttributes(['class' => 'custom-price-text']), // Styled via CSS above

                        // Independent Toggle Logic for Branches
                        Tables\Columns\ToggleColumn::make('is_available')
                            ->label('Stock Status')
                            ->getStateUsing(function (MenuItem $record) {
                                $user = auth()->user();
                                // 👇 FIX: Checked if branch_id is NOT null
                                if (($user->isBranchAdmin() || $user->isManager()) && $user->branch_id !== null) {
                                    $status = DB::table('branch_menu_item_status')
                                        ->where('menu_item_id', $record->id)
                                        ->where('branch_id', $user->branch_id)
                                        ->first();
                                    return $status ? (bool) $status->is_available : (bool) $record->is_available;
                                }
                                return (bool) $record->is_available;
                            })
                            ->updateStateUsing(function (MenuItem $record, $state) {
                                $user = auth()->user();
                                // 👇 FIX: Checked if branch_id is NOT null
                                if (($user->isBranchAdmin() || $user->isManager()) && $user->branch_id !== null) {
                                    DB::table('branch_menu_item_status')->updateOrInsert(
                                        ['menu_item_id' => $record->id, 'branch_id' => $user->branch_id],
                                        ['is_available' => $state, 'updated_at' => now()]
                                    );
                                } else {
                                    $record->update(['is_available' => $state]);
                                }
                            }),
                    ])->space(2)->extraAttributes(['class' => 'p-3']), // Tighter padding
                ])->space(0),
            ])
            // FILTERS FOR CATEGORY
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Filter by Category')
                    ->options(function () {
                        $user = auth()->user();
                        $query = Category::withoutGlobalScopes()
                            ->where('restaurant_id', $user->restaurant_id);

                        if ($user->isBranchAdmin() || $user->isManager()) {
                            $query->whereNull('branch_id');
                        }
                        return $query->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->button()
                    ->size('xs') // Smaller buttons
                    // 👈 Removed ->outlined() to make it a solid block 
                    ->visible(fn() => !auth()->user()->isBranchAdmin() && !auth()->user()->isManager()),

                Tables\Actions\DeleteAction::make()
                    ->button()
                    ->size('xs') // Smaller buttons
                    ->outlined()
                    ->visible(fn() => !auth()->user()->isBranchAdmin() && !auth()->user()->isManager()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageMenus::route('/'),
        ];
    }
}