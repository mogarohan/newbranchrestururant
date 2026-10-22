<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BranchResource\Pages;
use App\Models\Branch;
use App\Models\Restaurant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 2;

    /* -----------------------------------------------------------
       ACCESS CONTROL (Kon is page ko dekh sakta hai)
    ------------------------------------------------------------*/
    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        // if ($user->isRestaurantAdmin()) {
        //     return $user->restaurant && $user->restaurant->has_branches == true;
        // }

        return false;
    }

    /* -----------------------------------------------------------
       SHOW ONLY RESTAURANT BRANCHES (Data Isolation)
    ------------------------------------------------------------*/
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return $query;
        }

        if ($user->isRestaurantAdmin()) {
            return $query->where('restaurant_id', $user->restaurant_id);
        }

        return $query->where('id', $user->branch_id);
    }

   /* -----------------------------------------------------------
       FORM
    ------------------------------------------------------------*/
    public static function form(Form $form): Form
    {
        return $form->schema([

            auth()->user()->isSuperAdmin()
            ? Forms\Components\Select::make('restaurant_id')
                ->label('Restaurant')
                ->options(Restaurant::where('has_branches', true)->pluck('name', 'id'))
                ->searchable()
                ->required()
                ->live()
                ->default(function () {
                    $requestedRestaurantId = request()->query('restaurant_id');
                    
                    if ($requestedRestaurantId) {
                        return $requestedRestaurantId;
                    }
                    
                    return null;
                })
            : Forms\Components\Hidden::make('restaurant_id')
                ->default(fn() => Auth::user()->restaurant_id),

            /* 👇 BRANCH USAGE DISPLAY WITH DYNAMIC LIMIT 👇 */
            Forms\Components\Placeholder::make('branch_usage')
                ->label('Restaurant Branch Usage')
                ->visible(function (Forms\Get $get) {
                    $user = auth()->user();
                    if ($user->isSuperAdmin()) {
                        return filled($get('restaurant_id'));
                    }
                    return true;
                })
                ->content(function (Forms\Get $get) {
                    $user = auth()->user();
                    $restaurantId = $user->isSuperAdmin() ? $get('restaurant_id') : $user->restaurant_id;

                    if (!$restaurantId) return null;

                    // 👇 Get the dynamic limit from the database 👇
                    $restaurant = Restaurant::find($restaurantId);
                    if (!$restaurant) return null;
                    
                    $limit = $restaurant->max_branches; // Pulls from DB instead of hardcoded 3
                    $count = Branch::where('restaurant_id', $restaurantId)->count();

                    // If max_branches is null, it means unlimited
                    if ($limit === null) {
                         return new HtmlString("<span class='text-gray-700 dark:text-gray-300 font-bold'>{$count} branches used (Unlimited allowed)</span>");
                    }

                    if ($count >= $limit) {
                        return new HtmlString("
                            <div class='p-4 mb-2 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-red-900/30 dark:text-red-400 border border-red-200 dark:border-red-800' role='alert'>
                                <span class='font-black text-base'>⚠️ Limit Reached!</span><br>
                                {$count} / {$limit} branches used. <br>
                                <strong>Contact Super Admin to add more branches.</strong>
                            </div>
                        ");
                    }

                    return new HtmlString("<span class='text-gray-700 dark:text-gray-300 font-bold'>{$count} / {$limit} branches used</span>");
                })
                ->columnSpanFull(),

            Forms\Components\FileUpload::make('logo_path')
                ->label('Branch Logo')
                ->disk('public')
                ->directory('branch-logos')
                ->image()
                ->acceptedFileTypes([
                    'image/png',
                    'image/jpeg',
                    'image/jpg',
                    'image/svg+xml',
                    'image/webp',
                ])
                ->visibility('public')
                ->maxSize(2048)
                ->helperText('Custom logo for this branch. If empty, the main restaurant logo will be used automatically.')
                ->columnSpanFull(),

            Forms\Components\TextInput::make('name')
                ->label('Branch Name')
                ->required()
                ->maxLength(255)
                /* 👇 DYNAMIC VALIDATION LIMIT 👇 */
                ->rule(function (Forms\Get $get) {
                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                        $user = auth()->user();
                        $restaurantId = $user->isSuperAdmin() ? $get('restaurant_id') : $user->restaurant_id;

                        if ($restaurantId) {
                            $restaurant = Restaurant::find($restaurantId);
                            if (!$restaurant || $restaurant->max_branches === null) {
                                return; // Unlimited allowed
                            }

                            $limit = $restaurant->max_branches; // Pulls from DB
                            $count = Branch::where('restaurant_id', $restaurantId)->count();

                            if ($count >= $limit && request()->routeIs('filament.*.resources.branches.create')) {
                                $fail("Branch limit reached! Maximum allowed is {$limit}.");
                            }
                        }
                    };
                }),

            Forms\Components\TextInput::make('phone')
                ->tel()
                ->maxLength(20),

            Forms\Components\TextInput::make('gst_no')
                ->label('Branch GSTIN')
                ->placeholder('e.g., 27AAAAA0000A1Z5')
                ->maxLength(20)
                ->helperText('If not set, main restaurant GSTIN will apply.'),

            // 👇 NEW: UPI ID Field for Branch 👇
            Forms\Components\TextInput::make('upi_id')
                ->label('UPI ID (Optional)')
                ->placeholder('e.g., yourname@okhdfcbank')
                ->maxLength(255)
                ->helperText('If provided, payments at this branch will go to this UPI ID instead of the main restaurant.'),

            Forms\Components\TextInput::make('table_limits')
                ->label('Table Capacity Limit')
                ->numeric()
                ->default(0)
                ->helperText('Max tables allowed for this branch (0 for unlimited).'),

            Forms\Components\Toggle::make('is_pay_first')
                ->label('Pay First Model')
                ->helperText('Enable if customers at this branch must pay before ordering.')
                ->default(false),

            Forms\Components\Textarea::make('address')
                ->rows(3)
                ->columnSpanFull(),

            Forms\Components\Toggle::make('is_active')
                ->label('Active')
                ->default(true),

            /* 👇 MENU INHERITANCE CONFIGURATION 👇 */
            Forms\Components\Radio::make('inherit_restaurant_menu')
                ->label('Menu Catalog Configuration')
                ->options([
                    1 => 'Inherit Main Restaurant Menu (Shares central catalog with branch availability/price overrides)',
                    0 => 'Independent Menu (Branch starts with its own clean menu and independent categories)',
                ])
                ->default(1)
                ->inline(false)
                ->required()
                ->helperText('Choose whether this branch shares the parent restaurant menu items or maintains its own independent catalog.')
                ->columnSpanFull(),

            /* 👇 MODULES SECTION 👇 */
            Forms\Components\Section::make('Branch SaaS Modules & Features')
                ->description('Configure operational features and facilities for this branch.')
                ->schema([
                    Forms\Components\Toggle::make('is_rooms_facility')
                        ->label('Enable Rooms Facility')
                        ->helperText('Allow this branch to use Hotel/Rooms management & QR ordering.')
                        ->live()
                        ->default(false),

                    Forms\Components\TextInput::make('rooms_limit')
                        ->label('Maximum Rooms Allowed')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->visible(fn(Forms\Get $get) => (bool) $get('is_rooms_facility')),

                    Forms\Components\Toggle::make('has_inventory')
                        ->label('Enable Inventory & Stock Control')
                        ->helperText('Grants access to raw materials and stock tracking.')
                        ->default(false),

                    Forms\Components\Toggle::make('has_detailed_inventory')
                        ->label('Enable Detailed Auto Inventory')
                        ->helperText('Recipe-based ingredient deductions on orders.')
                        ->default(false),

                    Forms\Components\Toggle::make('has_attendance')
                        ->label('Enable Attendance & Payroll')
                        ->helperText('Manage branch staff attendance and payroll.')
                        ->default(false),

                    Forms\Components\Toggle::make('is_all_in_one_cafe')
                        ->label('Enable All-In-One Dashboard (QSR)')
                        ->helperText('Manager screen handles Kitchen & Serving directly.')
                        ->default(false),
                ])
                ->columns(2),

            /* 👇 ADMIN CREATION SECTION 👇 */
            Forms\Components\Section::make('Create Branch Admin')
                ->description('These credentials will be used by the branch admin to log in.')
                ->schema([
                    Forms\Components\TextInput::make('admin_name')
                        ->label('Branch Admin Name')
                        ->required()
                        ->dehydrated(false),

                    Forms\Components\TextInput::make('admin_email')
                        ->label('Branch Admin Email')
                        ->email()
                        ->required()
                        ->unique('users', 'email')
                        ->dehydrated(false),

                    Forms\Components\TextInput::make('admin_password')
                        ->label('Branch Admin Password')
                        ->password()
                        ->required()
                        ->dehydrated(false),
                ])
                ->visible(fn($livewire) => $livewire instanceof Pages\CreateBranch),

        ]);
    }

    /* -----------------------------------------------------------
       TABLE
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
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('restaurant.name')
                    ->label('Restaurant Brand')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->color('primary')
                    ->visible(fn() => auth()->user()->isSuperAdmin()),

                Tables\Columns\ImageColumn::make('logo_path')
                    ->label('Logo')
                    ->circular()
                    ->disk('public'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Branch Name')
                    ->searchable()
                    ->weight('bold')
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('gst_no')
                    ->label('GSTIN')
                    ->placeholder('-')
                    ->toggleable(),

                // 👇 NEW: Display UPI ID in Table 👇
                Tables\Columns\TextColumn::make('upi_id')
                    ->label('UPI ID')
                    ->searchable()
                    ->placeholder('Not Set')
                    ->copyable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('table_limits')
                    ->label('Table Limit')
                    ->formatStateUsing(fn($state) => $state > 0 ? $state : 'Unlimited')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('inherit_restaurant_menu')
                    ->label('Inherits Menu')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(fn($record) => $record->inherit_restaurant_menu ? 'Shares main restaurant menu' : 'Uses independent branch menu'),

                Tables\Columns\TextColumn::make('address')
                    ->limit(25),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->extraAttributes([
                        'style' => 'color: #456aba; transition: color 0.2s; display: inline-flex; padding: 6px; background: rgba(255,255,255,0.5); border-radius: 6px; border: 1px solid #000000;',
                        'onmouseover' => "this.style.color='#f16b3f'",
                        'onmouseout' => "this.style.color='#456aba'",
                    ]),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    /* -----------------------------------------------------------
       PAGES
    ------------------------------------------------------------*/
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBranches::route('/'),
            'create' => Pages\CreateBranch::route('/create'),
            'edit' => Pages\EditBranch::route('/{record}/edit'),
        ];
    }
}