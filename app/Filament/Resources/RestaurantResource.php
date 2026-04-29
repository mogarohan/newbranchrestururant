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
                ->label('Restaurant Name')
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
                ->label('User Limit of restaurant including branches')
                ->numeric()
                ->minValue(1)
                ->required(),

            // 👇 NEW: Address Field 👇
            Forms\Components\Textarea::make('address')
                ->label('Restaurant Address')
                ->maxLength(65535)
                ->columnSpanFull(),

            // 👇 NEW: Phone Number Field 👇
            Forms\Components\TextInput::make('phone_no')
                ->label('Phone Number')
                ->tel()
                ->maxLength(255),

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

                    /* --- 🎨 TABLE CONTAINER (GLASS + BLACK BORDER) --- */
                    .fi-ta-ctn {
                        background: rgba(255, 255, 255, 0.45) !important;
                        backdrop-filter: blur(16px) saturate(140%) !important;
                        -webkit-backdrop-filter: blur(16px) saturate(140%) !important;
                        border: 1.5px solid #000000 !important; /* BLACK BORDER */
                        border-radius: 1.25rem !important;
                        box-shadow: 0 8px 32px rgba(42, 71, 149, 0.08) !important;
                        overflow: hidden !important;
                        color: #000000 !important;
                    }

                    /* --- TABLE HEADER --- */
                    .fi-ta-header-ctn {
                        background: rgba(255, 255, 255, 0.2) !important;
                        border-bottom: 1.5px solid #000000 !important; /* Inner black separator */
                    }
                    
                    .fi-ta-header-cell {
                        background-color: transparent !important;
                    }

                    .fi-ta-header-cell-label {
                        color: #2a4795 !important; /* BRAND BLUE */
                        font-weight: 800 !important;
                        text-transform: uppercase !important;
                        letter-spacing: 0.05em !important;
                    }

                    /* --- TABLE ROWS (TEXT COLORS) --- */
                    .fi-ta-cell-content, 
                    .fi-ta-text-item-label,
                    .fi-ta-text-item-description {
                        color: #0f172a !important; /* Dark Slate Text */
                        font-family: "Inter", sans-serif !important;
                    }

                    .fi-ta-record {
                        border-bottom: 1px solid rgba(0, 0, 0, 0.1) !important;
                        background: transparent !important;
                        transition: all 0.2s ease !important;
                    }

                    /* --- 🔄 ALTERNATING ROW HOVER (BLUE & ORANGE) --- */
                    .fi-ta-record:nth-child(odd):hover {
                        background-color: rgba(42, 71, 149, 0.08) !important; /* Blue Tint */
                    }
                    .fi-ta-record:nth-child(even):hover {
                        background-color: rgba(241, 107, 63, 0.08) !important; /* Orange Tint */
                    }

                    /* --- TABLE PAGINATION / FOOTER --- */
                    .fi-ta-content + div {
                        background: rgba(255, 255, 255, 0.2) !important;
                        border-top: 1.5px solid #000000 !important; /* Black separator for footer */
                    }

                    /* --- SEARCH INPUT STYLING --- */
                    .fi-input-wrapper {
                        background-color: rgba(255, 255, 255, 0.5) !important;
                        border: 1.5px solid #2a4795 !important; /* Blue border */
                        border-radius: 0.75rem !important;
                    }
                    .fi-input-wrapper:focus-within {
                        border-color: #f16b3f !important; /* Orange border on focus */
                        box-shadow: 0 0 0 3px rgba(241, 107, 63, 0.2) !important;
                    }

                    /* --- 🌙 DARK THEME OVERRIDES --- */
                    .dark .fi-ta-ctn {
                        background: rgba(15, 15, 20, 0.7) !important;
                        border: 1.5px solid #000000 !important;
                    }
                    .dark .fi-ta-header-ctn {
                        background: rgba(0, 0, 0, 0.3) !important;
                        border-color: #000000 !important;
                    }
                    .dark .fi-ta-header-cell-label {
                        color: #456aba !important; /* Light Blue */
                    }
                    .dark .fi-ta-cell-content, 
                    .dark .fi-ta-text-item-label,
                    .dark .fi-ta-text-item-description {
                        color: #f8fafc !important; /* White Text */
                    }
                    .dark .fi-ta-record {
                        border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
                    }
                    .dark .fi-ta-record:nth-child(odd):hover {
                        background-color: rgba(69, 106, 186, 0.15) !important; /* Blue Tint Dark */
                    }
                    .dark .fi-ta-record:nth-child(even):hover {
                        background-color: rgba(241, 107, 63, 0.15) !important; /* Orange Tint Dark */
                    }
                    .dark .fi-ta-content + div {
                        background: rgba(0, 0, 0, 0.3) !important;
                        border-color: #000000 !important;
                    }
                    .dark .fi-input-wrapper {
                        background-color: rgba(0, 0, 0, 0.5) !important;
                        border-color: #456aba !important;
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

                Tables\Columns\TextColumn::make('upi_id')
                    ->label('UPI ID')
                    ->searchable()
                    ->placeholder('Not Set')
                    ->color('gray'),
                    // 👇 NEW: Phone Number Column 👇
                Tables\Columns\TextColumn::make('phone_no')
                    ->label('PHONE')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                // 👇 NEW: Address Column 👇
                Tables\Columns\TextColumn::make('address')
                    ->label('ADDRESS')
                    ->searchable()
                    ->limit(30) // Limits the text so long addresses don't break the layout
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 30 ? $state : null; // Shows full address on hover
                    })
                    ->toggleable(isToggledHiddenByDefault: true), // Hidden by default to keep the table clean

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
                // 👇 BLUE EDIT BUTTON (WITH ORANGE HOVER) 👇
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->extraAttributes([
                        'style' => 'color: #456aba; transition: color 0.2s; display: inline-flex; padding: 6px; background: rgba(255,255,255,0.5); border-radius: 6px; border: 1.5px solid #000000;',
                        'onmouseover' => "this.style.color='#f16b3f'",
                        'onmouseout' => "this.style.color='#456aba'",
                    ]),

                // 👇 RED DELETE BUTTON (TO MATCH PREMIUM STYLE) 👇
                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->extraAttributes([
                        'style' => 'color: #ef4444; transition: color 0.2s; display: inline-flex; padding: 6px; background: rgba(255,255,255,0.5); border-radius: 6px; border: 1.5px solid #000000;',
                        'onmouseover' => "this.style.color='#b91c1c'",
                        'onmouseout' => "this.style.color='#ef4444'",
                    ]),
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