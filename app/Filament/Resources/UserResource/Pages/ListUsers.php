<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make()
            //     ->label('Create User')
            //     ->icon('heroicon-o-user-plus')
            //     ->color('danger'), // Matches the orange button in your UI
        ];
    }

    // This adds the Top Stat Cards
    protected function getHeaderWidgets(): array
    {
        return [
            UserResource\Widgets\StaffStatsWidget::class,
        ];
    }

    // This adds the "All Staff", "Kitchen", "Front of House" Tabs
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Staff'),
            
            'management' => Tab::make('Management')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('role', fn($q) => $q->where('name', 'manager'))),

            'kitchen' => Tab::make('Chef')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('role', fn($q) => $q->where('name', 'chef'))),
            'front_of_house' => Tab::make('Waiter')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('role', fn($q) => $q->where('name', 'waiter'))),
            ];
    }
}