<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Models\Restaurant;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Placeholder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Access Control';
       protected static ?int $navigationSort = -1;


    /* ---------------------------------------------------
     | ACCESS CONTROL (WHO CAN SEE THE RESOURCE)
     |---------------------------------------------------*/
    public static function canAccess(): bool
    {
        return auth()->check() && (
            auth()->user()->isSuperAdmin()
            || auth()->user()->isRestaurantAdmin()
            || auth()->user()->isManager()
        );
    }
    protected static function getRestaurantStats(): array
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return [
                'count' => null,
                'limit' => null,
            ];
        }

        $restaurant = $user->restaurant;

        return [
            'count' => $restaurant->users()->count(),
            'limit' => $restaurant->user_limits,
        ];
    }

    /* ---------------------------------------------------
     | DATA ISOLATION (WHO SEES WHICH USERS)
     |---------------------------------------------------*/
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()->isSuperAdmin()) {
            return $query;
        }

        return $query->where('restaurant_id', auth()->user()->restaurant_id);
    }

    /* ---------------------------------------------------
     | FORM
     |---------------------------------------------------*/
    public static function form(Form $form): Form
    {
        return $form->schema([

            /* =========================
               RESTAURANT FIELD
            ========================== */

            Forms\Components\Select::make('restaurant_id')
                ->label('Restaurant')
                ->options(Restaurant::pluck('name', 'id'))
                ->searchable()
                ->reactive() // ✅ VERY IMPORTANT
                ->visible(fn() => auth()->user()->isSuperAdmin())
                ->required(fn() => auth()->user()->isSuperAdmin()),

            /* =========================
               USER LIMIT STATS
            ========================== */

            Placeholder::make('restaurant_user_stats')
                ->label('Restaurant User Usage')
                ->reactive() // ✅ VERY IMPORTANT
                ->content(function (callable $get) {

                    $authUser = auth()->user();

                    // 🟢 Super Admin
                    if ($authUser->isSuperAdmin()) {
                        $restaurantId = $get('restaurant_id');

                        if (!$restaurantId) {
                            return 'Select a restaurant to see user usage.';
                        }

                        $restaurant = Restaurant::withCount('users')->find($restaurantId);

                        if (!$restaurant) {
                            return 'Restaurant not found.';
                        }

                        return "{$restaurant->users_count} / {$restaurant->user_limits} users used";
                    }

                    // 🟢 Restaurant Admin / Manager
                    $restaurant = $authUser->restaurant;

                    if (!$restaurant) {
                        return 'No restaurant assigned.';
                    }

                    return "{$restaurant->users()->count()} / {$restaurant->user_limits} users used";
                }),

            /* =========================
               USER FIELDS
            ========================== */

            Forms\Components\TextInput::make('name')
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
                ->options(fn() => self::availableRoles()),

            Forms\Components\Toggle::make('is_active')
                ->default(true),
        ]);
    }

    /* ---------------------------------------------------
     | TABLE
     |---------------------------------------------------*/
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Avatar (Assuming you have an avatar column or method. If not, Filament handles null gracefully or you can use ui-avatars)
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=FFFFFF&background=111827'),

                // Name & Joined Date stacked
                Tables\Columns\TextColumn::make('name')
                    ->label('NAME')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn(User $record): string => 'Joined ' . ($record->created_at ? $record->created_at->format('M Y') : 'N/A')),

                // Role Badge
                Tables\Columns\TextColumn::make('role.name')
                    ->label('ROLE')
                    ->badge()
                    ->color(fn(string $state): string => match (strtolower($state)) {
                        'chef' => 'warning',
                        'waiter' => 'info',
                        'manager' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                // Status Badge
                Tables\Columns\TextColumn::make('is_active')
                    ->label('STATUS')
                    ->badge()
                    ->formatStateUsing(fn(bool $state): string => $state ? 'Active' : 'Offline')
                    ->color(fn(bool $state): string => $state ? 'danger' : 'gray') // Using danger for the orange look
                    ->icon(fn(bool $state): string => $state ? 'heroicon-m-sparkles' : ''), // Adds a tiny icon to active status

                // Email
                Tables\Columns\TextColumn::make('email')
                    ->label('CONTACT'),

                // Performance Bar
               
                // Last Active (Using created_at or updated_at as a fallback if you don't track sessions yet)
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('LAST ACTIVE')
                    ->since()
                    ->color('gray'),
            ])
            ->actions([
                // Group actions under the three dots (...)
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
                    ->icon('heroicon-m-ellipsis-horizontal')
                    ->color('gray'),
            ]);
    }

    /* =========================
       ROLE FILTERING LOGIC
    ========================== */

    protected static function availableRoles(): array
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return Role::pluck('name', 'id')->toArray();
        }

        if ($user->isRestaurantAdmin()) {
            return Role::whereIn('name', ['manager', 'chef', 'waiter'])
                ->pluck('name', 'id')->toArray();
        }

        if ($user->isManager()) {
            return Role::whereIn('name', ['chef', 'waiter'])
                ->pluck('name', 'id')->toArray();
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