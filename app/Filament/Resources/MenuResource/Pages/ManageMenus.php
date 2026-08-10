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
            // 0. MANAGE CATEGORIES — same Repeater/modal style as "Add Categories", no separate blade/widget needed.
            // Pre-fills with existing categories; toggle = active/inactive, trash icon = delete, edit name inline.
            Actions\Action::make('manageCategories')
                ->label('Manage Categories')
                ->modalHeading('Manage Categories')
                ->modalDescription($fixedModalCss)
                ->extraAttributes(['class' => 'hidden-manage-category hidden'])
                ->fillForm(function () {
                    $user = auth()->user();
                    // branch_id null hone par kabhi bhi branch_category_status table use nahi karna (warna insert fail hoga)
                    $isBranchOverride = ($user->isBranchAdmin() || $user->isManager()) && $user->branch_id !== null;

                    $query = Category::withoutGlobalScopes()
                        ->where('restaurant_id', $user->restaurant_id);

                    if ($isBranchOverride) {
                        $query->whereNull('branch_id');
                    }

                    $categories = $query->orderBy('name')->get();

                    return [
                        'categories' => $categories->map(function ($cat) use ($user, $isBranchOverride) {
                            $isActive = (bool) $cat->is_active;

                            if ($isBranchOverride) {
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
                        })->toArray(),
                    ];
                })
                ->form([
                    Forms\Components\Repeater::make('categories')
                        ->label('Your Categories')
                        ->addActionLabel('+ Add New Category')
                        ->schema([
                            Forms\Components\Hidden::make('id'),

                            Forms\Components\TextInput::make('name')
                                ->label('Category Name')
                                ->required()
                                ->maxLength(100)
                                ->disabled(fn() => (auth()->user()->isBranchAdmin() || auth()->user()->isManager()) && auth()->user()->branch_id !== null)
                                ->dehydrated(true), // disabled ho tab bhi value form data mein bheji jaaye

                            Forms\Components\Toggle::make('is_active')
                                ->label('Active')
                                ->default(true),
                        ])
                        ->columns(2)
                        ->itemLabel(fn(array $state): ?string => $state['name'] ?? null)
                        ->deletable(fn() => !((auth()->user()->isBranchAdmin() || auth()->user()->isManager()) && auth()->user()->branch_id !== null))
                        ->addable(fn() => !((auth()->user()->isBranchAdmin() || auth()->user()->isManager()) && auth()->user()->branch_id !== null))
                        ->reorderable(false),
                ])
                ->action(function (array $data) {
                    $user = auth()->user();
                    // branch_id null hone par kabhi bhi branch_category_status table use nahi karna (warna insert fail hoga)
                    $isBranchOverride = ($user->isBranchAdmin() || $user->isManager()) && $user->branch_id !== null;
                    $submittedIds = [];

                    foreach ($data['categories'] as $catData) {
                        if (!empty($catData['id'])) {
                            $submittedIds[] = $catData['id'];
                            $category = Category::withoutGlobalScopes()->find($catData['id']);
                            if (!$category) continue;

                            if ($isBranchOverride) {
                                DB::table('branch_category_status')->updateOrInsert(
                                    ['category_id' => $category->id, 'branch_id' => $user->branch_id],
                                    ['is_active' => $catData['is_active'], 'updated_at' => now()]
                                );
                            } else {
                                $category->update([
                                    'name' => $catData['name'] ?? $category->name,
                                    'is_active' => $catData['is_active'] ?? $category->is_active,
                                ]);
                            }
                        } elseif (!$isBranchOverride) {
                            // New category added via repeater
                            Category::create([
                                'restaurant_id' => $user->restaurant_id,
                                'branch_id' => $user->branch_id,
                                'name' => $catData['name'] ?? '',
                                'is_active' => $catData['is_active'] ?? true,
                            ]);
                        }
                    }

                    // Delete categories removed from the repeater (owner only, branch overrides never delete)
                    if (!$isBranchOverride) {
                        $existingIds = Category::withoutGlobalScopes()
                            ->where('restaurant_id', $user->restaurant_id)
                            ->pluck('id')->toArray();
                        $toDelete = array_diff($existingIds, $submittedIds);
                        if (!empty($toDelete)) {
                            Category::whereIn('id', $toDelete)->delete();
                        }
                    }

                    Notification::make()->title('Categories updated successfully')->success()->send();
                }),

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
                        ->itemLabel(fn(array $state): ?string => $state['name'] ?? null),
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
                                ->where(function ($q) use ($user) {
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
                                ->options(['veg' => 'Veg', 'non-veg' => 'Non-Veg',])
                                ->default('veg')
                                ->required(),
                            Forms\Components\TextInput::make('stock_quantity')
                                ->numeric()
                                ->placeholder('Unlimited')
                                ->default(null), // FIX: Ensures blank values are sent as null, not 0
                            Forms\Components\TextInput::make('low_stock_threshold')
                                ->numeric()
                                ->default(3)
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
                                ->maxSize(2048),

                            Forms\Components\Toggle::make('is_available')
                                ->default(true)
                                ->label('Available')
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->itemLabel(fn(array $state): ?string => $state['name'] ?? null),
                ])
                ->action(function (array $data) {
                    $user = auth()->user();
                    $categoryId = $data['category_id'];
                    $itemsAdded = 0;

                    foreach ($data['items'] as $item) {
                        MenuItem::create([
                            'restaurant_id' => $user->restaurant_id,
                            'branch_id' => $user->branch_id,
                            'category_id' => $categoryId,
                            'name' => $item['name'],
                            'price' => $item['price'],
                            'type' => $item['type'],
                            'stock_quantity' => blank($item['stock_quantity']) ? null : $item['stock_quantity'], // FIX: Blank check
                            'low_stock_threshold' => $item['low_stock_threshold'] ?? 3,
                            'description' => $item['description'] ?? null,
                            'image_path' => $item['image_path'],
                            'is_available' => $item['is_available'],
                        ]);
                        $itemsAdded++;
                    }

                    Notification::make()->title("{$itemsAdded} Item(s) Added")->success()->send();
                }),
        ];
    }
}