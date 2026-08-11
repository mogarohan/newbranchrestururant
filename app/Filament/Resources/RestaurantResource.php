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

            Forms\Components\Textarea::make('address')
                ->label('Restaurant Address')
                ->maxLength(65535)
                ->columnSpanFull(),

            Forms\Components\TextInput::make('phone_no')
                ->label('Phone Number')
                ->tel()
                ->maxLength(255),

            Forms\Components\Toggle::make('is_pay_first')
                ->label('Pay First Model')
                ->helperText('Enable if customers must pay before ordering'),

            Forms\Components\TextInput::make('gst_no')
                ->label('GST Number')
                ->placeholder('Enter GSTIN')
                ->maxLength(20),

            Forms\Components\TextInput::make('table_limits')
                ->label('Table Capacity Limit')
                ->numeric()
                ->default(0)
                ->helperText('Maximum number of tables allowed for this restaurant'),

            Forms\Components\TextInput::make('upi_id')
                ->label('Master UPI ID')
                ->placeholder('e.g., yourname@okhdfcbank')
                ->maxLength(255)
                ->helperText('This UPI ID will be used for all QR Payments unless a branch overrides it.'),

            Forms\Components\Toggle::make('has_branches')
                ->label('Enable Multiple Branches')
                ->live()
                ->default(false),

            Forms\Components\TextInput::make('max_branches')
                ->label('Maximum Branches Allowed')
                ->numeric()
                ->minValue(1)
                ->visible(fn(callable $get) => $get('has_branches'))
                ->required(fn(callable $get) => $get('has_branches')),

            Forms\Components\Toggle::make('is_active')
                ->default(true),

            Forms\Components\Section::make('Premium SaaS Modules')
                ->description('Enable and manage high-tier premium modules for this restaurant subscription.')
                ->schema([
                    Forms\Components\Toggle::make('is_rooms_facility')
                        ->label('Enable Rooms Facility')
                        ->helperText('Allow this restaurant to use the Hotel/Rooms dashboard and QR system.')
                        ->live()
                        ->default(false),

                    Forms\Components\TextInput::make('rooms_limit')
                        ->label('Maximum Rooms Allowed')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->helperText('The maximum number of rooms this restaurant can create under their plan.')
                        ->visible(fn(Forms\Get $get) => $get('is_rooms_facility') === true),

                    Forms\Components\Toggle::make('has_inventory')
                        ->label('Enable Inventory & Stock Control')
                        ->helperText('Grants access to full raw material sheets, inline tracking, and FIFO server logic.')
                        ->default(false),

                    Forms\Components\Toggle::make('has_detailed_inventory')
                        ->label('Enable Detailed Auto Inventory')
                        ->helperText('Enables recipe-based raw ingredient tracking with automated deduction on order acceptance.')
                        ->default(false),

                    // 🌟 NAYA TOGGLE ATTENDANCE KE LIYE 🌟
                    Forms\Components\Toggle::make('has_attendance')
                        ->label('Enable Attendance & Payroll')
                        ->helperText('Allow this restaurant to manage staff attendance, shifts, and auto-payroll.')
                        ->default(false),

                    // 🌟 NAYA TOGGLE ALL-IN-ONE CAFE KE LIYE 🌟
                    Forms\Components\Toggle::make('is_all_in_one_cafe')
                        ->label('Enable All-In-One Dashboard (QSR)')
                        ->helperText('For small cafes: Manager screen handles Kitchen (Chef) & Serving (Waiter) directly.')
                        ->default(false),
                    ])
                ->columns(2),


            Forms\Components\Section::make('Create Restaurant Admin')
                ->description('These credentials will be used by the restaurant admin to log in.')
                ->schema([
                    Forms\Components\TextInput::make('admin_name')
                        ->label('Admin Name')
                        ->required()
                        ->dehydrated(false),

                    Forms\Components\TextInput::make('admin_email')
                        ->label('Admin Email')
                        ->email()
                        ->required()
                        ->unique('users', 'email')
                        ->dehydrated(false),

                    Forms\Components\TextInput::make('admin_password')
                        ->label('Admin Password')
                        ->password()
                        ->required()
                        ->dehydrated(false),
                ])
                ->visible(fn($livewire) => $livewire instanceof Pages\CreateRestaurant),
        ]);
    }

    public static function table(Table $table): Table
    {
        $bgImageUrl = asset('images/bg.png');

        return $table
            ->heading(new HtmlString('
                <style>
                    /* Custom Styling Omitted for brevity, kept exactly same as your code */
                    html, body, .fi-layout, .fi-main, .fi-page { background-color: transparent !important; background: transparent !important; }
                    body::before { content: ""; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-image: url("' . $bgImageUrl . '") !important; background-size: cover !important; background-position: center !important; background-attachment: fixed !important; opacity: 0.15 !important; z-index: -999 !important; pointer-events: none; }
                    .fi-ta-ctn { background: rgba(255, 255, 255, 0.45) !important; backdrop-filter: blur(16px) saturate(140%) !important; -webkit-backdrop-filter: blur(16px) saturate(140%) !important; border: 1.5px solid #000000 !important; border-radius: 1.25rem !important; box-shadow: 0 8px 32px rgba(42, 71, 149, 0.08) !important; overflow: hidden !important; color: #000000 !important; }
                    .fi-ta-header-ctn { background: rgba(255, 255, 255, 0.2) !important; border-bottom: 1.5px solid #000000 !important; }
                    .fi-ta-header-cell { background-color: transparent !important; }
                    .fi-ta-header-cell-label { color: #2a4795 !important; font-weight: 800 !important; text-transform: uppercase !important; letter-spacing: 0.05em !important; }
                    .fi-ta-cell-content, .fi-ta-text-item-label, .fi-ta-text-item-description { color: #0f172a !important; font-family: "Inter", sans-serif !important; }
                    .fi-ta-record { border-bottom: 1px solid rgba(0, 0, 0, 0.1) !important; background: transparent !important; transition: all 0.2s ease !important; }
                    .fi-ta-record:nth-child(odd):hover { background-color: rgba(42, 71, 149, 0.08) !important; }
                    .fi-ta-record:nth-child(even):hover { background-color: rgba(241, 107, 63, 0.08) !important; }
                    .fi-ta-content + div { background: rgba(255, 255, 255, 0.2) !important; border-top: 1.5px solid #000000 !important; }
                    .fi-input-wrapper { background-color: rgba(255, 255, 255, 0.5) !important; border: 1.5px solid #2a4795 !important; border-radius: 0.75rem !important; }
                    .fi-input-wrapper:focus-within { border-color: #f16b3f !important; box-shadow: 0 0 0 3px rgba(241, 107, 63, 0.2) !important; }
                    .dark .fi-ta-ctn { background: rgba(15, 15, 20, 0.7) !important; border: 1.5px solid #000000 !important; }
                    .dark .fi-ta-header-ctn { background: rgba(0, 0, 0, 0.3) !important; border-color: #000000 !important; }
                    .dark .fi-ta-header-cell-label { color: #456aba !important; }
                    .dark .fi-ta-cell-content, .dark .fi-ta-text-item-label, .dark .fi-ta-text-item-description { color: #f8fafc !important; }
                    .dark .fi-ta-record { border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important; }
                    .dark .fi-ta-record:nth-child(odd):hover { background-color: rgba(69, 106, 186, 0.15) !important; }
                    .dark .fi-ta-record:nth-child(even):hover { background-color: rgba(241, 107, 63, 0.15) !important; }
                    .dark .fi-ta-content + div { background: rgba(0, 0, 0, 0.3) !important; border-color: #000000 !important; }
                    .dark .fi-input-wrapper { background-color: rgba(0, 0, 0, 0.5) !important; border-color: #456aba !important; }
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

                Tables\Columns\TextColumn::make('phone_no')
                    ->label('PHONE')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_pay_first')
                    ->label('Pay First')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('gst_no')
                    ->label('GST No')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('table_limits')
                    ->label('Table Limit')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('address')
                    ->label('ADDRESS')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 30 ? $state : null;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

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

                Tables\Columns\IconColumn::make('is_rooms_facility')
                    ->label('Rooms Feature')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('has_inventory')
                    ->label('INVENTORY')
                    ->boolean()
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('has_detailed_inventory')
                    ->label('DETAILED INV')
                    ->boolean()
                    ->sortable()
                    ->alignCenter(),

                // 🌟 NAYA: Super Admin list mein dekh sakega kisko permission mili hai 🌟
                Tables\Columns\IconColumn::make('has_attendance')
                    ->label('ATTENDANCE')
                    ->boolean()
                    ->sortable()
                    ->alignCenter(),
                
                // Table ke columns array me `has_attendance` ke theek baad ye add karo:
                Tables\Columns\IconColumn::make('is_all_in_one_cafe')
                    ->label('ALL-IN-ONE')
                    ->boolean()
                    ->sortable()
                    ->alignCenter(),

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
                    ->label('')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->extraAttributes([
                        'style' => 'color: #456aba; transition: color 0.2s; display: inline-flex; padding: 6px; background: rgba(255,255,255,0.5); border-radius: 6px; border: 1.5px solid #000000;',
                        'onmouseover' => "this.style.color='#f16b3f'",
                        'onmouseout' => "this.style.color='#456aba'",
                    ]),

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