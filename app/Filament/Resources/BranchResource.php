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

        // 1. Super Admin hamesha dekh sakta hai
        if ($user->isSuperAdmin()) {
            return true;
        }

        // 2. Restaurant Admin sirf tabhi dekh sakta hai jab uske restaurant ka 'has_branches' TRUE ho
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

        // 1. Super Admin ko saari branches dikhengi
        if ($user->isSuperAdmin()) {
            return $query;
        }

        // 2. Restaurant Admin ko sirf apne restaurant ki branches dikhengi
        if ($user->isRestaurantAdmin()) {
            return $query->where('restaurant_id', $user->restaurant_id);
        }

        // 3. Manager/Branch Admin ko sirf unki specific branch dikhegi (Optional safety)
        return $query->where('id', $user->branch_id);
    }

    /* -----------------------------------------------------------
       FORM
    ------------------------------------------------------------*/
    public static function form(Form $form): Form
    {
        return $form->schema([

            /* 👇 FIX: Dono fields ko combine kar diya taaki same naam se clash na ho 👇 */
            auth()->user()->isSuperAdmin()
            ? Forms\Components\Select::make('restaurant_id')
                ->label('Restaurant')
                ->options(Restaurant::where('has_branches', true)->pluck('name', 'id'))
                ->searchable()
                ->required()
            : Forms\Components\Hidden::make('restaurant_id')
                ->default(fn() => Auth::user()->restaurant_id),

            Forms\Components\TextInput::make('name')
                ->label('Branch Name')
                ->required()
                ->maxLength(255),

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
                        ->dehydrated(false), // dehydrated(false) ensures ye Branch table me save hone ki koshish na kare

                    Forms\Components\TextInput::make('admin_email')
                        ->label('Branch Admin Email')
                        ->email()
                        ->required()
                        ->unique('users', 'email') // Email pehle se na ho
                        ->dehydrated(false),

                    Forms\Components\TextInput::make('admin_password')
                        ->label('Branch Admin Password')
                        ->password()
                        ->required()
                        ->dehydrated(false),
                ])
                // YEH SECTION SIRF CREATE WALE PAGE PAR DIKHEGA
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

                // Super Admin ko pata chalna chahiye ye branch kis restaurant ki hai
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