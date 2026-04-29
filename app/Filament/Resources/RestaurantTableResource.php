<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RestaurantTableResource\Pages;
use App\Models\RestaurantTable;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\ImageColumn;
use App\Services\Restaurant\QrZipService;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Layout\Split;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class RestaurantTableResource extends Resource
{
    protected static ?string $model = RestaurantTable::class;
    protected static ?string $navigationIcon = 'heroicon-o-qr-code';
    protected static ?string $navigationLabel = 'Tables & QR Setup';
    protected static ?string $navigationGroup = 'Restaurant Table Setup';

    public static function canAccess(): bool
    {
        return auth()->check()
            && auth()->user()->restaurant_id !== null
            && in_array(auth()->user()->role->name, ['restaurant_admin', 'manager', 'branch_admin']);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery()
            ->where('restaurant_id', $user->restaurant_id);

        if ($user->isRestaurantAdmin()) {
            $query->whereNull('branch_id');
        } elseif ($user->isBranchAdmin() || $user->isManager()) {
            $query->where('branch_id', $user->branch_id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('table_number')
                    ->label('Table Number')
                    ->required()
                    ->maxLength(20),

                Forms\Components\TextInput::make('seating_capacity')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->required(),

                Forms\Components\Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        $bgImageUrl = asset('images/bg.png');

        return $table
            ->heading(new HtmlString('
                <style>
                    /* --- 🌟 MAKE WRAPPERS TRANSPARENT TO SHOW BG ── */
                    html, body, .fi-layout, .fi-main, .fi-page {
                        background-color: transparent !important;
                        background: transparent !important;
                    }

                    /* --- 🌟 BACKGROUND IMAGE (bg.png at 15% Opacity) ── */
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

                    /* --- 🎨 PREMIUM GLASS TABLE BOX WITH BLACK BORDER ── */
                    .fi-ta-ctn {
                        background: rgba(255, 255, 255, 0.55) !important;
                        backdrop-filter: blur(18px) saturate(150%) !important;
                        -webkit-backdrop-filter: blur(18px) saturate(150%) !important;
                        border: 1.5px solid #000000 !important;
                        border-radius: 1.25rem !important;
                        box-shadow: 0 8px 32px rgba(42, 71, 149, 0.10) !important;
                        overflow: hidden !important;
                    }
                    .dark .fi-ta-ctn { background: rgba(15, 15, 20, 0.75) !important; border-color: #000 !important; }

                    /* --- CARD STYLING ── */
                    .fi-ta-record {
                        background: rgba(255, 255, 255, 0.40) !important;
                        border: 1.5px solid #000000 !important;
                        border-radius: 12px !important;
                        transition: all 0.3s ease !important;
                        cursor: pointer !important;
                        margin: 0.5rem !important;
                    }

                    .fi-ta-record:hover {
                        transform: translateY(-4px) !important;
                        border-color: #f16b3f !important;
                        box-shadow: 0 10px 24px rgba(241, 107, 63, 0.18) !important;
                        background: rgba(255, 255, 255, 0.60) !important;
                    }

                    /* Header Toolbar Styling */
                    .fi-ta-header-toolbar {
                        background: rgba(252, 236, 221, 0.45) !important;
                        border-bottom: 1.5px solid #000000 !important;
                        padding: 1rem !important;
                    }
                    .fi-ta-header-cell-label { color: #2a4795 !important; font-weight: 900 !important; text-transform: uppercase !important; }
                    
                    /* Action Buttons Styling */
                    .fi-ta-record .fi-ta-actions button:nth-of-type(1) {
                        background-color: #2a4795 !important; color: #ffffff !important; border: 1.5px solid #000 !important; border-radius: 8px !important;
                    }
                    .fi-ta-record .fi-ta-actions button:nth-of-type(1):hover { background-color: #456aba !important; }
                    .fi-ta-record .fi-ta-actions button:nth-of-type(2) {
                        color: #ef4444 !important; background-color: rgba(239, 68, 68, 0.05) !important; border: 1.5px solid #ef4444 !important; border-radius: 8px !important;
                    }
                    .fi-ta-record .fi-ta-actions button:nth-of-type(2):hover { background-color: rgba(239, 68, 68, 0.1) !important; }
                </style>
                <span style="font-size: 1.5rem; font-weight: 900; color: #2a4795; font-family: Poppins, sans-serif; letter-spacing: 0.02em;">Tables & QR Setup Dashboard</span>
            '))
            ->contentGrid([
                'md' => 2,
                'xl' => 4,
                '2xl' => 5,
            ])
            ->columns([
                Stack::make([
                    Split::make([
                        Stack::make([
                            Tables\Columns\TextColumn::make('table_number')
                                ->label('Table Details')
                                ->formatStateUsing(function ($state, $record) {
                                    return new HtmlString("
                                        <div style='display: flex; flex-direction: column;'>
                                            <span style='font-size: 1.4rem; font-weight: 900; color: #2a4795;'>{$state}</span>
                                            <span style='font-size: 0.7rem; font-weight: 800; color: #f16b3f; background: rgba(241, 107, 63, 0.1); padding: 3px 8px; border-radius: 99px; width: fit-content; border: 1px solid rgba(241, 107, 63, 0.3); margin-top: 4px;'>
                                                👥 Capacity: {$record->seating_capacity}
                                            </span>
                                        </div>
                                    ");
                                }),
                        ]),
                        Tables\Columns\IconColumn::make('is_active')
                            ->boolean()
                            ->grow(false),
                    ]),

                    ImageColumn::make('qr_path')
                        ->label('QR')
                        ->disk('public')
                        ->height(120)
                        ->width('100%')
                        ->extraImgAttributes([
                            'style' => 'object-fit: contain; margin-top: 1rem; margin-bottom: 0.5rem; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1)); border: 1.5px solid #000; border-radius: 8px; background: white;',
                        ])
                        ->visibility('public'),
                ])->space(3),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->button()
                    ->visible(fn() => in_array(auth()->user()->role->name, ['restaurant_admin', 'branch_admin', 'manager'])),

                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn() => in_array(auth()->user()->role->name, ['restaurant_admin', 'branch_admin', 'manager'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => in_array(auth()->user()->role->name, ['restaurant_admin', 'branch_admin', 'manager'])),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('generateTables')
                    ->label('Generate Tables')
                    ->icon('heroicon-o-qr-code')
                    ->color('primary')
                    ->form(function () {
                        return [
                            \Filament\Forms\Components\TextInput::make('total_tables')->numeric()->minValue(1)->required(),
                            \Filament\Forms\Components\TextInput::make('seating_capacity')->label('Seating Capacity Per Table')->numeric()->default(1),
                        ];
                    })
                    ->action(function (array $data) {
                        $user = auth()->user();
                        $restaurant = $user->restaurant;
                        $branchId = ($user->isBranchAdmin() || $user->isManager()) ? $user->branch_id : null;

                        $startQuery = \App\Models\RestaurantTable::where('restaurant_id', $restaurant->id);
                        if ($branchId) {
                            $startQuery->where('branch_id', $branchId);
                        } else {
                            $startQuery->whereNull('branch_id');
                        }

                        $currentCount = $startQuery->count();
                        $qrService = app(\App\Services\Restaurant\QrCodeService::class);

                        for ($i = 1; $i <= $data['total_tables']; $i++) {
                            $table = \App\Models\RestaurantTable::create([
                                'restaurant_id' => $restaurant->id,
                                'branch_id' => $branchId,
                                'table_number' => 'T-0' . ($currentCount + $i),
                                'seating_capacity' => $data['seating_capacity'],
                            ]);
                            $qrService->generate($table);
                        }
                    }),

                Tables\Actions\Action::make('download_pdf_qr')
                    ->label('Download QRs as PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('info')
                    ->outlined()
                    ->modalWidth('5xl')
                    ->modalHeading('Design & Download PDF QRs')
                    // 👇 This makes the Header and Footer fixed, and the Body scrollable 👇
                    ->stickyModalHeader()
                    ->stickyModalFooter()
                    ->modalSubmitActionLabel('Generate & Download PDF')
                    ->form(function () {
                        return [
                            \Filament\Forms\Components\Grid::make(12)
                                ->schema([
                                    // LEFT SIDE: CONTROLS (Scrollable normally)
                                    \Filament\Forms\Components\Group::make()
                                        ->columnSpan(['default' => 12, 'lg' => 7])
                                        ->schema([
                                            \Filament\Forms\Components\Section::make('Background Setup')
                                                ->schema([
                                                    \Filament\Forms\Components\Radio::make('bg_type')
                                                        ->label('Background Type')
                                                        ->options(['image' => 'Background Image', 'color' => 'Solid Color'])
                                                        ->default('image')
                                                        ->inline()
                                                        ->live(),

                                                    \Filament\Forms\Components\FileUpload::make('bg_image')
                                                        ->label('Upload Background')
                                                        ->helperText('Leave empty to use the default app background')
                                                        ->image()
                                                        ->directory('qr_backgrounds')
                                                        ->live()
                                                        ->visible(fn(\Filament\Forms\Get $get) => $get('bg_type') === 'image'),

                                                    \Filament\Forms\Components\ColorPicker::make('bg_color')
                                                        ->label('Solid Color')
                                                        ->default('#E2F0CB')
                                                        ->live()
                                                        ->visible(fn(\Filament\Forms\Get $get) => $get('bg_type') === 'color'),
                                                ])->columns(1),

                                            \Filament\Forms\Components\Section::make('Color Customization')
                                                ->schema([
                                                    \Filament\Forms\Components\ColorPicker::make('name_color')->label('Restaurant Name')->default('#9A3B2A')->live(),
                                                    \Filament\Forms\Components\ColorPicker::make('address_color')->label('Address Text')->default('#333333')->live(),
                                                    \Filament\Forms\Components\ColorPicker::make('table_color')->label('Table Number')->default('#32402A')->live(),
                                                    \Filament\Forms\Components\ColorPicker::make('subtitle_color')->label('Subtitles & Labels')->default('#4B5320')->live(),
                                                    \Filament\Forms\Components\ColorPicker::make('accent_color')->label('Divider Lines & Borders')->default('#E47A33')->live(),
                                                    \Filament\Forms\Components\ColorPicker::make('pill_bg_color')->label('Scan Pill Background')->default('#B85C4A')->live(),
                                                ])->columns(2),
                                        ]),

                                    // RIGHT SIDE: LIVE PREVIEW (Sticky so it stays visible when scrolling controls)
                                    \Filament\Forms\Components\Group::make()
                                        ->columnSpan(['default' => 12, 'lg' => 5])
                                        ->extraAttributes(['class' => 'lg:sticky lg:top-4']) // 👇 Bonus: Keeps preview visible while scrolling settings!
                                        ->schema([
                                            \Filament\Forms\Components\Placeholder::make('pdf_preview')
                                                ->label('Live Design Preview')
                                                ->content(function (\Filament\Forms\Get $get) {
                                                    $restaurant = auth()->user()->restaurant;
                                                    
                                                    // Get Values
                                                    $bgType = $get('bg_type') ?? 'image';
                                                    $bgImage = $get('bg_image');
                                                    $bgColor = $get('bg_color') ?? '#E2F0CB';

                                                    $nameColor = $get('name_color') ?? '#9A3B2A';
                                                    $addressColor = $get('address_color') ?? '#333333';
                                                    $tableColor = $get('table_color') ?? '#32402A';
                                                    $subtitleColor = $get('subtitle_color') ?? '#4B5320';
                                                    $accentColor = $get('accent_color') ?? '#E47A33';
                                                    $pillBgColor = $get('pill_bg_color') ?? '#B85C4A';

                                                    // Background Style
                                                    $bgStyle = '';
                                                    if ($bgType === 'image') {
                                                        $url = asset('images/b.png'); 

                                                        if (!empty($bgImage)) {
                                                            $file = is_array($bgImage) ? reset($bgImage) : $bgImage;
                                                            
                                                            if ($file instanceof TemporaryUploadedFile) {
                                                                try {
                                                                    $url = $file->temporaryUrl();
                                                                } catch (\Exception $e) {
                                                                    $url = 'data:' . $file->getClientMimeType() . ';base64,' . base64_encode(file_get_contents($file->getRealPath()));
                                                                }
                                                            } elseif (is_string($file)) {
                                                                $url = Storage::disk('public')->url($file);
                                                            }
                                                        }
                                                        $bgStyle = "background-image: url('{$url}'); background-size: cover; background-position: center;";
                                                    } else {
                                                        $bgStyle = "background-color: {$bgColor};";
                                                    }

                                                    // Fetch Restaurant Details
                                                    $restName = strtoupper($restaurant->name ?? 'RESTAURANT');
                                                    $address = $restaurant->address ?? '123 Main Street, City, State';
                                                    $logoUrl = ($restaurant && $restaurant->logo_path) ? Storage::disk('public')->url($restaurant->logo_path) : null;

                                                    $logoHtml = $logoUrl ? "<img src='{$logoUrl}' style='max-width: 50px; max-height: 50px; object-fit: contain; margin-bottom: 5px;' />" : "";
                                                    $addressHtml = $address ? "<div style='font-size: 9px; color: {$addressColor}; margin: 4px 10px; line-height: 1.2;'>" . nl2br(htmlspecialchars($address)) . "</div>" : "";

                                                    return new HtmlString("
                                                        <div style='width: 100%; max-width: 320px; height: 420px; border: 1px dashed #ccc; border-radius: 8px; padding: 20px; text-align: center; margin: 0 auto; {$bgStyle} box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);'>
                                                            {$logoHtml}
                                                            <div style='font-family: Times, serif; font-size: 18px; font-weight: bold; color: {$nameColor}; text-transform: uppercase; letter-spacing: 1px;'>{$restName}</div>
                                                            {$addressHtml}
                                                            
                                                            <div style='border-top: 3px solid {$accentColor}; width: 35px; margin: 8px auto;'></div>
                                                            <div style='font-size: 9px; color: {$subtitleColor}; font-weight: bold; letter-spacing: 1px;'>EXQUISITE DINING EXPERIENCE</div>
                                                            
                                                            <div style='display: flex; justify-content: center; align-items: center; margin-top: 15px;'>
                                                                <div style='border-top: 3px solid {$accentColor}; border-left: 3px solid {$accentColor}; width: 20px; height: 20px; position: absolute; transform: translate(-55px, -55px);'></div>
                                                                <div style='border-bottom: 3px solid {$accentColor}; border-right: 3px solid {$accentColor}; width: 20px; height: 20px; position: absolute; transform: translate(55px, 55px);'></div>
                                                                
                                                                <div style='background: white; padding: 6px; border-radius: 8px; border: 2px solid #8B5CF6; z-index: 10;'>
                                                                    <img src='https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=LivePreview' style='width: 100px; height: 100px; display: block;' />
                                                                </div>
                                                            </div>
                                                            
                                                            <div style='margin-top: 15px;'>
                                                                <span style='background-color: {$pillBgColor}; color: white; padding: 6px 20px; border-radius: 15px; font-size: 10px; font-weight: bold; letter-spacing: 1px;'>SCAN TO MENU</span>
                                                            </div>
                                                            
                                                            <div style='margin-top: 12px; font-size: 9px; color: {$subtitleColor}; font-weight: bold; letter-spacing: 0.5px;'>YOUR LOCATION</div>
                                                            <div style='font-family: Times, serif; font-size: 26px; font-style: italic; font-weight: bold; color: {$tableColor}; margin-top: 2px;'>Table T-01</div>
                                                        </div>
                                                    ");
                                                }),
                                        ]),
                                ]),
                        ];
                    })
                    ->action(function (array $data) {
                        ini_set('memory_limit', '1024M');
                        set_time_limit(300);

                        $user = auth()->user();
                        $restaurant = $user->restaurant;
                        $branchId = ($user->isBranchAdmin() || $user->isManager()) ? $user->branch_id : null;

                        $query = \App\Models\RestaurantTable::where('restaurant_id', $restaurant->id);
                        if ($branchId) {
                            $query->where('branch_id', $branchId);
                        } else {
                            $query->whereNull('branch_id');
                        }
                        $tables = $query->get();

                        // Parse Data from Modal
                        $bgType = $data['bg_type'] ?? 'image';
                        $bgColor = $data['bg_color'] ?? '#E2F0CB';
                        $bgImage = $data['bg_image'] ?? null;
                        
                        $nameColor = $data['name_color'] ?? '#9A3B2A';
                        $addressColor = $data['address_color'] ?? '#333333';
                        $tableColor = $data['table_color'] ?? '#32402A';
                        $subtitleColor = $data['subtitle_color'] ?? '#4B5320';
                        $accentColor = $data['accent_color'] ?? '#E47A33';
                        $pillBgColor = $data['pill_bg_color'] ?? '#B85C4A';

                        // Process Background Image into Base64 for the PDF render
                        $cardBackgroundStyle = '';
                        if ($bgType === 'image') {
                            if (!empty($bgImage)) {
                                $path = is_array($bgImage) ? reset($bgImage) : $bgImage;
                                $bgImagePath = Storage::disk('public')->path($path);
                            } else {
                                $bgImagePath = public_path('images/b.png');
                            }

                            if (file_exists($bgImagePath)) {
                                if (extension_loaded('gd') && mime_content_type($bgImagePath) === 'image/png') {
                                    $img = @imagecreatefrompng($bgImagePath);
                                    if ($img) {
                                        $width = imagesx($img);
                                        $height = imagesy($img);
                                        $newWidth = 400;
                                        $newHeight = 500;
                                        $resizedImg = imagecreatetruecolor($newWidth, $newHeight);
                                        $white = imagecolorallocate($resizedImg, 255, 255, 255);
                                        imagefill($resizedImg, 0, 0, $white);
                                        imagecopyresampled($resizedImg, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                                        ob_start();
                                        imagejpeg($resizedImg, null, 40);
                                        $compressedData = ob_get_clean();
                                        imagedestroy($img);
                                        imagedestroy($resizedImg);
                                        $bgBase64 = 'data:image/jpeg;base64,' . base64_encode($compressedData);
                                    }
                                } else {
                                    $bgData = file_get_contents($bgImagePath);
                                    $mime = mime_content_type($bgImagePath);
                                    $bgBase64 = 'data:' . $mime . ';base64,' . base64_encode($bgData);
                                }
                                $cardBackgroundStyle = 'background-image: url("' . $bgBase64 . '"); background-size: cover; background-position: center; background-repeat: no-repeat;';
                            }
                        } else {
                            $cardBackgroundStyle = 'background-color: ' . $bgColor . ';';
                        }

                        // Process Logo
                        $restaurantName = strtoupper($restaurant->name ?? 'RESTAURANT');
                        $address = $restaurant->address ?? '';
                        $logoBase64 = '';

                        if ($restaurant && $restaurant->logo_path) {
                            $logoFullPath = Storage::disk('public')->path($restaurant->logo_path);
                            if (file_exists($logoFullPath)) {
                                $mime = mime_content_type($logoFullPath);
                                $logoData = file_get_contents($logoFullPath);
                                $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode($logoData);
                            }
                        }

                        $logoHtml = $logoBase64 ? '<img src="' . $logoBase64 . '" style="max-width: 55px; max-height: 55px; object-fit: contain; margin-bottom: 5px;" />' : '';
                        $addressHtml = $address ? '<div style="font-size: 10px; color: ' . $addressColor . '; margin: 4px 15px; line-height: 1.2;">' . nl2br(htmlspecialchars($address)) . '</div>' : '';

                        // CSS and Layout
                        $html = '<!DOCTYPE html><html><head><style>
                            @page { margin: 15px; size: A4 portrait; }
                            body { margin: 0; padding: 0; background-color: #ffffff; font-family: "Helvetica", "Arial", sans-serif; }
                            .page-table { width: 100%; border-collapse: separate; border-spacing: 15px; table-layout: fixed; page-break-after: always; }
                            .page-table:last-child { page-break-after: auto; }
                            .quadrant { width: 50%; height: 460px; padding: 0; vertical-align: top; }
                            .card { border: 1px dashed #cbd5e1; border-radius: 8px; height: 460px; box-sizing: border-box; text-align: center; ' . $cardBackgroundStyle . ' }
                            .content-wrapper { background-color: transparent; width: 100%; height: 100%; padding-top: 15px; box-sizing: border-box; }
                            .title { font-family: "Times", serif; font-size: 24px; font-weight: bold; color: ' . $nameColor . '; margin: 0; text-transform: uppercase; letter-spacing: 1px; }
                            .orange-line { border-top: 3px solid ' . $accentColor . '; width: 40px; margin: 6px auto; } 
                            .subtitle { font-size: 9px; color: ' . $subtitleColor . '; font-weight: bold; letter-spacing: 1px; margin-bottom: 8px; }
                            .qr-bracket-table { margin: 0 auto 10px auto; border-collapse: collapse; }
                            .qr-bracket-table td { padding: 0; }
                            .br-tl { border-top: 3px solid ' . $accentColor . '; border-left: 3px solid ' . $accentColor . '; width: 25px; height: 25px; }
                            .br-br { border-bottom: 3px solid ' . $accentColor . '; border-right: 3px solid ' . $accentColor . '; width: 25px; height: 25px; }
                            .qr-img { width: 125px; height: 125px; border: 2px solid #8B5CF6; border-radius: 8px; padding: 4px; background-color: #ffffff; display: block; margin: 6px; }
                            .btn-wrapper { margin-bottom: 10px; } 
                            .scan-pill { background-color: ' . $pillBgColor . '; color: #ffffff; padding: 6px 25px; border-radius: 15px; font-size: 10px; font-weight: bold; display: inline-block; letter-spacing: 1px;}
                            .loc-label { font-size: 9px; color: ' . $subtitleColor . '; font-weight: bold; margin-bottom: 2px; letter-spacing: 0.5px; } 
                            .table-number { font-family: "Times", serif; font-size: 32px; font-style: italic; font-weight: bold; color: ' . $tableColor . '; margin: 0; }
                        </style></head><body>';

                        $pages = $tables->chunk(4);

                        foreach ($pages as $pageItems) {
                            $html .= '<table class="page-table">';
                            $rows = $pageItems->chunk(2);

                            foreach ($rows as $rowItems) {
                                $html .= '<tr>';
                                foreach ($rowItems as $table) {
                                    $imagePath = storage_path('app/public/' . $table->qr_path);
                                    $qrBase64 = '';

                                    if ($table->qr_path && file_exists($imagePath)) {
                                        $svgData = file_get_contents($imagePath);
                                        $qrBase64 = 'data:image/svg+xml;base64,' . base64_encode($svgData);
                                    }

                                    $html .= '<td class="quadrant"><div class="card"><div class="content-wrapper">
                                        ' . $logoHtml . '
                                        <div class="title">' . $restaurantName . '</div>
                                        ' . $addressHtml . '
                                        <div class="orange-line"></div><div class="subtitle">EXQUISITE DINING EXPERIENCE</div>
                                        <table class="qr-bracket-table"><tr><td class="br-tl"></td><td></td><td></td></tr><tr><td></td><td><img src="' . $qrBase64 . '" class="qr-img" /></td><td></td></tr><tr><td></td><td></td><td class="br-br"></td></tr></table>
                                        <div class="btn-wrapper"><div class="scan-pill">SCAN TO MENU</div></div>
                                        <div class="loc-label">YOUR LOCATION</div><div class="table-number">Table ' . $table->table_number . '</div>
                                        </div></div></td>';
                                }

                                if ($rowItems->count() == 1) {
                                    $html .= '<td class="quadrant"></td>';
                                }
                                $html .= '</tr>';
                            }

                            if ($rows->count() == 1) {
                                $html .= '<tr><td class="quadrant"></td><td class="quadrant"></td></tr>';
                            }
                            $html .= '</table>';
                        }

                        $html .= '</body></html>';

                        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
                                ->setPaper('a4', 'portrait')
                                ->setWarnings(false);

                            return response()->streamDownload(function () use ($pdf) {
                                echo $pdf->output();
                            }, 'Restaurant_QRs.pdf');
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('PDF Library Missing')
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('delete_all_qr')
                    ->label('Delete All QRs')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->outlined()
                    ->requiresConfirmation()
                    ->modalHeading('Delete All Tables & QRs')
                    ->modalDescription('Are you sure you want to delete all tables and their QR codes? This action cannot be undone.')
                    ->modalSubmitActionLabel('Yes, delete them all')
                    ->action(function () {
                        $user = auth()->user();
                        $restaurant = $user->restaurant;
                        $branchId = ($user->isBranchAdmin() || $user->isManager()) ? $user->branch_id : null;

                        $query = \App\Models\RestaurantTable::where('restaurant_id', $restaurant->id);
                        if ($branchId) {
                            $query->where('branch_id', $branchId);
                        } else {
                            $query->whereNull('branch_id');
                        }

                        $tables = $query->get();
                        foreach ($tables as $table) {
                            if ($table->qr_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($table->qr_path)) {
                                \Illuminate\Support\Facades\Storage::disk('public')->delete($table->qr_path);
                            }
                        }

                        $query->delete();

                        \Filament\Notifications\Notification::make()
                            ->title('All tables and QR codes deleted successfully.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn() => in_array(auth()->user()->role->name, ['restaurant_admin', 'branch_admin'])),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRestaurantTables::route('/'),
            'edit' => Pages\EditRestaurantTable::route('/{record}/edit'),
        ];
    }
}