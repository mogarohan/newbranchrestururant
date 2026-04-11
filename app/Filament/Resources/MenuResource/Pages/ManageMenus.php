<?php

namespace App\Filament\Resources\MenuResource\Pages;

use App\Filament\Resources\MenuResource;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Branch;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
        $bgImageUrl = asset('images/bg.png');

        // 👇 GLOBAL STYLES FOR PAGE BACKGROUND, MODAL GLASS EFFECT & BLACK BORDERS 👇
        $fixedModalCss = new HtmlString('
            <style>
                /* --- 🌟 MAKE FILAMENT WRAPPERS TRANSPARENT --- */
                html, body, .fi-layout, .fi-main, .fi-page {
                    background-color: transparent !important;
                    background: transparent !important;
                }

                /* --- 🌟 BACKGROUND IMAGE WITH 0.15 OPACITY --- */
                body::before {
                    content: "";
                    position: fixed;
                    top: 0; left: 0; right: 0; bottom: 0;
                    background-image: url("' . $bgImageUrl . '") !important;
                    background-size: cover !important;
                    background-position: center !important;
                    background-attachment: fixed !important;
                    opacity: 0.15 !important;
                    z-index: -999 !important;
                    pointer-events: none;
                }

                /* --- 🎨 MODAL GLASS EFFECT & BLACK BORDER --- */
                .fi-modal-window {
                    background: rgba(255, 255, 255, 0.45) !important;
                    backdrop-filter: blur(16px) saturate(140%) !important;
                    -webkit-backdrop-filter: blur(16px) saturate(140%) !important;
                    border: 1.5px solid #000000 !important; /* BLACK BORDER */
                    box-shadow: 0 8px 32px rgba(42, 71, 149, 0.08) !important;
                    border-radius: 1.25rem !important;
                    display: flex !important; 
                    flex-direction: column !important; 
                    max-height: 85vh !important; 
                    overflow: hidden !important;
                }
                .dark .fi-modal-window {
                    background: rgba(15, 15, 20, 0.7) !important;
                }
                
                .fi-modal-header { 
                    flex-shrink: 0 !important; 
                    border-bottom: 1.5px solid rgba(0,0,0,0.1) !important; 
                    padding-bottom: 1rem !important; 
                    background: rgba(255,255,255,0.2) !important; 
                }
                .dark .fi-modal-header { 
                    border-bottom: 1.5px solid rgba(255,255,255,0.1) !important; 
                    background: rgba(0,0,0,0.2) !important; 
                }
                
                .fi-modal-content { 
                    flex-grow: 1 !important; 
                    overflow-y: auto !important; 
                    padding: 1.5rem !important; 
                }
                
                .fi-modal-footer { 
                    flex-shrink: 0 !important; 
                    border-top: 1.5px solid rgba(0,0,0,0.1) !important; 
                    padding-top: 1rem !important; 
                    margin-top: 0 !important; 
                    background: rgba(255,255,255,0.2) !important; 
                }
                .dark .fi-modal-footer { 
                    border-top: 1.5px solid rgba(255,255,255,0.1) !important; 
                    background: rgba(0,0,0,0.2) !important; 
                }

                /* Inputs in Modal */
                .fi-input-wrapper {
                    border: 1.5px solid #000000 !important; /* BLACK BORDER FOR INPUTS */
                    background: rgba(255,255,255,0.5) !important;
                    border-radius: 0.75rem !important;
                }
                .dark .fi-input-wrapper { background: rgba(0,0,0,0.5) !important; }
                
                .fi-input-wrapper:focus-within {
                    border-color: #f16b3f !important;
                    box-shadow: 0 0 0 3px rgba(241, 107, 63, 0.2) !important;
                }
            </style>
        ');

        return [
            // 1. BULK ADD CATEGORY ACTION
            Actions\Action::make('addCategory')
                ->label('Add Categories')
                ->modalHeading('Add New Categories')
                ->modalDescription($fixedModalCss) 
                ->extraAttributes(['class' => 'hidden-add-category hidden'])
                ->form([
                    Forms\Components\Repeater::make('categories')
                        ->label('Categories to Add')
                        ->addActionLabel('Add Another Category')
                        ->minItems(1)
                        ->defaultItems(1)
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Category Name')
                                ->required()
                                ->maxLength(100),
                            
                            Forms\Components\Toggle::make('is_active')
                                ->label('Active by Default')
                                ->default(true),
                        ])
                        ->columns(2)
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                ])
                ->action(function (array $data) {
                    $user = auth()->user();
                    $categoriesAdded = 0;

                    foreach ($data['categories'] as $catData) {
                        Category::create([
                            'restaurant_id' => $user->restaurant_id,
                            'branch_id' => $user->branch_id, 
                            'name' => $catData['name'],
                            'is_active' => $catData['is_active'],
                        ]);
                        $categoriesAdded++;
                    }

                    Notification::make()->title("{$categoriesAdded} Categor(ies) Added Successfully")->success()->send();
                }),

            // 2. BULK ADD ITEM ACTION
            Actions\Action::make('addItem')
                ->label('Add Items')
                ->modalHeading('Add Items to Category')
                ->modalDescription($fixedModalCss) 
                ->extraAttributes(['class' => 'hidden-add-item hidden'])
                ->form([
                    Forms\Components\Select::make('category_id')
                        ->label('Target Category')
                        ->required()
                        ->options(function () {
                            $user = auth()->user();
                            $query = Category::withoutGlobalScopes()
                                ->where('restaurant_id', $user->restaurant_id)
                                ->where('is_active', true)
                                ->where(function($q) use ($user) {
                                    $q->whereNull('branch_id');
                                    if ($user->branch_id) {
                                        $q->orWhere('branch_id', $user->branch_id);
                                    }
                                });
                            return $query->pluck('name', 'id');
                        })
                        ->searchable()
                        ->columnSpanFull()
                        ->helperText('Select the category these items belong to.'),

                    Forms\Components\Repeater::make('items')
                        ->label('Menu Items')
                        ->addActionLabel('Add Another Item')
                        ->minItems(1)
                        ->defaultItems(1)
                        ->schema([
                            Forms\Components\TextInput::make('name')->label('Item Name')->required()->maxLength(150),
                            Forms\Components\TextInput::make('price')->numeric()->minValue(0)->required()->prefix('₹'),
                            Forms\Components\Select::make('type')->label('Type')
                                ->options(['veg' => 'Veg','non-veg' => 'Non-Veg',])
                                ->default('veg')
                                ->required(),
                            Forms\Components\Textarea::make('description')->maxLength(500)->rows(6),
                            
                            Forms\Components\FileUpload::make('image_path')
                                ->label('Item Image')
                                ->image()
                                ->disk('public')
                                ->directory(function (callable $get) {
                                    $user = auth()->user();
                                    $restaurantSlug = Str::slug($user->restaurant->name ?? 'restaurant');
                                    
                                    $categoryId = $get('../../category_id');
                                    $categoryName = Category::find($categoryId)?->name ?? 'uncategorized';
                                    $categorySlug = Str::slug($categoryName);

                                    if ($user->branch_id) {
                                        $branchName = Branch::find($user->branch_id)?->name ?? 'branch';
                                        $branchSlug = Str::slug($branchName);
                                        return "restaurants/{$restaurantSlug}/branches/{$branchSlug}/Categories/{$categorySlug}";
                                    }

                                    return "restaurants/{$restaurantSlug}/Categories/{$categorySlug}";
                                })
                                ->getUploadedFileNameForStorageUsing(function (\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file, callable $get): string {
                                    $itemName = Str::slug($get('name') ?? 'item');
                                    $extension = $file->getClientOriginalExtension();
                                    return "{$itemName}.{$extension}";
                                })
                                ->imageEditor()
                                ->required()
                                ->maxSize(2048),

                            Forms\Components\Toggle::make('is_available')
                                ->default(true)
                                ->label('Available')
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                ])
                ->action(function (array $data) {
                    $user = auth()->user();
                    $categoryId = $data['category_id'];
                    $itemsAdded = 0;

                    foreach ($data['items'] as $itemData) {
                        MenuItem::create([
                            'restaurant_id' => $user->restaurant_id,
                            'branch_id' => $user->branch_id, 
                            'category_id' => $categoryId,
                            'name' => $itemData['name'],
                            'price' => $itemData['price'],
                            'type' => $itemData['type'],
                            'description' => $itemData['description'] ?? null,
                            'image_path' => $itemData['image_path'],
                            'is_available' => $itemData['is_available'],
                        ]);
                        $itemsAdded++;
                    }

                    Notification::make()->title("{$itemsAdded} Item(s) Added Successfully")->success()->send();
                }),

            // 3. MANAGE CATEGORIES ACTION
            Actions\Action::make('manageCategories')
                ->label('Manage Categories')
                ->extraAttributes(['class' => 'hidden-manage-category hidden'])
                ->modalHeading('Manage Categories')
                ->modalDescription(new HtmlString($fixedModalCss . '
                    Update category names or toggle their availability.
                    <style>
                        .fi-fo-repeater-item-header-title, .fi-fo-repeater-item-header-icon { display: none !important; }
                        .fi-fo-repeater-item-header { background: transparent !important; border-bottom: none !important; padding: 0 !important; position: absolute !important; bottom: 1rem !important; right: 1rem !important; top: auto !important; min-height: auto !important; z-index: 10; }
                        
                        /* 👇 ALTERNATING GLASS CATEGORY CARDS WITH BLACK BORDER 👇 */
                        .fi-fo-repeater-item { 
                            position: relative !important; 
                            border-radius: 12px !important; 
                            padding: 1rem 1rem 4rem 1rem !important; 
                            background: rgba(255, 255, 255, 0.3) !important;
                            backdrop-filter: blur(8px) !important;
                            border: 1.5px solid #000000 !important; /* BLACK BORDER */
                            box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important;
                            transition: all 0.2s ease !important;
                        }
                        .fi-fo-repeater-item:hover { transform: translateY(-2px) !important; }
                        .dark .fi-fo-repeater-item { background: rgba(0, 0, 0, 0.3) !important; }

                        /* Alternate Colors - Blue */
                        .fi-fo-repeater-item:nth-child(odd) { 
                            border-top: 4px solid #2a4795 !important; 
                            background: rgba(42, 71, 149, 0.05) !important;
                        }
                        .fi-fo-repeater-item:nth-child(odd) button[role="switch"][aria-checked="true"] { background-color: #2a4795 !important; }

                        /* Alternate Colors - Orange */
                        .fi-fo-repeater-item:nth-child(even) { 
                            border-top: 4px solid #f16b3f !important; 
                            background: rgba(241, 107, 63, 0.05) !important;
                        }
                        .fi-fo-repeater-item:nth-child(even) button[role="switch"][aria-checked="true"] { background-color: #f16b3f !important; }

                        .fi-fo-repeater-item-header button { color: #ef4444 !important; }
                        .absolute-bottom-left-toggle { position: absolute !important; bottom: 1rem !important; left: 1rem !important; margin: 0 !important; z-index: 20; }
                    </style>
                '))
                ->fillForm(function () {
                    $user = auth()->user();
                    
                    $query = Category::withoutGlobalScopes()
                        ->where('restaurant_id', $user->restaurant_id)
                        ->where(function($q) use ($user) {
                            $q->whereNull('branch_id'); 
                            if ($user->branch_id) {
                                $q->orWhere('branch_id', $user->branch_id); 
                            }
                        });

                    $categories = $query->get()->map(function ($cat) use ($user) {
                        $isActive = $cat->is_active;
                        
                        if ($user->branch_id !== null && $cat->branch_id === null) {
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
                            'branch_id' => $cat->branch_id, 
                        ];
                    })->toArray();

                    return ['categories' => $categories];
                })
                ->form([
                    Forms\Components\Repeater::make('categories')
                        ->hiddenLabel()
                        ->grid([
                            'default' => 1,
                            'sm' => 3,
                            'md' => 4,
                            'xl' => 5,
                        ])
                        ->schema([
                            Forms\Components\Hidden::make('id'),
                            Forms\Components\Hidden::make('branch_id'),

                            Forms\Components\TextInput::make('name')
                                ->label('Name')
                                ->placeholder('Category Name')
                                ->required()
                                ->maxLength(100)
                                ->disabled(fn(Forms\Get $get) => auth()->user()->branch_id !== null && $get('branch_id') === null),

                            Forms\Components\Toggle::make('is_active')
                                ->hiddenLabel()
                                ->inline(false)
                                ->extraAttributes(['class' => 'absolute-bottom-left-toggle']),
                        ])
                        ->addable(false)
                        ->reorderable(false)
                        ->deletable(fn(array $state) => auth()->user()->branch_id === null 
                            ? empty($state['branch_id']) 
                            : ($state['branch_id'] ?? null) === auth()->user()->branch_id
                        )
                        ->itemLabel(null),
                ])
                ->action(function (array $data) {
                    $user = auth()->user();

                    $submittedIds = collect($data['categories'] ?? [])->pluck('id')->filter()->toArray();

                    $existingIdsQuery = Category::withoutGlobalScopes()->where('restaurant_id', $user->restaurant_id);
                    
                    if ($user->branch_id === null) {
                        $existingIdsQuery->whereNull('branch_id'); 
                    } else {
                        $existingIdsQuery->where('branch_id', $user->branch_id); 
                    }
                    
                    $existingIds = $existingIdsQuery->pluck('id')->toArray();
                    $idsToDelete = array_diff($existingIds, $submittedIds);

                    if (!empty($idsToDelete)) {
                        Category::withoutGlobalScopes()->whereIn('id', $idsToDelete)->delete();
                    }

                    foreach ($data['categories'] ?? [] as $catData) {
                        if (empty($catData['id'])) continue;

                        $category = Category::withoutGlobalScopes()->find($catData['id']);
                        if (!$category) continue;

                        if ($user->branch_id !== null && $category->branch_id === null) {
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