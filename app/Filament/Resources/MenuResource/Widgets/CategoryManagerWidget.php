<?php

namespace App\Filament\Resources\MenuResource\Widgets;

use App\Models\Category;
use App\Models\Branch;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class CategoryManagerWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    // Unique ID for CSS/JS targeting
    protected function getExtraAttributes(): array
    {
        return [
            'id' => 'category-manager-inner',
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $user = auth()->user();
                $query = Category::withoutGlobalScopes()
                    ->where('restaurant_id', $user->restaurant_id);

                if ($user->isRestaurantAdmin()) {
                    // Main restaurant admin can view all categories across central and branches
                } elseif ($user->isBranchAdmin() || ($user->isManager() && $user->branch_id)) {
                    $branch = $user->branch_id ? Branch::find($user->branch_id) : null;
                    if ($branch && !$branch->inherit_restaurant_menu) {
                        $query->where('branch_id', $user->branch_id);
                    } else {
                        $query->where(function ($q) use ($user) {
                            $q->whereNull('branch_id');
                            if ($user->branch_id) {
                                $q->orWhere('branch_id', $user->branch_id);
                            }
                        });
                    }
                } else {
                    // Main restaurant manager (branch_id is null): ONLY main restaurant categories!
                    $query->whereNull('branch_id');
                }

                return $query;
            })
            ->heading(new HtmlString('
                <style>
                    /* --- 🌟 GLASS EFFECT & BLACK BORDER FOR WIDGET --- */
                    #category-manager-inner {
                        background: rgba(255, 255, 255, 0.45) !important;
                        backdrop-filter: blur(16px) saturate(140%) !important;
                        -webkit-backdrop-filter: blur(16px) saturate(140%) !important;
                        border: 1.5px solid #000000 !important; /* BLACK BORDER */
                        border-radius: 1.25rem !important;
                        box-shadow: 0 8px 32px rgba(42, 71, 149, 0.08) !important;
                        overflow: hidden !important;
                    }
                    .dark #category-manager-inner {
                        background: rgba(15, 15, 20, 0.7) !important;
                    }

                    /* --- HEADER STYLING --- */
                    .fi-ta-header-ctn {
                        background: rgba(255, 255, 255, 0.2) !important;
                        border-bottom: 1.5px solid #000000 !important;
                    }
                    .fi-ta-header-cell-label {
                        color: #2a4795 !important; /* BRAND BLUE */
                        font-weight: 800 !important;
                        text-transform: uppercase !important;
                    }

                    /* --- ROW HOVER EFFECT --- */
                    .fi-ta-record {
                        transition: all 0.2s ease !important;
                        background: transparent !important;
                    }
                    .fi-ta-record:nth-child(odd):hover {
                        background-color: rgba(42, 71, 149, 0.08) !important;
                    }
                    .fi-ta-record:even:hover {
                        background-color: rgba(241, 107, 63, 0.08) !important;
                    }
                </style>

                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;"
                     x-init="
                        window.addEventListener(\'open-category-manager\', () => {
                            $el.closest(\'#category-manager-inner\').classList.add(\'force-show\');
                            let outerWrapper = $el.closest(\'.fi-wi\');
                            if(outerWrapper) {
                                outerWrapper.style.display = \'block\';
                                setTimeout(() => outerWrapper.scrollIntoView({behavior: \'smooth\', block: \'start\'}), 100);
                            }
                        });
                     "
                >
                    <span style="font-weight: 900; color: #0f172a;">Manage Categories</span>
                    
                    <button type="button" onclick="let inner = this.closest(\'#category-manager-inner\'); if(inner) { inner.classList.remove(\'force-show\'); let outer = inner.closest(\'.fi-wi\'); if(outer) outer.style.display = \'none\'; }" style="color: #000000; padding: 4px; border-radius: 6px; transition: all 0.2s; cursor: pointer;" onmouseover="this.style.color=\'#ef4444\'; this.style.backgroundColor=\'rgba(239, 68, 68, 0.1)\'" onmouseout="this.style.color=\'#000000\'; this.style.backgroundColor=\'transparent\'" title="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 20px; height: 20px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            '))
            ->description('Enable or disable categories for your menu.')
            ->defaultPaginationPageOption(5)
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('CATEGORY NAME')
                    ->weight('bold')
                    ->searchable()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('origin')
                    ->label('ORIGIN')
                    ->badge()
                    ->state(fn(Category $record) => $record->branch_id === null ? '🏢 Inherited (Main)' : '🏪 Branch Custom')
                    ->color(fn(Category $record) => $record->branch_id === null ? 'info' : 'warning'),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('STATUS')
                    ->onColor('warning')
                    ->getStateUsing(function (Category $record) {
                        $user = auth()->user();
                        if ($user->isBranchAdmin() || $user->isManager()) {
                            if ($record->branch_id === null) {
                                $status = DB::table('branch_category_status')
                                    ->where('category_id', $record->id)
                                    ->where('branch_id', $user->branch_id)
                                    ->first();
                                return $status ? (bool) $status->is_active : (bool) $record->is_active;
                            }
                        }
                        return (bool) $record->is_active;
                    })
                    ->updateStateUsing(function (Category $record, $state) {
                        $user = auth()->user();
                        if (($user->isBranchAdmin() || $user->isManager()) && $record->branch_id === null) {
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
                    ->label('')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->extraAttributes([
                        'style' => 'color: #2a4795; transition: color 0.2s; display: inline-flex; padding: 6px; background: rgba(255,255,255,0.5); border-radius: 6px; border: 1px solid #000000;',
                        'onmouseover' => "this.style.color='#f16b3f'",
                        'onmouseout' => "this.style.color='#2a4795'",
                    ])
                    ->form([
                        \Filament\Forms\Components\TextInput::make('name')->required()->maxLength(100),
                    ])
                    ->visible(fn(Category $record) => auth()->user()->isRestaurantAdmin() || (auth()->user()->branch_id && $record->branch_id === auth()->user()->branch_id)),
                
                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->extraAttributes([
                        'style' => 'color: #ef4444; transition: color 0.2s; display: inline-flex; padding: 6px; background: rgba(255,255,255,0.5); border-radius: 6px; border: 1px solid #000000;',
                        'onmouseover' => "this.style.color='#b91c1c'",
                        'onmouseout' => "this.style.color='#ef4444'",
                    ])
                    ->visible(fn(Category $record) => auth()->user()->isRestaurantAdmin() || (auth()->user()->branch_id && $record->branch_id === auth()->user()->branch_id)),
            ]);
    }
}