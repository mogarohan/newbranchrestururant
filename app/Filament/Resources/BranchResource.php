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

        if ($user->isRestaurantAdmin()) {
            return $user->restaurant && $user->restaurant->has_branches == true;
        }

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
                // 👇 YAHAN PE URL SE ID GRAB KARNE WALA LOGIC ADD KIYA 👇
                ->default(function () {
                    $requestedRestaurantId = request()->query('restaurant_id');
                    
                    if ($requestedRestaurantId) {
                        return $requestedRestaurantId;
                    }
                    
                    return null;
                })
            : Forms\Components\Hidden::make('restaurant_id')
                ->default(fn() => Auth::user()->restaurant_id),

            /* 👇 BRANCH USAGE DISPLAY WITH ALERT MESSAGE 👇 */
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

                    $limit = 3; // Aap is limit ko apne hisaab se change kar sakte hain
                    $count = Branch::where('restaurant_id', $restaurantId)->count();

                    // Agar limit poori ho gayi hai, toh ek bada Red Warning Box dikhega
                    if ($count >= $limit) {
                        return new HtmlString("
                            <div class='p-4 mb-2 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-red-900/30 dark:text-red-400 border border-red-200 dark:border-red-800' role='alert'>
                                <span class='font-black text-base'>⚠️ Limit Reached!</span><br>
                                {$count} / {$limit} branches used. <br>
                                <strong>Contact Super Admin to add more branches.</strong>
                            </div>
                        ");
                    }

                    // Agar limit bachi hai, toh normal text dikhega
                    return new HtmlString("<span class='text-gray-700 dark:text-gray-300 font-bold'>{$count} / {$limit} branches used</span>");
                })
                ->columnSpanFull(),

            Forms\Components\TextInput::make('name')
                ->label('Branch Name')
                ->required()
                ->maxLength(255)
                /* 👇 SAVE KARNE SE ROKNE KE LIYE VALIDATION 👇 */
                ->rule(function (Forms\Get $get) {
                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                        $user = auth()->user();
                        $restaurantId = $user->isSuperAdmin() ? $get('restaurant_id') : $user->restaurant_id;

                        if ($restaurantId) {
                            $limit = 3; // Same limit yahan bhi rakhein
                            $count = Branch::where('restaurant_id', $restaurantId)->count();

                            // Agar naya record create ho raha hai aur limit cross ho gayi hai
                            if ($count >= $limit && request()->routeIs('filament.*.resources.branches.create')) {
                                $fail('Branch limit reached! Contact Super Admin to add more branches.');
                            }
                        }
                    };
                }),

            Forms\Components\TextInput::make('phone')
                ->tel()
                ->maxLength(20),

            Forms\Components\Textarea::make('address')
                ->rows(3)
                ->columnSpanFull(),

            Forms\Components\Toggle::make('is_active')
                ->label('Active')
                ->default(true),

            /* 👇 NAYI BRANCH BANATE WAQT BRANCH ADMIN CREATE KARNE KE LIYE FIELDS 👇 */
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
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('restaurant.name')
                    ->label('Restaurant Brand')
                    ->sortable()
                    ->searchable()
                    ->visible(fn() => auth()->user()->isSuperAdmin()),

                Tables\Columns\TextColumn::make('name')
                    ->label('Branch Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),

                Tables\Columns\TextColumn::make('address')
                    ->limit(30),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
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