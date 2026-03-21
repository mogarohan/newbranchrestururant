<?php

namespace App\Filament\Resources\MenuResource\Pages;

use App\Filament\Resources\MenuResource;
use App\Models\Category;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class ManageMenus extends ManageRecords
{
    protected static string $resource = MenuResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            MenuResource\Widgets\MenuDashboardStats::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            // 1. HIDDEN CATEGORY ACTION (Quick Add)
            Actions\Action::make('addCategory')
                ->label('Add Category')
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

            // 2. HIDDEN ITEM ACTION (Quick Add)
            Actions\CreateAction::make('addItem')
                ->label('Add Item')
                ->extraAttributes(['class' => 'hidden-add-item hidden'])
                ->visible(fn() => !auth()->user()->isBranchAdmin() && !auth()->user()->isManager()),
                
            // 👇 3. MANAGE CATEGORIES ACTION (Native Repeater Form)
            Actions\Action::make('manageCategories')
                ->label('Manage Categories')
                ->extraAttributes(['class' => 'hidden-manage-category hidden'])
               
                ->modalHeading('Manage Categories')
                // 👇 Injecting CSS to create the specific Vertical Card Layout & Scroll Lock
                ->modalDescription(new HtmlString('
                    Update category names or toggle their availability.
                    <style>
                        /* 👇 Scroll Lock Logic 👇 */
                        /* Target the Filament Slide-over window content */
                        .fi-modal-window {
                            display: flex !important;
                            flex-direction: column !important;
                            height: 100vh !important; /* Force full height */
                        }
                        .fi-modal-content {
                            flex-grow: 1 !important;
                            overflow-y: auto !important; /* Allow scrolling inside */
                            padding-bottom: 2rem !important; /* Give some breathing room at the bottom */
                        }
                        .fi-modal-header, .fi-modal-footer {
                            flex-shrink: 0 !important; /* Prevent header/footer from shrinking */
                            background: white !important; /* Keep background solid */
                            z-index: 20 !important;
                        }
                        .dark .fi-modal-header, .dark .fi-modal-footer {
                            background: #111827 !important;
                        }
                        /* 👇 End Scroll Lock Logic 👇 */


                        /* Hide the main header text/icon */
                        .fi-fo-repeater-item-header-title,
                        .fi-fo-repeater-item-header-icon {
                            display: none !important;
                        }
                        
                        /* Re-position the header (which holds the delete button) to the bottom right */
                        .fi-fo-repeater-item-header {
                            background: transparent !important;
                            border-bottom: none !important;
                            padding: 0 !important;
                            position: absolute !important;
                            bottom: 1rem !important; /* Pin to bottom */
                            right: 1rem !important;  /* Pin to right */
                            top: auto !important;
                            min-height: auto !important;
                            z-index: 10;
                        }

                        /* Main card styling */
                        .fi-fo-repeater-item {
                            position: relative !important;
                            border: 1px solid rgba(156, 163, 175, 0.3) !important;
                            border-radius: 12px !important;
                            padding: 1rem 1rem 4rem 1rem !important; /* Big padding at bottom for the toggle & delete btn */
                            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02) !important;
                            background-color: white !important;
                        }

                        .dark .fi-fo-repeater-item {
                            background-color: #1f2937 !important;
                            border-color: rgba(255,255,255,0.1) !important;
                        }
                        
                        /* Make delete button icon red */
                        .fi-fo-repeater-item-header button {
                            color: #ef4444 !important;
                        }

                        /* Position the toggle switch at the absolute bottom left */
                        .absolute-bottom-left-toggle {
                            position: absolute !important;
                            bottom: 1rem !important;
                            left: 1rem !important;
                            margin: 0 !important;
                            z-index: 20;
                        }
                    </style>
                '))
                ->fillForm(function () {
                    $user = auth()->user();
                    $query = Category::withoutGlobalScopes()->where('restaurant_id', $user->restaurant_id);
                    
                    if ($user->isBranchAdmin() || $user->isManager()) {
                        $query->whereNull('branch_id');
                    }

                    $categories = $query->get()->map(function ($cat) use ($user) {
                        $isActive = $cat->is_active;
                        if ($user->isBranchAdmin() || $user->isManager()) {
                            $status = DB::table('branch_category_status')
                                ->where('category_id', $cat->id)
                                ->where('branch_id', $user->branch_id)
                                ->first();
                            $isActive = $status ? (bool) $status->is_active : (bool) $cat->is_active;
                        }

                        return [
                            'id' => $cat->id,
                            'name' => $cat->name,
                            'is_active' => $isActive,
                        ];
                    })->toArray();

                    return ['categories' => $categories];
                })
                ->form([
                    Forms\Components\Repeater::make('categories')
                        ->hiddenLabel()
                        // 👇 THIS CREATES THE MULTIPLE CARDS IN A ROW 👇
                        ->grid([
                            'default' => 1,
                            'sm' => 3,
                            'md' => 4,
                            'xl' => 5, // Shows 4 cards per row on large screens
                        ])
                        ->schema([
                            Forms\Components\Hidden::make('id'),
                            
                            // 👇 Name Input at the top
                            Forms\Components\TextInput::make('name')
                                ->label('Name') 
                                ->placeholder('Category Name')
                                ->required()
                                ->maxLength(100)
                                ->disabled(fn() => auth()->user()->isBranchAdmin() || auth()->user()->isManager()),
                                
                            // 👇 Toggle anchored to the bottom left via custom CSS class
                            Forms\Components\Toggle::make('is_active')
                                ->hiddenLabel()
                                ->onColor('warning')
                                ->inline(false)
                                ->extraAttributes(['class' => 'absolute-bottom-left-toggle']), 
                        ])
                        ->addable(false)
                        ->deletable(fn() => !auth()->user()->isBranchAdmin() && !auth()->user()->isManager())
                        ->reorderable(false)
                        ->itemLabel(null), 
                ])
                ->action(function (array $data) {
                    $user = auth()->user();
                    
                    $submittedIds = collect($data['categories'] ?? [])
                        ->pluck('id')
                        ->filter()
                        ->toArray();
                    
                    if (!$user->isBranchAdmin() && !$user->isManager()) {
                        $existingIds = Category::withoutGlobalScopes()
                            ->where('restaurant_id', $user->restaurant_id)
                            ->pluck('id')
                            ->toArray();

                        $idsToDelete = array_diff($existingIds, $submittedIds);

                        if (!empty($idsToDelete)) {
                            Category::withoutGlobalScopes()
                                ->whereIn('id', $idsToDelete)
                                ->delete();
                        }
                    }

                    foreach ($data['categories'] ?? [] as $catData) {
                        if (empty($catData['id'])) continue;

                        $category = Category::withoutGlobalScopes()->find($catData['id']);
                        if (!$category) continue;

                        if ($user->isBranchAdmin() || $user->isManager()) {
                            DB::table('branch_category_status')->updateOrInsert(
                                ['category_id' => $category->id, 'branch_id' => $user->branch_id],
                                ['is_active' => $catData['is_active'], 'updated_at' => now()]
                            );
                        } else {
                            $category->update([
                                'name' => $catData['name'],
                                'is_active' => $catData['is_active'],
                            ]);
                        }
                    }

                    Notification::make()->title('Categories Updated')->success()->send();
                })
        ];
    }
}