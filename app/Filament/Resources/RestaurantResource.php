<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RestaurantResource\Pages;
use App\Models\Restaurant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString; // 👈 Custom CSS ke liye import kiya gaya hai

class RestaurantResource extends Resource
{
    protected static ?string $model = Restaurant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationLabel = 'Restaurants';
    protected static ?string $navigationGroup = 'Administration';
    

    /**
     * 🔐 Only Super Admin can see this resource
     */
    public static function canAccess(): bool
    {
        return auth()->check()
            && auth()->user()->is_super_admin === true;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(
                    fn($state, callable $set) =>
                    $set('slug', Str::slug($state))
                ),

            Forms\Components\TextInput::make('slug')
                ->disabled()
                ->dehydrated()
                ->required()
                ->unique(ignoreRecord: true),

            Forms\Components\FileUpload::make('logo_path')
                ->label('Restaurant Logo')
                ->image()
                ->imageEditor()
                ->disk('public')
                ->directory(
                    fn($get) =>
                    'restaurants/' . ($get('slug') ?? 'temp') . '/LOGO'
                )
                ->getUploadedFileNameForStorageUsing(
                    fn($file) => 'logo.' . $file->getClientOriginalExtension()
                )
                ->acceptedFileTypes([
                    'image/png',
                    'image/jpeg',
                    'image/jpg',
                    'image/svg+xml',
                    'image/heif',
                    'image/webp',
                ])
                ->visibility('public')
                ->maxSize(2048)
                ->required(fn(string $operation) => $operation === 'create'),

            Forms\Components\TextInput::make('user_limits')
                ->numeric()
                ->minValue(1)
                ->required(),

            Forms\Components\Toggle::make('is_active')
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
                    .fi-ta-record:hover {
                        background-color: rgba(234, 88, 12, 0.05) !important; /* Slight orange tint on hover */
                    }
                </style>
            '))
            ->columns([
                Tables\Columns\ImageColumn::make('logo_path')
                    ->label('LOGO')
                    ->disk('public')
                    ->circular(),

                Tables\Columns\TextColumn::make('name')
                    ->label('NAME')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'), // Bold text matching the UI

                Tables\Columns\TextColumn::make('slug')
                    ->label('SLUG')
                    ->copyable()
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('user_limits')
                    ->label('USER LIMIT')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('STATUS')
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('CREATED ON')
                    ->date('M d, Y') // Clean date format
                    ->sortable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                // Premium Button Actions (Like UserResource)
                Tables\Actions\EditAction::make()
                    ->color('warning')
                    ->button()
                    ->outlined(),
                    
                Tables\Actions\DeleteAction::make()
                    ->color('danger')
                    ->button()
                    ->outlined(),
            ])
            ->bulkActions([]); // 🚫 no bulk delete
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRestaurants::route('/'),
            'create' => Pages\CreateRestaurant::route('/create'),
            'edit' => Pages\EditRestaurant::route('/{record}/edit'),
        ];
    }
}