<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\DB; // 👈 IMPORT DB FOR PIVOT TABLE

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Categories';
    protected static ?string $navigationGroup = 'Menu Management';
    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return false;
        // return auth()->check()
        //     && auth()->user()->restaurant_id !== null
        //     && in_array(auth()->user()->role->name, ['restaurant_admin', 'manager', 'branch_admin']);
    }

    // 👇 Branch Admin / Manager naya nahi bana sakte
    public static function canCreate(): bool
    {
        return !auth()->user()->isBranchAdmin() && !auth()->user()->isManager(); 
    }

    // 👇 Edit ko TRUE karna zaroori hai, warna Filament Toggle button ko disable kar dega!
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return true;
    }

    // 👇 Branch Admin delete nahi kar sakte
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return !auth()->user()->isBranchAdmin() && !auth()->user()->isManager();
    }

    /* -----------------------------------------------------------
       DATA ISOLATION (FIXED)
    ------------------------------------------------------------*/
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        // 👇 FIX: withoutGlobalScopes() add kiya 
        $query = parent::getEloquentQuery()->withoutGlobalScopes(); 

        $query->where('restaurant_id', $user->restaurant_id);

        // 👇 Sirf Main Restaurant (null branch_id) ki categories dikhengi
        if ($user->isBranchAdmin() || $user->isManager()) {
            $query->whereNull('branch_id');
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Hidden::make('restaurant_id')
                ->default(fn() => auth()->user()->restaurant_id)
                ->required(),

            Forms\Components\Hidden::make('branch_id')
                ->default(fn() => auth()->user()->branch_id),

            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(100)
                ->unique(
                    table: 'categories',
                    column: 'name',
                    ignoreRecord: true,
                    modifyRuleUsing: function ($rule) {
                        $user = auth()->user();
                        $rule->where('restaurant_id', $user->restaurant_id);

                        if ($user->isBranchAdmin() || $user->isManager()) {
                            $rule->where('branch_id', $user->branch_id);
                        }

                        return $rule;
                    }
                ),

            Forms\Components\TextInput::make('sort_order')
                ->numeric()
                ->default(0)
                ->hidden(),

            Forms\Components\Toggle::make('is_active')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading(new HtmlString('
                <style>
                    .fi-ta-ctn { background-color: transparent !important; box-shadow: none !important; border: 1px solid rgba(156, 163, 175, 0.2) !important; }
                    .fi-ta-header-toolbar, .fi-ta-footer, .fi-ta-content, .fi-ta-table thead, .fi-ta-table th { background-color: transparent !important; border-color: rgba(156, 163, 175, 0.2) !important; }
                    .fi-ta-record { background-color: transparent !important; border-bottom: 1px solid rgba(156, 163, 175, 0.2) !important; transition: background-color 0.2s ease; }
                    .fi-ta-record:hover { background-color: rgba(234, 88, 12, 0.05) !important; }
                </style>
                <span style="font-size: 1.25rem; font-weight: 800;">Menu Categories</span>
            '))
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('CATEGORY NAME')
                    ->weight('bold')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('branch.name')
                    ->label('SOURCE')
                    ->default('Main Restaurant')
                    ->sortable()
                    ->color('gray'),

                // 👇 JADOO: Independent Category Toggle
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('STATUS')
                    ->onColor('warning')
                    // Disabled property hata di taaki click ho sake
                    ->getStateUsing(function (Category $record) {
                        $user = auth()->user();
                        if ($user->isBranchAdmin() || $user->isManager()) {
                            $status = DB::table('branch_category_status')
                                ->where('category_id', $record->id)
                                ->where('branch_id', $user->branch_id)
                                ->first();
                            return $status ? (bool) $status->is_active : (bool) $record->is_active;
                        }
                        return (bool) $record->is_active;
                    })
                    ->updateStateUsing(function (Category $record, $state) {
                        $user = auth()->user();
                        if ($user->isBranchAdmin() || $user->isManager()) {
                            DB::table('branch_category_status')->updateOrInsert(
                                ['category_id' => $record->id, 'branch_id' => $user->branch_id],
                                ['is_active' => $state, 'updated_at' => now()]
                            );
                        } else {
                            $record->update(['is_active' => $state]);
                        }
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('CREATED ON')
                    ->dateTime('d M, Y')
                    ->color('gray')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->button()
                    ->outlined()
                    ->color('warning')
                    // 👇 Edit permission ON hone ke bawajood, button ko HIDE kar diya Branch Admin ke liye
                    ->visible(fn() => !auth()->user()->isBranchAdmin() && !auth()->user()->isManager()),

                Tables\Actions\DeleteAction::make()
                    ->button()
                    ->outlined()
                    ->color('danger')
                    ->visible(fn() => !auth()->user()->isBranchAdmin() && !auth()->user()->isManager()),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn() => !auth()->user()->isBranchAdmin() && !auth()->user()->isManager()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}