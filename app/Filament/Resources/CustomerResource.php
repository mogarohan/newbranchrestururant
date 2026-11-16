<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers\OrdersRelationManager;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Customers & History';
    protected static ?string $navigationGroup = 'Operations';
    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->isRestaurantAdmin() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->check()
            && auth()->user()->restaurant_id
            && in_array(auth()->user()->role->name ?? '', ['manager', 'restaurant_admin', 'branch_admin']);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery()
            ->where('restaurant_id', $user->restaurant_id)
            ->with(['branch'])
            ->withCount('orders')
            ->orderBy('last_visit_at', 'desc');

        if ($user->isBranchAdmin() || $user->isManager()) {
            if ($user->branch_id) {
                $query->where(function ($q) use ($user) {
                    $q->where('branch_id', $user->branch_id)->orWhereNull('branch_id');
                });
            }
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Customer Name')
                    ->disabled(),
                Forms\Components\TextInput::make('phone')
                    ->label('Phone Number')
                    ->disabled(),
                Forms\Components\TextInput::make('total_orders_count')
                    ->label('Total Orders')
                    ->disabled(),
                Forms\Components\TextInput::make('total_spent')
                    ->label('Total Spent (₹)')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Customer Name')
                    ->weight('bold')
                    ->icon('heroicon-m-user')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone Number')
                    ->icon('heroicon-m-phone')
                    ->copyable()
                    ->copyMessage('Phone number copied')
                    ->searchable()
                    ->placeholder('Not Provided')
                    ->color(fn ($state) => $state ? 'primary' : 'gray'),

                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Outlet')
                    ->badge()
                    ->color('warning')
                    ->placeholder('Main Restaurant')
                    ->visible(fn () => auth()->user()?->isRestaurantAdmin() && (bool) auth()->user()?->restaurant?->has_branches),

                Tables\Columns\TextColumn::make('total_orders_count')
                    ->label('Orders')
                    ->badge()
                    ->color('info')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_spent')
                    ->label('Total Spent')
                    ->money('INR')
                    ->weight('bold')
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_visit_at')
                    ->label('Last Visit')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->placeholder('Never'),
            ])
            ->filters([
                SelectFilter::make('has_phone')
                    ->label('Phone Status')
                    ->options([
                        'with_phone' => 'With Phone Number',
                        'without_phone' => 'Without Phone Number',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'with_phone') {
                            $query->whereNotNull('phone')->where('phone', '!=', '');
                        } elseif ($data['value'] === 'without_phone') {
                            $query->where(function ($q) {
                                $q->whereNull('phone')->orWhere('phone', '');
                            });
                        }
                    }),

                Filter::make('last_visit_at')
                    ->form([
                        DatePicker::make('visited_from')->label('Visited From'),
                        DatePicker::make('visited_until')->label('Visited Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['visited_from'], fn (Builder $q, $date) => $q->whereDate('last_visit_at', '>=', $date))
                            ->when($data['visited_until'], fn (Builder $q, $date) => $q->whereDate('last_visit_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->isRestaurantAdmin() ?? false),
                ]),
            ])
            ->emptyStateHeading('No Customers Found')
            ->emptyStateDescription('When customers join a session via QR scan, they will automatically appear here.')
            ->emptyStateIcon('heroicon-o-user-group');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Customer Profile')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('Customer Name')
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('phone')
                            ->label('Phone Number')
                            ->placeholder('Not Provided')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('branch.name')
                            ->label('Branch')
                            ->placeholder('Main Restaurant'),

                        Infolists\Components\TextEntry::make('total_orders_count')
                            ->label('Lifetime Orders Count')
                            ->badge()
                            ->color('info'),

                        Infolists\Components\TextEntry::make('total_spent')
                            ->label('Lifetime Spend')
                            ->money('INR')
                            ->color('success')
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('last_visit_at')
                            ->label('Last Visit Date')
                            ->dateTime('d M Y, h:i A'),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            OrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'view' => Pages\ViewCustomer::route('/{record}'),
        ];
    }
}
