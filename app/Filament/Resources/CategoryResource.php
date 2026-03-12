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
use Illuminate\Support\HtmlString; // 👈 CSS Injection ke liye zaroori hai

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Categories';
    protected static ?string $navigationGroup = 'Menu Management';
    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->check()
            && auth()->user()->restaurant_id !== null
            && in_array(auth()->user()->role->name, ['restaurant_admin', 'manager']);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('restaurant_id', auth()->user()->restaurant_id);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Hidden::make('restaurant_id')
                ->default(fn () => auth()->user()->restaurant_id)
                ->required(),

            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(100)
                ->unique(
                    table: 'categories',
                    column: 'name',
                    ignoreRecord: true,
                    modifyRuleUsing: fn ($rule) => $rule->where('restaurant_id', auth()->user()->restaurant_id)
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
                    /* Row Hover Effect */
                    .fi-ta-record:hover {
                        background-color: rgba(234, 88, 12, 0.05) !important; /* Slight orange tint on hover */
                    }
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

                // Toggle column matches the orange theme
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('STATUS')
                    ->onColor('warning'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('CREATED ON')
                    ->dateTime('d M, Y')
                    ->color('gray')
                    ->sortable(),
            ])
            ->actions([
                // Styled Actions matching Premium UI
                Tables\Actions\EditAction::make()
                    ->button()
                    ->outlined()
                    ->color('warning'),

                Tables\Actions\DeleteAction::make()
                    ->button()
                    ->outlined()
                    ->color('danger')
                    ->visible(fn () => auth()->user()->role->name === 'restaurant_admin'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()->role->name === 'restaurant_admin'),
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