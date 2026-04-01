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
use Illuminate\Support\HtmlString;

class RestaurantResource extends Resource
{
    protected static ?string $model = Restaurant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationLabel = 'Restaurants';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 1;

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

            // 👇 NEW: UPI ID Field for Main Restaurant 👇
            Forms\Components\TextInput::make('upi_id')
                ->label('Master UPI ID')
                ->placeholder('e.g., yourname@okhdfcbank')
                ->maxLength(255)
                ->helperText('This UPI ID will be used for all QR Payments unless a branch overrides it.'),

            /* --- MULTI-BRANCH FEATURE TOGGLE --- */
            Forms\Components\Toggle::make('has_branches')
                ->label('Enable Multiple Branches')
                ->live()
                ->default(false),

            /* --- MAX BRANCHES INPUT (Conditional) --- */
            Forms\Components\TextInput::make('max_branches')
                ->label('Maximum Branches Allowed')
                ->numeric()
                ->minValue(1)
                ->visible(fn(callable $get) => $get('has_branches'))
                ->required(fn(callable $get) => $get('has_branches')),

            Forms\Components\Toggle::make('is_active')
                ->default(true),

            /* 👇 NAYI RESTAURANT BANATE WAQT RESTAURANT ADMIN CREATE KARNE KE LIYE FIELDS 👇 */
            Forms\Components\Section::make('Create Restaurant Admin')
                ->description('These credentials will be used by the restaurant admin to log in.')
                ->schema([
                    Forms\Components\TextInput::make('admin_name')
                        ->label('Admin Name')
                        ->required()
                        ->dehydrated(false), // Isko Restaurant table me save nahi karna hai

                    Forms\Components\TextInput::make('admin_email')
                        ->label('Admin Email')
                        ->email()
                        ->required()
                        ->unique('users', 'email') // User table me unique hona chahiye
                        ->dehydrated(false),

                    Forms\Components\TextInput::make('admin_password')
                        ->label('Admin Password')
                        ->password()
                        ->required()
                        ->dehydrated(false),
                ])
                // YEH SECTION SIRF CREATE WALE PAGE PAR DIKHEGA
                ->visible(fn($livewire) => $livewire instanceof Pages\CreateRestaurant),
        ]);
    }

    /* ---------------------------------------------------
     | TABLE (UPDATED FOR TRANSPARENCY & PREMIUM LOOK)
     |---------------------------------------------------*/
    public static function table(Table $table): Table
    {
        return $table
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
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('slug')
                    ->label('SLUG')
                    ->copyable()
                    ->color('gray')
                    ->toggleable(),
                    
                // 👇 NEW: Display UPI ID in Table 👇
                Tables\Columns\TextColumn::make('upi_id')
                    ->label('UPI ID')
                    ->searchable()
                    ->placeholder('Not Set')
                    ->color('gray'),

                Tables\Columns\IconColumn::make('has_branches')
                    ->label('MULTI-BRANCH')
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('max_branches')
                    ->label('MAX BRANCHES')
                    ->placeholder('-')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('user_limits')
                    ->label('USER LIMIT')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('STATUS')
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('CREATED ON')
                    ->date('M d, Y')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->color('warning')
                    ->button()
                    ->outlined(),

                Tables\Actions\DeleteAction::make()
                    ->color('danger')
                    ->button()
                    ->outlined(),
            ])
            ->bulkActions([]);
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