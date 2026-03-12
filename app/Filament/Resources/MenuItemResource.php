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
use Illuminate\Support\HtmlString; // 👈 CSS Injection ke liye zaroori hai

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
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('restaurant_id', auth()->user()->restaurant_id);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Hidden::make('restaurant_id')
                ->default(fn () => auth()->user()->restaurant_id)
                ->required(),

            Forms\Components\Select::make('category_id')
                ->label('Category')
                ->required()
                ->options(fn () =>
                    Category::where('restaurant_id', auth()->user()->restaurant_id)
                        ->where('is_active', true)
                        ->pluck('name', 'id')
                )
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
                ->directory(fn ($get) =>
                    'restaurants/' . auth()->user()->restaurant->slug . '/items'
                )
                ->imageEditor()
                ->maxSize(2048),

            Forms\Components\Toggle::make('is_available')
                ->default(true),
        ]);
    }

    /* ---------------------------------------------------
     | TABLE (UPDATED FOR TRANSPARENCY & PREMIUM LOOK)
     |---------------------------------------------------*/
    public static function table(Table $table): Table
    {
        return $table
            // 🎨 CSS INJECTION FOR TRANSPARENCY
            ->heading(new HtmlString('
                <style>
                    /* Make the entire table wrapper transparent */
                    .fi-ta-ctn {
                        background-color: transparent !important;
                        box-shadow: none !important;
                        border: 1px solid rgba(156, 163, 175, 0.2) !important;
                    }
                    /* Headers, Toolbars, Footers */
                    .fi-ta-header-toolbar, .fi-ta-footer, .fi-ta-content, .fi-ta-table thead, .fi-ta-table th {
                        background-color: transparent !important;
                        border-color: rgba(156, 163, 175, 0.2) !important;
                    }
                    /* Individual Rows */
                    .fi-ta-record {
                        background-color: transparent !important;
                        border-bottom: 1px solid rgba(156, 163, 175, 0.2) !important;
                        transition: background-color 0.2s ease;
                    }
                    /* Row Hover Effect */
                    .fi-ta-record:hover {
                        background-color: rgba(234, 88, 12, 0.05) !important; /* Slight orange tint on hover */
                    }
                </style>
                <span style="font-size: 1.25rem; font-weight: 800;">Restaurant Menu Items</span>
            '))
            ->columns([
                
                // 1. Image
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('IMAGE')
                    ->circular()
                    ->size(50)
                    ->defaultImageUrl('https://ui-avatars.com/api/?name=Food&color=FFFFFF&background=111827'), // Added fallback if image missing

                // 2. Name & Description stacked
                Tables\Columns\TextColumn::make('name')
                    ->label('ITEM NAME')
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->description(fn (MenuItem $record): string => Str::limit($record->description ?? '', 40)), // Limited desc so row doesn't get too tall

                // 3. Category Badge
                Tables\Columns\TextColumn::make('category.name')
                    ->label('CATEGORY')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Appetizers' => 'info',
                        'Main Course' => 'warning',
                        'Desserts' => 'danger',
                        'Beverages' => 'success',
                        default => 'gray',
                    }),

                // 4. Price
                Tables\Columns\TextColumn::make('price')
                    ->label('PRICE')
                    ->money('INR')
                    ->weight('bold')
                    ->color('primary'),

                // 5. Availability Toggle
                Tables\Columns\ToggleColumn::make('is_available')
                    ->label('AVAILABILITY')
                    ->onColor('warning'), // Orange theme match
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                // Premium Button Actions matching the other pages
                Tables\Actions\EditAction::make()
                    ->color('warning')
                    ->button()
                    ->outlined(),

                Tables\Actions\DeleteAction::make()
                    ->color('danger')
                    ->button()
                    ->outlined(),
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