<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MenuResource\Pages;
use App\Models\MenuItem;
use App\Models\Category;
use App\Models\Branch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class MenuResource extends Resource
{
    protected static ?string $model = MenuItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Menu Dashboard';
    protected static ?string $navigationGroup = 'Menu Management';
    protected static ?int $navigationSort = 1;

    /* -----------------------------------------------------------
       PERMISSIONS
    ------------------------------------------------------------*/
    public static function canAccess(): bool
    {
        return auth()->check()
            && auth()->user()->restaurant_id !== null
            && in_array(auth()->user()->role->name, ['restaurant_admin', 'manager', 'branch_admin']);
    }

    public static function canCreate(): bool
    {
        return true; 
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return true; 
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = auth()->user();
        if ($user->branch_id === null) return $record->branch_id === null;
        return $record->branch_id === $user->branch_id;
    }

    /* -----------------------------------------------------------
       DATA ISOLATION
    ------------------------------------------------------------*/
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery()->withoutGlobalScopes();
        
        $query->where('restaurant_id', $user->restaurant_id)
              ->where(function($q) use ($user) {
                  $q->whereNull('branch_id'); 
                  if ($user->branch_id) {
                      $q->orWhere('branch_id', $user->branch_id); 
                  }
              });

        return $query;
    }

    /* -----------------------------------------------------------
       CARD GRID LAYOUT & FILTERS
    ------------------------------------------------------------*/
    public static function table(Table $table): Table
    {
        return $table
            ->heading(new HtmlString('
                <style>
                    /* Card Base */
                    .fi-ta-record {
                        border-radius: 12px !important;
                        background-color: #ffffff !important;
                        border: 2px solid #3B82F6 !important;
                        box-shadow: none !important;
                        outline: none !important;
                    }
                    .dark .fi-ta-record {
                        background-color: #1e293b !important;
                        border-color: rgba(59, 130, 246, 0.5) !important;
                    }
                    
                    /* Category Badge - Blue */
                    .fi-ta-record .fi-badge {
                        color: #2563eb !important; 
                        background-color: rgba(59, 130, 246, 0.1) !important;
                    }

                    /* 👇 NEW: Type Badge Styling 👇 */
                    /* Green for Veg */
                    .type-badge-veg {
                        display: inline-flex;
                        align-items: center;
                        gap: 0.25rem;
                        padding: 2px 8px;
                        border-radius: 9999px;
                        font-size: 0.65rem;
                        font-weight: 700;
                        background-color: rgba(34, 197, 94, 0.1);
                        color: #16a34a;
                        border: 1px solid rgba(34, 197, 94, 0.2);
                        margin-top: 4px;
                    }
                    .type-badge-veg::before {
                        content: ""; display: inline-block; width: 6px; height: 6px; background-color: #16a34a; border-radius: 50%;
                    }

                    /* Red for Non-Veg */
                    .type-badge-non-veg {
                        display: inline-flex;
                        align-items: center;
                        gap: 0.25rem;
                        padding: 2px 8px;
                        border-radius: 9999px;
                        font-size: 0.65rem;
                        font-weight: 700;
                        background-color: rgba(239, 68, 68, 0.1);
                        color: #dc2626;
                        border: 1px solid rgba(239, 68, 68, 0.2);
                        margin-top: 4px;
                    }
                    .type-badge-non-veg::before {
                        content: ""; display: inline-block; width: 6px; height: 6px; background-color: #dc2626; border-radius: 50%;
                    }
                    
                    /* Price Text - Blue */
                    .fi-ta-record .custom-price-text,
                    .fi-ta-record .custom-price-text * {
                        color: #3B82F6 !important;
                    }
                    
                    /* Toggle Switch (Checked state) - Orange */
                    .fi-ta-record button[role="switch"][aria-checked="true"] {
                        background-color: #F47D20 !important;
                    }
                    
                    /* Edit Button */
                    .fi-ta-record .fi-ta-actions button:nth-of-type(1) {
                        background-color: #3B82F6 !important; color: #ffffff !important; border: none !important; transition: 0.2s;
                    }
                    .fi-ta-record .fi-ta-actions button:nth-of-type(1) * { color: #ffffff !important; }
                    .fi-ta-record .fi-ta-actions button:nth-of-type(1):hover { background-color: #2563eb !important; }

                    /* Delete Button */
                    .fi-ta-record .fi-ta-actions button:nth-of-type(2) {
                        color: #ef4444 !important; border-color: rgba(239, 68, 68, 0.4) !important; background-color: transparent !important;
                    }
                    .fi-ta-record .fi-ta-actions button:nth-of-type(2):hover { background-color: rgba(239, 68, 68, 0.1) !important; }

                    /* Branch Specific Badge */
                    .branch-badge {
                        position: absolute; top: 8px; right: 8px; background: #F47D20; color: white; font-size: 0.65rem; font-weight: 800; padding: 2px 8px; border-radius: 12px; z-index: 10;
                    }
                </style>
            '))
            ->contentGrid([
                'md' => 3, 
                'lg' => 4, 
                'xl' => 5, 
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\TextColumn::make('branch_id')
                        ->formatStateUsing(fn ($state) => $state ? 'Branch Item' : '')
                        ->extraAttributes(['class' => 'branch-badge'])
                        ->visible(fn ($record) => $record && $record->branch_id !== null),

                    Tables\Columns\ImageColumn::make('image_path')
                        ->height('120px') 
                        ->width('100%')
                        ->extraImgAttributes(['class' => 'object-cover w-full h-full rounded-t-xl']),

                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('name')
                            ->weight('bold')
                            ->size('md') 
                            ->searchable(), 

                        // 👇 ADDED: Type Badge Custom HTML
                        Tables\Columns\TextColumn::make('type')
                            ->formatStateUsing(fn (string $state): HtmlString => new HtmlString(
                                $state === 'veg' 
                                    ? '<span class="type-badge-veg">VEG</span>' 
                                    : '<span class="type-badge-non-veg">NON-VEG</span>'
                            )),

                        Tables\Columns\TextColumn::make('category.name')
                            ->badge()
                            ->size('xs'), 

                        Tables\Columns\TextColumn::make('price')
                            ->money('INR')
                            ->weight('bold')
                            ->size('sm') 
                            ->extraAttributes(['class' => 'custom-price-text']), 

                        Tables\Columns\ToggleColumn::make('is_available')
                            ->label('Stock Status')
                            ->getStateUsing(function ($record) {
                                if (!$record) return false;
                                
                                $user = auth()->user();
                                if ($user->branch_id !== null && $record->branch_id === null) {
                                    $status = DB::table('branch_menu_item_status')
                                        ->where('menu_item_id', $record->id)
                                        ->where('branch_id', $user->branch_id)
                                        ->first();
                                    return $status ? (bool) $status->is_available : (bool) $record->is_available;
                                }
                                return (bool) $record->is_available;
                            })
                            ->updateStateUsing(function ($record, $state) {
                                if (!$record) return;
                                
                                $user = auth()->user();
                                if ($user->branch_id !== null && $record->branch_id === null) {
                                    DB::table('branch_menu_item_status')->updateOrInsert(
                                        ['menu_item_id' => $record->id, 'branch_id' => $user->branch_id],
                                        ['is_available' => $state, 'updated_at' => now()]
                                    );
                                } else {
                                    $record->update(['is_available' => $state]);
                                }
                            }),
                    ])->space(2)->extraAttributes(['class' => 'p-3']), 
                ])->space(0),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Filter by Category')
                    ->options(function () {
                        $user = auth()->user();
                        if (!$user || !$user->restaurant_id) return [];

                        $query = Category::withoutGlobalScopes()
                            ->where('restaurant_id', $user->restaurant_id)
                            ->where(function($q) use ($user) {
                                $q->whereNull('branch_id');
                                if ($user->branch_id) {
                                    $q->orWhere('branch_id', $user->branch_id);
                                }
                            });

                        return $query->pluck('name', 'id')->toArray();
                    })
                    ->searchable(),
                
                // 👇 ADDED: Dietary Type Filter
                Tables\Filters\SelectFilter::make('type')
                    ->label('Filter by Type')
                    ->options([
                        'veg' => 'Veg',
                        'non-veg' => 'Non-Veg',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->button()
                    ->size('xs')
                    ->form([
                        Forms\Components\Select::make('category_id')
                            ->label('Category')
                            ->required()
                            ->disabled(fn($record) => $record && auth()->user()->branch_id !== null && $record->branch_id === null)
                            ->options(function () {
                                $user = auth()->user();
                                return Category::withoutGlobalScopes()
                                    ->where('restaurant_id', $user->restaurant_id)
                                    ->where('is_active', true)
                                    ->where(function($q) use ($user) {
                                        $q->whereNull('branch_id');
                                        if ($user->branch_id) $q->orWhere('branch_id', $user->branch_id);
                                    })
                                    ->pluck('name', 'id');
                            }),
                        
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(150)
                            ->disabled(fn($record) => $record && auth()->user()->branch_id !== null && $record->branch_id === null),
                        
                        Forms\Components\TextInput::make('price')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->prefix('₹')
                            ->disabled(fn($record) => $record && auth()->user()->branch_id !== null && $record->branch_id === null),
                        
                        Forms\Components\Select::make('type')
                            ->label('Dietary Type')
                            ->options([
                                'veg' => '🟢 Vegetarian',
                                'non-veg' => '🔴 Non-Vegetarian',
                            ])
                            ->default('veg')
                            ->required()
                            ->disabled(fn($record) => $record && auth()->user()->branch_id !== null && $record->branch_id === null),
                            
                        Forms\Components\Textarea::make('description')
                            ->maxLength(500)
                            ->columnSpanFull()
                            ->disabled(fn($record) => $record && auth()->user()->branch_id !== null && $record->branch_id === null),
                        
                        Forms\Components\FileUpload::make('image_path')
                            ->label('Item Image')
                            ->image()
                            ->disk('public')
                            ->disabled(fn($record) => $record && auth()->user()->branch_id !== null && $record->branch_id === null)
                            ->directory(function (callable $get, $record) {
                                $user = auth()->user();
                                $restaurantSlug = Str::slug($user->restaurant->name ?? 'restaurant');
                                
                                $categoryId = $get('category_id');
                                $categoryName = Category::find($categoryId)?->name ?? 'uncategorized';
                                $categorySlug = Str::slug($categoryName);

                                if ($record && $record->branch_id) {
                                    $branchName = Branch::find($record->branch_id)?->name ?? 'branch';
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
                    ]),

                Tables\Actions\DeleteAction::make()
                    ->button()
                    ->size('xs')
                    ->outlined()
                    ->visible(fn($record) => $record && (auth()->user()->branch_id === null ? $record->branch_id === null : $record->branch_id === auth()->user()->branch_id)),
            ]);
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageMenus::route('/'),
        ];
    }
}