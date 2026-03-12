<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MenuItemResource\Pages;
use App\Models\MenuItem;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class MenuItemResource extends Resource
{
    protected static ?string $model = MenuItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Menu Items';
    protected static ?string $navigationGroup = 'Menu Management';
    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return auth()->check()
            && auth()->user()->restaurant_id !== null
            && in_array(auth()->user()->role->name, [
                'restaurant_admin',
                'manager',
            ]);
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

            Forms\Components\Select::make('category_id')
                ->label('Category')
                ->required()
                ->options(fn () =>
                    Category::where('restaurant_id', auth()->user()->restaurant_id)
                        ->where('is_active', true)
                        ->pluck('name', 'id')
                )
                ->searchable(),

            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(150),

            Forms\Components\Textarea::make('description')
                ->maxLength(500)
                ->columnSpanFull(),

            Forms\Components\TextInput::make('price')
                ->numeric()
                ->minValue(0)
                ->required(),

            Forms\Components\FileUpload::make('image_path')
                ->label('Item Image')
                ->image()
                ->disk('public')
                ->directory(fn ($get) =>
                    'restaurants/' . auth()->user()->restaurant->slug . '/items'
                )
                ->imageEditor()
                ->maxSize(2048),

            Forms\Components\Toggle::make('is_available')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('Image')
                    ->circular()
                    ->size(50),

                Tables\Columns\TextColumn::make('name')
                    ->label('Item Name')
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->description(fn (MenuItem $record): string => $record->description ?? ''),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Appetizers' => 'info',
                        'Main Course' => 'warning',
                        'Desserts' => 'danger',
                        'Beverages' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('price')
                    ->label('Price (INR)')
                    ->money('INR')
                    ->weight('bold'),

                Tables\Columns\ToggleColumn::make('is_available')
                    ->label('Availability')
                    ->onColor('warning'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton(),
                Tables\Actions\DeleteAction::make()->iconButton(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMenuItems::route('/'),
            'create' => Pages\CreateMenuItem::route('/create'),
            'edit' => Pages\EditMenuItem::route('/{record}/edit'),
        ];
    }
}