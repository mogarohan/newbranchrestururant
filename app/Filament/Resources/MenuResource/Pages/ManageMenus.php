<?php

namespace App\Filament\Resources\MenuResource\Pages;

use App\Filament\Resources\MenuResource;
use App\Models\Category;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Support\Facades\DB;

class ManageMenus extends ManageRecords
{
    protected static string $resource = MenuResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            MenuResource\Widgets\MenuDashboardStats::class,
           MenuResource\Widgets\CategoryManagerWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            // 1. HIDDEN CATEGORY ACTION (Triggered by Widget for quick add)
            Actions\Action::make('addCategory')
                ->label('Add Category')
                ->model(Category::class)
                ->extraAttributes(['class' => 'hidden-add-category hidden'])
                ->form([
                    Forms\Components\Hidden::make('restaurant_id')->default(auth()->user()->restaurant_id),
                    Forms\Components\Hidden::make('branch_id')->default(auth()->user()->branch_id),
                    Forms\Components\TextInput::make('name')->required()->maxLength(100),
                    Forms\Components\Toggle::make('is_active')->default(true)->label('Active'),
                ])
                ->action(function (array $data) {
                    Category::create($data);
                    Notification::make()->title('Category Added Successfully')->success()->send();
                })
                ->visible(fn() => !auth()->user()->isBranchAdmin() && !auth()->user()->isManager()),

            // 2. HIDDEN ITEM ACTION (Triggered by Widget)
            Actions\CreateAction::make('addItem')
                ->label('Add Item')
                ->extraAttributes(['class' => 'hidden-add-item hidden'])
                ->visible(fn() => !auth()->user()->isBranchAdmin() && !auth()->user()->isManager()),
                
            // 3. VISIBLE MANAGE CATEGORIES BUTTON (Top Right)
            // Actions\Action::make('manageCategories')
            //     ->label('Manage Categories')
            //     ->icon('heroicon-o-folder-open')
            //     ->color('gray')
            //     ->url(fn (): string => route('filament.admin.resources.categories.index')) // 👈 We will restore CategoryResource just for background management
            //     ->visible(fn() => !auth()->user()->isBranchAdmin() && !auth()->user()->isManager()),
        ];
    }
}