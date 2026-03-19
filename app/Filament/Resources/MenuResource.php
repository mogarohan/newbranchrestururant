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
            ->contentGrid([
                'md' => 3, // 👈 Increased columns from 2 to 3 for medium screens
                'lg' => 4, // 👈 Increased columns to 4 for large screens (Compact cards)
                'xl' => 5, // 👈 5 cards per row on very large screens
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\ImageColumn::make('image_path')
                        ->height('120px') // 👈 Reduced image height
                        ->width('100%')
                        ->extraImgAttributes(['class' => 'object-cover w-full h-full rounded-t-xl']),
                    
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('name')
                            ->weight('bold')
                            ->size('md') // 👈 Reduced font size
                            ->searchable(), // 👈 ADDED SEARCHABILITY HERE
                        
                        Tables\Columns\TextColumn::make('category.name')
                            ->badge()
                            ->size('xs') // 👈 Smaller badge
                            ->color('warning'),
                            
                        Tables\Columns\TextColumn::make('price')
                            ->money('INR')
                            ->weight('bold')
                            ->color('primary')
                            ->size('sm'), // 👈 Smaller price text

                        // Independent Toggle Logic for Branches
                        Tables\Columns\ToggleColumn::make('is_available')
                            ->label('Stock Status')
                            ->onColor('warning')
                            ->getStateUsing(function (MenuItem $record) {
                                $user = auth()->user();
                                if ($user->isBranchAdmin() || $user->isManager()) {
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
                                if ($user->isBranchAdmin() || $user->isManager()) {
                                    DB::table('branch_menu_item_status')->updateOrInsert(
                                        ['menu_item_id' => $record->id, 'branch_id' => $user->branch_id],
                                        ['is_available' => $state, 'updated_at' => now()]
                                    );
                                } else {
                                    $record->update(['is_available' => $state]);
                                }
                            }),
                    ])->space(2)->extraAttributes(['class' => 'p-3']), // 👈 Tighter padding
                ])->space(0),
            ])
            // 👇 ADDED FILTERS FOR CATEGORY
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
                    ->size('xs') // 👈 Smaller buttons
                    ->outlined()
                    ->color('warning')
                    ->visible(fn() => !auth()->user()->isBranchAdmin() && !auth()->user()->isManager()),

                Tables\Actions\DeleteAction::make()
                    ->button()
                    ->size('xs') // 👈 Smaller buttons
                    ->outlined()
                    ->color('danger')
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