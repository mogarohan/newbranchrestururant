<?php

namespace App\Filament\Resources\MenuResource\Widgets;

use App\Models\Category;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class CategoryManagerWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    // 👇 UPDATED: Added display: none so it is hidden the second the page loads
    protected function getExtraAttributes(): array
    {
        return [
            'id' => 'category-table-wrapper',
            'style' => 'display: none;', 
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $user = auth()->user();
                $query = Category::withoutGlobalScopes()
                    ->where('restaurant_id', $user->restaurant_id);

                if ($user->isBranchAdmin() || $user->isManager()) {
                    $query->whereNull('branch_id');
                }

                return $query;
            })
            // 👇 UPDATED: Cleaned up heading. The Close button just hides the div again.
            ->heading(new HtmlString('
                <div id="category-manager-header" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <span>Manage Categories</span>
                    <button type="button" onclick="document.getElementById(\'category-table-wrapper\').style.display = \'none\';" style="color: #9ca3af; padding: 4px; border-radius: 6px; transition: all 0.2s; cursor: pointer;" onmouseover="this.style.color=\'#ef4444\'; this.style.backgroundColor=\'rgba(239, 68, 68, 0.1)\'" onmouseout="this.style.color=\'#9ca3af\'; this.style.backgroundColor=\'transparent\'" title="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            '))
            ->description('Active categories will appear as filters for your menu items.')
            ->defaultPaginationPageOption(5)
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Status')
                    ->onColor('warning')
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
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form([
                        \Filament\Forms\Components\TextInput::make('name')->required()->maxLength(100),
                    ])
                    ->visible(fn() => !auth()->user()->isBranchAdmin() && !auth()->user()->isManager()),
                
                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => !auth()->user()->isBranchAdmin() && !auth()->user()->isManager()),
            ]);
    }
}