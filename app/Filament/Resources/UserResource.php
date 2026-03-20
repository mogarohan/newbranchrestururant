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

    // 👇 YEH 3 FUNCTIONS ADD KIYE HAIN "CREATE/EDIT" BUTTONS KO WAPAS LAANE KE LIYE 👇
    public static function canCreate(): bool
    {
        return true; // Force enable Create Button
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return true; // Force enable Edit Button
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        // Sirf Super, Restaurant aur Branch admin delete kar sakte hain
        return auth()->user()->isSuperAdmin()
            || auth()->user()->isRestaurantAdmin()
            || auth()->user()->isBranchAdmin();
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

        return $query->where('branch_id', $user->branch_id);
    }

    /* ---------------------------------------------------
     | FORM (DYNAMICALLY BUILT TO FIX 404 BUG)
     |---------------------------------------------------*/
    public static function form(Form $form): Form
    {
        $user = auth()->user();
        $schema = [];

        /* =========================================================
           1. DYNAMIC RESTAURANT & BRANCH ASSIGNMENT
           (Fixed 404 Not Found issue by preventing duplicate fields)
        ========================================================= */

        if ($user->isSuperAdmin()) {
            /* SUPER ADMIN CAN SELECT RESTAURANT */
            $schema[] = Forms\Components\Select::make('restaurant_id')
                ->label('Restaurant')
                ->options(Restaurant::pluck('name', 'id'))
                ->searchable()
                ->reactive() // Triggers update for Branch dropdown & User Stats
                ->required();

            /* DYNAMIC BRANCH SELECTION FOR SUPER ADMIN */
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
            /* AUTO ASSIGN RESTAURANT FOR RESTAURANT ADMIN */
            $schema[] = Forms\Components\Hidden::make('restaurant_id')
                ->default($user->restaurant_id);

            /* DYNAMIC BRANCH SELECTION FOR RESTAURANT ADMIN */
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
            /* AUTO ASSIGN BOTH RESTAURANT AND BRANCH FOR MANAGERS/BRANCH ADMINS */
            $schema[] = Forms\Components\Hidden::make('restaurant_id')
                ->default($user->restaurant_id);

            $schema[] = Forms\Components\Hidden::make('branch_id')
                ->default($user->branch_id);
        }

        /* =========================================================
           2. COMMON USER FIELDS
        ========================================================= */
        $commonFields = [
            /* USER LIMIT INFO */
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

            /* NAME */
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),

            /* EMAIL */
            Forms\Components\TextInput::make('email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true),

            /* PASSWORD */
            Forms\Components\TextInput::make('password')
                ->password()
                ->required(fn($operation) => $operation === 'create')
                ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null)
                ->dehydrated(fn($state) => filled($state)),

            /* ROLE SELECT */
            Forms\Components\Select::make('role_id')
                ->label('Role')
                ->required()
                ->reactive()
                ->options(fn() => self::availableRoles())
                ->default(function () {
                    $requestedRole = request()->query('role'); // Get '?role=xxx' from URL
        
                    if ($requestedRole) {
                        // Fetch the ID directly from the DB
                        return \App\Models\Role::where('name', $requestedRole)->value('id');
                    }

                    return null;
                }),

            /* ACTIVE STATUS */
            Forms\Components\Toggle::make('is_active')
                ->default(true),
        ];

        // Combine dynamic logic with regular fields
        return $form->schema(array_merge($schema, $commonFields));
    }

    /* ---------------------------------------------------
     | TABLE
     |---------------------------------------------------*/
public static function table(Table $table): Table
{
    return $table
        ->heading(new HtmlString('
            <style>
                /* --- LIGHT THEME (BLUE) --- */
                .fi-ta-ctn {
                    background-color: transparent !important;
                    box-shadow: none !important;
                    border: 1px solid rgba(156,163,175,0.2) !important;
                    color: #000000 !important;
                }

                .fi-ta-cell-content, 
                .fi-ta-text-item-label,
                .fi-ta-text-item-description,
                .fi-ta-header-cell-label {
                    color: #000000 !important;
                }

                .fi-ta-header-cell-label { font-weight: 800 !important; }

                /* Row Hover - Blue with low opacity */
                .fi-ta-record:hover {
                    background-color: rgba(30, 64, 175, 0.1) !important;
                }

                /* Active Icon - Green */
                .fi-ta-icon-item {
                    color: #20af1e !important;
                }

                /* --- DARK THEME (WHITE TEXT) --- */
                .dark .fi-ta-ctn,
                .dark .fi-ta-cell-content, 
                .dark .fi-ta-text-item-label,
                .dark .fi-ta-text-item-description,
                .dark .fi-ta-header-cell-label {
                    color: #ffffff !important;
                }

                .dark .fi-ta-record:hover {
                    background-color: rgba(255, 255, 255, 0.1) !important;
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
                ->color('warning')
                ->button()
                ->outlined(),

            Tables\Actions\DeleteAction::make()
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
        $allRoles = Role::all(); // Saare roles ek baar DB se nikal liye

        /* SUPER ADMIN → ALL ROLES */
        if ($user->isSuperAdmin()) {
            return $allRoles->pluck('name', 'id')->toArray();
        }

        /* RESTAURANT ADMIN */
        if ($user->isRestaurantAdmin()) {
            $rolesAllowed = ['manager', 'chef', 'waiter'];

            // Agar restaurant me branches allowed hain, tabhi 'branch_admin' role create karne do
            if ($user->restaurant && $user->restaurant->has_branches) {
                $rolesAllowed[] = 'branch_admin';
            }

            // DB ke naam ko match karne ke liye smart filter
            return $allRoles->filter(function ($r) use ($rolesAllowed) {
                $normalized = strtolower(str_replace([' ', '-'], '_', $r->name));
                return in_array($normalized, $rolesAllowed);
            })->pluck('name', 'id')->toArray();
        }

        /* MANAGER / BRANCH ADMIN */
        if ($user->isManager() || $user->isBranchAdmin()) {
            $rolesAllowed = ['chef', 'waiter'];

            // Branch Admin ko apne managers create karne ki permission allow ki gayi hai
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