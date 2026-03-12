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
use Illuminate\Support\HtmlString;

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

            Forms\Components\Select::make('restaurant_id')
                ->label('Restaurant')
                ->options(Restaurant::pluck('name', 'id'))
                ->searchable()
                ->reactive()
                ->visible(fn() => auth()->user()->isSuperAdmin())
                ->required(fn() => auth()->user()->isSuperAdmin()),

            Placeholder::make('restaurant_user_stats')
                ->label('Restaurant User Usage')
                ->reactive()
                ->content(function (callable $get) {
                    $authUser = auth()->user();

                    if ($authUser->isSuperAdmin()) {
                        $restaurantId = $get('restaurant_id');
                        if (!$restaurantId)
                            return 'Select a restaurant to see user usage.';
                        $restaurant = Restaurant::withCount('users')->find($restaurantId);
                        if (!$restaurant)
                            return 'Restaurant not found.';
                        return "{$restaurant->users_count} / {$restaurant->user_limits} users used";
                    }

                    $restaurant = $authUser->restaurant;
                    if (!$restaurant)
                        return 'No restaurant assigned.';
                    return "{$restaurant->users()->count()} / {$restaurant->user_limits} users used";
                }),

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

                // 1. Avatar
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label('Avatar')
                    ->circular()
                    ->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=FFFFFF&background=111827'),

                // 2. Name & Joined Date stacked
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn(User $record): string => 'Joined ' . ($record->created_at ? $record->created_at->format('M Y') : 'N/A')),

                // 3. Email / Contact Info
                Tables\Columns\TextColumn::make('email')
                    ->label('Contact Info')
                    ->searchable()
                    ->copyable()
                    ->color('gray'),

                // 4. Role Badge
                Tables\Columns\TextColumn::make('role.name')
                    ->label('Role')
                    ->badge()
                    ->color(fn(string $state): string => match (strtolower($state)) {
                        'chef' => 'warning',
                        'waiter' => 'info',
                        'manager' => 'primary',
                        'super_admin' => 'gray', // Match image style for SA
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                // 5. Status (Boolean Outline Icon like image)
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Is Active')
                    ->boolean()
                    ->alignCenter(),

                // 6. Last Active
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Active')
                    ->since()
                    ->color('gray'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                // Actions moved outside of dropdown, styled exactly like image
                Tables\Actions\EditAction::make()
                    ->color('warning') // Yellow/Orange Edit
                    ->button()         // Make it look like a button or distinct link
                    ->outlined(),      // Gives it the premium outline look

                Tables\Actions\DeleteAction::make()
                    ->color('danger')
                    ->button()
                    ->outlined(),
            ])
            ->bulkActions([]); // Disabled bulk delete as per standard
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