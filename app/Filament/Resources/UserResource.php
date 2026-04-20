<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Models\Restaurant;
use App\Models\Branch;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\HtmlString;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Access Control';
    protected static ?string $navigationLabel = 'Staff Members';
    protected static ?string $modelLabel = 'Staff Member';
    protected static ?string $pluralModelLabel = 'Staff Members';
    protected static ?int $navigationSort = -1;

    /* ---------------------------------------------------
     | ACCESS CONTROL
     |---------------------------------------------------*/
    public static function canAccess(): bool
    {
        return auth()->check() && (
            auth()->user()->isSuperAdmin()
            || auth()->user()->isRestaurantAdmin()
            || auth()->user()->isBranchAdmin()
            || auth()->user()->isManager()
        );
    }

    public static function canCreate(): bool
    {
        return true;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $currentUser = auth()->user();

        if ($currentUser->isSuperAdmin()) {
            return true;
        }

        // 1. Har user ko sirf khud ki profile Edit karne ka haq hai
        if ($currentUser->id === $record->id) {
            return true;
        }

        $targetRole = strtolower(str_replace([' ', '-'], '_', $record->role?->name ?? ''));

        // 2. Restaurant Admin Logic (Branch Admin, Manager, Chef, Waiter sabko EDIT kar sakta hai)
        if ($currentUser->isRestaurantAdmin()) {
            return in_array($targetRole, ['branch_admin', 'manager', 'chef', 'waiter']);
        }

        // 3. Branch Admin Logic
        if ($currentUser->isBranchAdmin()) {
            return in_array($targetRole, ['manager', 'chef', 'waiter']);
        }

        // 4. Manager Logic
        if ($currentUser->isManager()) {
            return in_array($targetRole, ['chef', 'waiter']);
        }

        return false;
    }

    // 🔥 FIX: Delete Logic Updated for Branch Staff
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $currentUser = auth()->user();

        // 1. Koi bhi user khud ko DELETE nahi kar sakta (Safety First)
        if ($currentUser->id === $record->id) {
            return false;
        }

        if ($currentUser->isSuperAdmin()) {
            return true;
        }

        $targetRole = strtolower(str_replace([' ', '-'], '_', $record->role?->name ?? ''));

        // 2. Restaurant Admin Logic
        if ($currentUser->isRestaurantAdmin()) {
            // 🔥 NAYA LOGIC: Agar ye record kisi BRANCH ka hai (branch_id null nahi hai)
            // toh Restaurant Admin isko delete nahi kar sakta, sirf Edit kar sakta hai.
            if (!empty($record->branch_id)) {
                return false;
            }

            // Agar Main Restaurant ka staff hai (branch_id null hai), tabhi delete allowed hai
            return in_array($targetRole, ['manager', 'chef', 'waiter']);
        }

        // 3. Branch Admin Logic
        if ($currentUser->isBranchAdmin()) {
            return in_array($targetRole, ['manager', 'chef', 'waiter']);
        }

        // 4. Manager Logic
        if ($currentUser->isManager()) {
            return in_array($targetRole, ['chef', 'waiter']);
        }

        return false;
    }

    /* ---------------------------------------------------
     | DATA ISOLATION 
     |---------------------------------------------------*/
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

        if ($user->isBranchAdmin() || $user->isManager()) {
            return $query->where('restaurant_id', $user->restaurant_id)
                ->where('branch_id', $user->branch_id);
        }

        return $query->where('id', -1);
    }

    /* ---------------------------------------------------
     | FORM 
     |---------------------------------------------------*/
    public static function form(Form $form): Form
    {
        $user = auth()->user();
        $schema = [];

        if ($user->isSuperAdmin()) {
            $schema[] = Forms\Components\Select::make('restaurant_id')
                ->label('Restaurant')
                ->options(Restaurant::pluck('name', 'id'))
                ->searchable()
                ->reactive()
                ->required();

            $schema[] = Forms\Components\Select::make('branch_id')
                ->label('Branch')
                ->placeholder('Main Restaurant (Leave empty)')
                ->options(fn(callable $get) => Branch::where('restaurant_id', $get('restaurant_id'))->pluck('name', 'id'))
                ->searchable()
                ->visible(fn(callable $get) => Restaurant::find($get('restaurant_id'))?->has_branches ?? false)
                ->required(function (callable $get) {
                    $roleId = $get('role_id');
                    if (!$roleId)
                        return false;
                    $role = Role::find($roleId);
                    return $role && strtolower(str_replace([' ', '-'], '_', $role->name)) === 'branch_admin';
                });

        } elseif ($user->isRestaurantAdmin()) {
            $schema[] = Forms\Components\Hidden::make('restaurant_id')
                ->default($user->restaurant_id);

            $schema[] = Forms\Components\Select::make('branch_id')
                ->label('Branch')
                ->placeholder('Main Restaurant (Leave empty)')
                ->options(Branch::where('restaurant_id', $user->restaurant_id)->pluck('name', 'id'))
                ->searchable()
                ->visible(fn() => $user->restaurant?->has_branches ?? false)
                ->required(function (callable $get) {
                    $roleId = $get('role_id');
                    if (!$roleId)
                        return false;
                    $role = Role::find($roleId);
                    return $role && strtolower(str_replace([' ', '-'], '_', $role->name)) === 'branch_admin';
                });

        } else {
            $schema[] = Forms\Components\Hidden::make('restaurant_id')
                ->default($user->restaurant_id);

            $schema[] = Forms\Components\Hidden::make('branch_id')
                ->default($user->branch_id);
        }

        $commonFields = [
            Forms\Components\Placeholder::make('restaurant_user_stats')
                ->label('Restaurant User Usage')
                ->reactive()
                ->content(function (callable $get) use ($user) {
                    if ($user->isSuperAdmin()) {
                        $resId = $get('restaurant_id');
                        if (!$resId)
                            return 'Select a restaurant to see usage';
                        $restaurant = Restaurant::withCount('users')->find($resId);
                        return $restaurant ? "{$restaurant->users_count} / {$restaurant->user_limits} users used" : 'Restaurant not found';
                    }
                    $restaurant = $user->restaurant;
                    return $restaurant ? "{$restaurant->users()->count()} / {$restaurant->user_limits} users used" : 'No restaurant assigned';
                }),

            Forms\Components\TextInput::make('name')
                ->label('User Name')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true),

            Forms\Components\TextInput::make('password')
                ->password()
                ->required(fn($operation) => $operation === 'create')
                ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null)
                ->dehydrated(fn($state) => filled($state)),

            Forms\Components\Select::make('role_id')
                ->label('Role')
                ->required()
                ->reactive()
                ->options(fn() => self::availableRoles())
                ->default(function () {
                    $requestedRole = request()->query('role');
                    if ($requestedRole) {
                        return \App\Models\Role::where('name', $requestedRole)->value('id');
                    }
                    return null;
                }),

            Forms\Components\Toggle::make('is_active')
                ->default(true),
        ];

        return $form->schema(array_merge($schema, $commonFields));
    }

    /* ---------------------------------------------------
     | TABLE
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
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label('Avatar')
                    ->circular()
                    ->defaultImageUrl(fn($record) =>
                        'https://ui-avatars.com/api/?name='
                        . urlencode($record->name)
                        . '&color=FFFFFF&background=000000'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(
                        fn(User $record) =>
                        'Joined ' . ($record->created_at ? $record->created_at->format('M Y') : 'N/A')
                    ),

                Tables\Columns\TextColumn::make('email')
                    ->label('Contact Info')
                    ->searchable()
                    ->copyable()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Branch')
                    ->default('Main Restaurant')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('role.name')
                    ->label('Role')
                    ->badge()
                    ->color(function (string $state) {
                        $normalized = strtolower(str_replace([' ', '-'], '_', $state));
                        return match ($normalized) {
                            'chef', 'waiter', 'manager', 'branch_admin', 'restaurant_admin' => 'warning',
                            default => 'gray',
                        };
                    })
                    ->formatStateUsing(fn(string $state) => ucfirst($state)),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Active')
                    ->since()
                    ->color('primary'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn(\Illuminate\Database\Eloquent\Model $record) => static::canEdit($record))
                    ->color('warning')
                    ->button()
                    ->outlined(),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn(\Illuminate\Database\Eloquent\Model $record) => static::canDelete($record))
                    ->color('danger')
                    ->button()
                    ->outlined(),
            ]);
    }

    /* ---------------------------------------------------
     | ROLE FILTER (BULLETPROOF)
     |---------------------------------------------------*/
    protected static function availableRoles(): array
    {
        $user = auth()->user();
        $allRoles = Role::all();

        if ($user->isSuperAdmin()) {
            return $allRoles->pluck('name', 'id')->toArray();
        }

        if ($user->isRestaurantAdmin()) {
            $rolesAllowed = ['manager', 'chef', 'waiter'];

            if ($user->restaurant && $user->restaurant->has_branches) {
                $rolesAllowed[] = 'branch_admin';
            }

            return $allRoles->filter(function ($r) use ($rolesAllowed) {
                $normalized = strtolower(str_replace([' ', '-'], '_', $r->name));
                return in_array($normalized, $rolesAllowed);
            })->pluck('name', 'id')->toArray();
        }

        if ($user->isManager() || $user->isBranchAdmin()) {
            $rolesAllowed = ['chef', 'waiter'];

            if ($user->isBranchAdmin()) {
                $rolesAllowed[] = 'manager';
            }

            return $allRoles->filter(function ($r) use ($rolesAllowed) {
                $normalized = strtolower(str_replace([' ', '-'], '_', $r->name));
                return in_array($normalized, $rolesAllowed);
            })->pluck('name', 'id')->toArray();
        }

        return [];
    }

    /* ---------------------------------------------------
     | PAGES
     |---------------------------------------------------*/
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}