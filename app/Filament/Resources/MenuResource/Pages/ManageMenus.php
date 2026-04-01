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
        $fixedModalCss = new HtmlString('
            <style>
                .fi-modal-window { display: flex !important; flex-direction: column !important; max-height: 85vh !important; overflow: hidden !important; }
                .fi-modal-header { flex-shrink: 0 !important; border-bottom: 1px solid rgba(156, 163, 175, 0.3) !important; padding-bottom: 1rem !important; }
                .fi-modal-content { flex-grow: 1 !important; overflow-y: auto !important; padding: 1.5rem !important; }
                .fi-modal-footer { flex-shrink: 0 !important; border-top: 1px solid rgba(156, 163, 175, 0.3) !important; padding-top: 1rem !important; margin-top: 0 !important; }
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
                            'branch_id' => $user->branch_id, // Associates with branch automatically if branch admin
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
                            Forms\Components\TextInput::make('name')->required()->maxLength(150),
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

                                    // Branch Storage Path Rule
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
                            'branch_id' => $user->branch_id, // Associates with branch automatically if branch admin
                            'category_id' => $categoryId,
                            'name' => $itemData['name'],
                            'price' => $itemData['price'],
                            'type' => $itemData['type'], // 👈 ADD THIS LINE TO SAVE IT
                            'description' => $itemData['description'] ?? null,
                            'image_path' => $itemData['image_path'],
                            'is_available' => $itemData['is_available'],
                        ]);
                        $itemsAdded++;
                    }

                    Notification::make()->title("{$itemsAdded} Item(s) Added Successfully")->success()->send();
                }),

            // 3. MANAGE CATEGORIES ACTION
            // 3. MANAGE CATEGORIES ACTION
            Actions\Action::make('manageCategories')
                ->label('Manage Categories')
                ->extraAttributes(['class' => 'hidden-manage-category hidden'])
                ->modalHeading('Manage Categories')
                ->modalDescription(new HtmlString('
                    Update category names or toggle their availability.
                    <style>
                        /* Lock Modal Height to screen */
                        .fi-modal-window { display: flex !important; flex-direction: column !important; max-height: 85vh !important; overflow: hidden !important; }
                        .fi-modal-header { flex-shrink: 0 !important; border-bottom: 1px solid rgba(156, 163, 175, 0.3) !important; padding-bottom: 1rem !important; }
                        .fi-modal-content { flex-grow: 1 !important; overflow-y: auto !important; padding: 1.5rem !important; }
                        .fi-modal-footer { flex-shrink: 0 !important; border-top: 1px solid rgba(156, 163, 175, 0.3) !important; padding-top: 1rem !important; margin-top: 0 !important; }
                        .fi-fo-repeater-item-header-title, .fi-fo-repeater-item-header-icon { display: none !important; }
                        .fi-fo-repeater-item-header { background: transparent !important; border-bottom: none !important; padding: 0 !important; position: absolute !important; bottom: 1rem !important; right: 1rem !important; top: auto !important; min-height: auto !important; z-index: 10; }
                        .fi-fo-repeater-item { position: relative !important; border-radius: 12px !important; padding: 1rem 1rem 4rem 1rem !important; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02) !important; background-color: white !important; }
                        .dark .fi-fo-repeater-item { background-color: #1f2937 !important; }
                        .fi-fo-repeater-item:nth-child(odd) { border: 2px solid #3B82F6 !important; }
                        .fi-fo-repeater-item:nth-child(odd) button[role="switch"][aria-checked="true"] { background-color: #3B82F6 !important; }
                        .fi-fo-repeater-item:nth-child(even) { border: 2px solid #F47D20 !important; }
                        .fi-fo-repeater-item:nth-child(even) button[role="switch"][aria-checked="true"] { background-color: #F47D20 !important; }
                        .fi-fo-repeater-item-header button { color: #ef4444 !important; }
                        .absolute-bottom-left-toggle { position: absolute !important; bottom: 1rem !important; left: 1rem !important; margin: 0 !important; z-index: 20; }
                    </style>
                '))
                ->fillForm(function () {
                    $user = auth()->user();
                    
                    $query = Category::withoutGlobalScopes()
                        ->where('restaurant_id', $user->restaurant_id)
                        ->where(function($q) use ($user) {
                            $q->whereNull('branch_id'); // Load main categories
                            if ($user->branch_id) {
                                $q->orWhere('branch_id', $user->branch_id); // Load branch's own categories
                            }
                        });

                    $categories = $query->get()->map(function ($cat) use ($user) {
                        $isActive = $cat->is_active;
                        
                        // Check pivot status if branch admin is looking at a main category
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
                            'branch_id' => $cat->branch_id, // 👈 Ensures branch_id is loaded into the form
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
                            
                            // 👇 FIX: Added Hidden field to hold the branch_id in the repeater state
                            Forms\Components\Hidden::make('branch_id'),

                            Forms\Components\TextInput::make('name')
                                ->label('Name')
                                ->placeholder('Category Name')
                                ->required()
                                ->maxLength(100)
                                // Prevent branch admin from editing the NAME of a main category
                                ->disabled(fn(Forms\Get $get) => auth()->user()->branch_id !== null && $get('branch_id') === null),

                            Forms\Components\Toggle::make('is_active')
                                ->hiddenLabel()
                                ->inline(false)
                                ->extraAttributes(['class' => 'absolute-bottom-left-toggle']),
                        ])
                        ->addable(false)
                        ->reorderable(false)
                        // 👇 FIX: Only allow Branch Admin to delete their OWN categories (and safely checks for null)
                        ->deletable(fn(array $state) => auth()->user()->branch_id === null 
                            ? empty($state['branch_id']) 
                            : ($state['branch_id'] ?? null) === auth()->user()->branch_id
                        )
                        ->itemLabel(null),
                ])
                ->action(function (array $data) {
                    $user = auth()->user();

                    $submittedIds = collect($data['categories'] ?? [])->pluck('id')->filter()->toArray();

                    // 👇 FIX: Safely delete missing IDs based on role
                    $existingIdsQuery = Category::withoutGlobalScopes()->where('restaurant_id', $user->restaurant_id);
                    
                    if ($user->branch_id === null) {
                        $existingIdsQuery->whereNull('branch_id'); // Main admin compares/deletes only main categories
                    } else {
                        $existingIdsQuery->where('branch_id', $user->branch_id); // Branch admin compares/deletes only their own categories
                    }
                    
                    $existingIds = $existingIdsQuery->pluck('id')->toArray();
                    $idsToDelete = array_diff($existingIds, $submittedIds);

                    if (!empty($idsToDelete)) {
                        Category::withoutGlobalScopes()->whereIn('id', $idsToDelete)->delete();
                    }

                    // Resolve Updates
                    foreach ($data['categories'] ?? [] as $catData) {
                        if (empty($catData['id'])) continue;

                        $category = Category::withoutGlobalScopes()->find($catData['id']);
                        if (!$category) continue;

                        // If Branch Admin is modifying a Main Category -> Update Status Pivot
                        if ($user->branch_id !== null && $category->branch_id === null) {
                            DB::table('branch_category_status')->updateOrInsert(
                                ['category_id' => $category->id, 'branch_id' => $user->branch_id],
                                ['is_active' => $catData['is_active'], 'updated_at' => now()]
                            );
                        } else {
                            // Main Admin modifying Main, OR Branch Admin modifying Branch
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