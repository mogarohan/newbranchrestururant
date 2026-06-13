<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MeasurementUnitResource\Pages;
use App\Models\MeasurementUnit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MeasurementUnitResource extends Resource
{
    protected static ?string $model = MeasurementUnit::class;
    protected static ?string $navigationIcon = 'heroicon-o-scale';
    protected static ?string $navigationLabel = 'Measurement Units';
    protected static ?string $navigationGroup = 'Detailed Inventory';
    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return auth()->check()
            && $user->restaurant_id
            && (bool) $user->restaurant?->has_detailed_inventory
            && in_array($user->role->name ?? null, ['manager', 'branch_admin', 'restaurant_admin']);
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
                ->default(fn() => auth()->user()->restaurant_id),

            Forms\Components\TextInput::make('name')
                ->label('Unit Name')
                ->placeholder('e.g., Kilogram')
                ->required()
                ->maxLength(50),

            Forms\Components\TextInput::make('short_name')
                ->label('Short Name')
                ->placeholder('e.g., kg')
                ->required()
                ->maxLength(10),

            Forms\Components\TextInput::make('base_unit')
                ->label('Base Unit (for conversion)')
                ->placeholder('e.g., gram')
                ->helperText('Leave blank if this IS the base unit')
                ->maxLength(50),

            Forms\Components\TextInput::make('conversion_factor')
                ->label('Conversion Factor')
                ->numeric()
                ->default(1)
                ->helperText('How many base units = 1 of this unit (e.g., 1 kg = 1000 grams → enter 1000)'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('UNIT NAME')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('short_name')
                    ->label('SHORT')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('base_unit')
                    ->label('BASE UNIT')
                    ->placeholder('—')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('conversion_factor')
                    ->label('FACTOR')
                    ->numeric(4),

                Tables\Columns\TextColumn::make('grocery_items_count')
                    ->label('ITEMS USING')
                    ->counts('groceryItems')
                    ->badge()
                    ->color('info'),
            ])
            ->defaultSort('name', 'asc')
            ->actions([
                Tables\Actions\EditAction::make()->button()->size('xs'),
                Tables\Actions\DeleteAction::make()->button()->size('xs')->outlined(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageMeasurementUnits::route('/'),
        ];
    }
}
