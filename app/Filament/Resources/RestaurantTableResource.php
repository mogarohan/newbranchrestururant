<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RestaurantTableResource\Pages;
use App\Filament\Resources\RestaurantTableResource\RelationManagers;
use App\Models\RestaurantTable;
use App\Models\Branch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use App\Services\Restaurant\QrCodeService;
use Filament\Tables\Columns\ImageColumn;
use App\Services\Restaurant\QrZipService;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Layout\Split;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

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
                Forms\Components\Section::make('Table Details')
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
                    ])->columns(2),

                // 🔥 QR PDF Customizer (Single Table)
                Forms\Components\Section::make('QR PDF Customizer (Single Table)')
                    ->description('Set specific background settings just for this table when downloading its individual QR.')
                    ->schema([
                        Forms\Components\Radio::make('single_bg_type')
                            ->label('Background Type')
                            ->options([
                                'color' => 'Solid Color',
                                'image' => 'Custom Image',
                            ])
                            ->default('color')
                            ->live()
                            ->dehydrated(false),

                        Forms\Components\ColorPicker::make('single_bg_color')
                            ->label('Select Background Color')
                            ->default('#E2F0CB') // Pista Color by default
                            ->live()
                            ->visible(fn(Forms\Get $get) => $get('single_bg_type') === 'color')
                            ->dehydrated(false),

                        Forms\Components\FileUpload::make('single_bg_image')
                            ->label('Upload Background Image')
                            ->image()
                            ->directory('temp_qr_backgrounds')
                            ->visibility('public')
                            ->live()
                            ->visible(fn(Forms\Get $get) => $get('single_bg_type') === 'image')
                            ->dehydrated(false),

                        // 🔥 LIVE PREVIEW 
                        Forms\Components\Placeholder::make('qr_preview')
                            ->label('Live Preview')
                            ->content(function (Forms\Get $get, ?RestaurantTable $record) {
                                if (!$record || !$record->qr_path) {
                                    return new HtmlString('<p style="color:red;">QR not generated yet. Please save first.</p>');
                                }

                                $bgType = $get('single_bg_type') ?? 'color';
                                $bgColor = $get('single_bg_color') ?? '#E2F0CB';
                                $bgImage = $get('single_bg_image') ?? null;

                                $bgStyle = "background-color: {$bgColor};";

                                if ($bgType === 'image' && !empty($bgImage)) {
                                    $bgImagePath = Storage::disk('public')->url(is_array($bgImage) ? reset($bgImage) : $bgImage);
                                    $bgStyle = "background-image: url('{$bgImagePath}'); background-size: cover; background-position: center;";
                                }

                                $qrUrl = Storage::disk('public')->url($record->qr_path);
                                $restName = strtoupper($record->restaurant->name ?? 'RESTAURANT');

                                return new HtmlString("
                                    <div style='width: 300px; height: 350px; border: 1px dashed #ccc; border-radius: 8px; padding: 20px; text-align: center; {$bgStyle}'>
                                        <h3 style='font-family: Times, serif; color: #9A3B2A; margin: 0;'>{$restName}</h3>
                                        <hr style='border-top: 2px solid #E47A33; width: 40px; margin: 5px auto;'>
                                        <p style='font-size: 8px; font-weight: bold; color: #4B5320;'>EXQUISITE DINING</p>
                                        <div style='background: white; padding: 10px; display: inline-block; border-radius: 8px; border: 2px solid #8B5CF6;'>
                                            <img src='{$qrUrl}' style='width: 100px; height: 100px;' />
                                        </div>
                                        <h2 style='font-family: Times, serif; color: #32402A; margin-top: 15px;'>Table {$record->table_number}</h2>
                                    </div>
                                ");
                            }),

                        // 🔥 Download Single PDF Action
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('download_single_pdf')
                                ->label('Download This QR (PDF)')
                                ->icon('heroicon-o-document-arrow-down')
                                ->color('success')
                                ->action(function (Forms\Get $get, ?RestaurantTable $record) {
                                    if (!$record)
                                        return;

                                    $bgType = $get('single_bg_type') ?? 'color';
                                    $bgColor = $get('single_bg_color') ?? '#E2F0CB';
                                    $bgImage = $get('single_bg_image') ?? null;

                                    $cardBackgroundStyle = "background-color: {$bgColor};";

                                    if ($bgType === 'image' && !empty($bgImage)) {
                                        $path = is_array($bgImage) ? reset($bgImage) : $bgImage;
                                        $fullPath = Storage::disk('public')->path($path);
                                        if (file_exists($fullPath)) {
                                            $mime = mime_content_type($fullPath);
                                            $data = file_get_contents($fullPath);
                                            $base64 = 'data:' . $mime . ';base64,' . base64_encode($data);
                                            $cardBackgroundStyle = 'background-image: url("' . $base64 . '"); background-size: cover; background-position: center;';
                                        }
                                    }

                                    $qrPath = storage_path('app/public/' . $record->qr_path);
                                    $qrBase64 = '';
                                    if (file_exists($qrPath)) {
                                        $qrBase64 = 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($qrPath));
                                    }

                                    $restName = strtoupper($record->restaurant->name ?? 'RESTAURANT');

                                    $html = "<!DOCTYPE html><html><head><style>
                                        @page { margin: 15px; size: A4 portrait; }
                                        body { margin: 0; padding: 0; font-family: 'Helvetica', sans-serif; text-align: center; }
                                        .card { width: 50%; height: 460px; margin: 0 auto; border: 1px dashed #ccc; border-radius: 8px; {$cardBackgroundStyle} }
                                        .content { padding-top: 25px; }
                                        h1 { font-family: 'Times', serif; color: #9A3B2A; margin: 0; }
                                        .line { border-top: 3px solid #E47A33; width: 40px; margin: 10px auto; }
                                        img.qr { width: 150px; border: 2px solid #8B5CF6; border-radius: 8px; padding: 5px; background: white; margin-top: 20px;}
                                        h2 { font-family: 'Times', serif; color: #32402A; font-size: 30px;}
                                    </style></head><body>
                                        <div class='card'>
                                            <div class='content'>
                                                <h1>{$restName}</h1>
                                                <div class='line'></div>
                                                <p style='font-size: 10px; color: #4B5320; font-weight: bold;'>EXQUISITE DINING EXPERIENCE</p>
                                                <img src='{$qrBase64}' class='qr' />
                                                <br>
                                                <span style='background-color: #B85C4A; color: white; padding: 5px 15px; border-radius: 15px; font-size: 12px; margin-top: 15px; display: inline-block;'>SCAN TO MENU</span>
                                                <p style='font-size: 10px; color: #7F8A74; font-weight: bold; margin-bottom: 0;'>YOUR LOCATION</p>
                                                <h2>Table {$record->table_number}</h2>
                                            </div>
                                        </div>
                                    </body></html>";

                                    if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                                        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4', 'portrait');
                                        return response()->streamDownload(function () use ($pdf) {
                                            echo $pdf->output();
                                        }, "Table_{$record->table_number}_QR.pdf");
                                    }
                                })
                        ])
                    ])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading(new HtmlString('
                <style>
                    html, body, .fi-layout, .fi-main, .fi-page { background-color: transparent !important; background: transparent !important; }
                    body::before { content: ""; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(135deg, #2a4795 0%, #f16b3f 100%) !important; opacity: 0.15 !important; z-index: -999 !important; pointer-events: none; }
                    .fi-ta-ctn { background: rgba(255, 255, 255, 0.55) !important; backdrop-filter: blur(18px) saturate(150%) !important; -webkit-backdrop-filter: blur(18px) saturate(150%) !important; border: 1.5px solid #000000 !important; border-radius: 1.25rem !important; box-shadow: 0 8px 32px rgba(42, 71, 149, 0.10) !important; overflow: hidden !important; }
                    .fi-ta-record { background: rgba(255, 255, 255, 0.40) !important; border: 1.5px solid #000000 !important; border-radius: 12px !important; transition: all 0.3s ease !important; margin: 0.5rem !important; }
                    .fi-ta-record:hover { transform: translateY(-4px) !important; border-color: #f16b3f !important; box-shadow: 0 10px 24px rgba(241, 107, 63, 0.18) !important; }
                </style>
                <span style="font-size: 1.5rem; font-weight: 900; color: #2a4795; font-family: Poppins, sans-serif;">Tables & QR Setup Dashboard</span>
            '))
            ->contentGrid(['md' => 2, 'xl' => 4, '2xl' => 5])
            ->columns([
                Stack::make([
                    Split::make([
                        Stack::make([
                            Tables\Columns\TextColumn::make('table_number')
                                ->formatStateUsing(fn($state, $record) => new HtmlString("<div style='display: flex; flex-direction: column;'><span style='font-size: 1.4rem; font-weight: 900; color: #2a4795;'>{$state}</span><span style='font-size: 0.7rem; font-weight: 800; color: #f16b3f;'>👥 Capacity: {$record->seating_capacity}</span></div>")),
                        ]),
                        Tables\Columns\IconColumn::make('is_active')->boolean()->grow(false),
                    ]),
                    ImageColumn::make('qr_path')->disk('public')->height(120)->width('100%')->extraImgAttributes(['style' => 'object-fit: contain; border: 1.5px solid #000; border-radius: 8px; background: white;']),
                ])->space(3),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->button(),
                Tables\Actions\DeleteAction::make()->iconButton()->color('danger'),
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

                            // 🔥 MODIFIED: Global Settings with LIVE PREVIEW inside Generate Modal
                            \Filament\Forms\Components\Section::make('Global PDF Background Settings')
                                ->schema([
                                    \Filament\Forms\Components\Radio::make('bg_type')
                                        ->label('Background Type')
                                        ->options(['color' => 'Solid Color', 'image' => 'Custom Image'])
                                        ->default('color')
                                        ->live(),

                                    \Filament\Forms\Components\ColorPicker::make('bg_color')
                                        ->label('Select Background Color')
                                        ->default('#E2F0CB')
                                        ->live()
                                        ->visible(fn(\Filament\Forms\Get $get) => $get('bg_type') === 'color'),

                                    \Filament\Forms\Components\FileUpload::make('bg_image')
                                        ->label('Upload Background Image')
                                        ->image()
                                        ->directory('qr_backgrounds')
                                        ->live()
                                        ->visible(fn(\Filament\Forms\Get $get) => $get('bg_type') === 'image'),

                                    // 🔥 NAYA: Live Preview Placeholder
                                    \Filament\Forms\Components\Placeholder::make('global_qr_preview')
                                        ->label('Global Design Preview')
                                        ->content(function (\Filament\Forms\Get $get) {
                                            $bgType = $get('bg_type') ?? 'color';
                                            $bgColor = $get('bg_color') ?? '#E2F0CB';
                                            $bgImage = $get('bg_image') ?? null;

                                            $bgStyle = "background-color: {$bgColor};";
                                            if ($bgType === 'image' && !empty($bgImage)) {
                                                // Handle potential temporary file array
                                                $path = is_array($bgImage) ? reset($bgImage) : $bgImage;
                                                $url = Storage::disk('public')->url($path);
                                                $bgStyle = "background-image: url('{$url}'); background-size: cover; background-position: center;";
                                            }

                                            return new HtmlString("
                                                <div style='width: 100%; max-width: 250px; height: 300px; border: 1px dashed #ccc; border-radius: 8px; padding: 15px; text-align: center; margin: 10px auto; {$bgStyle}'>
                                                    <div style='font-size: 14px; font-weight: bold; color: #9A3B2A;'>RESTAURANT NAME</div>
                                                    <div style='border-top: 2px solid #E47A33; width: 30px; margin: 4px auto;'></div>
                                                    <div style='background: white; padding: 8px; display: inline-block; border-radius: 5px; margin-top: 15px; border: 1.5px solid #8B5CF6;'>
                                                        <img src='https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=Preview' style='width: 80px; height: 80px;' />
                                                    </div>
                                                    <div style='margin-top: 10px; font-weight: bold; color: #32402A;'>Table T-01</div>
                                                </div>
                                            ");
                                        }),
                                ])->collapsible(),
                        ];
                    })
                    ->action(function (array $data) {
                        $user = auth()->user();
                        $restaurant = $user->restaurant;
                        $branchId = ($user->isBranchAdmin() || $user->isManager()) ? $user->branch_id : null;

                        Session::put("qr_pdf_prefs_{$restaurant->id}", [
                            'bg_type' => $data['bg_type'] ?? 'color',
                            'bg_color' => $data['bg_color'] ?? '#E2F0CB',
                            'bg_image' => $data['bg_image'] ?? null,
                        ]);

                        $startQuery = \App\Models\RestaurantTable::where('restaurant_id', $restaurant->id);
                        if ($branchId)
                            $startQuery->where('branch_id', $branchId);
                        else
                            $startQuery->whereNull('branch_id');

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
                    ->label('Download ALL QRs as PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('info')
                    ->outlined()
                    ->action(function () {
                        ini_set('memory_limit', '1024M');
                        set_time_limit(300);

                        $user = auth()->user();
                        $restaurant = $user->restaurant;
                        $branchId = ($user->isBranchAdmin() || $user->isManager()) ? $user->branch_id : null;

                        $query = \App\Models\RestaurantTable::where('restaurant_id', $restaurant->id);
                        if ($branchId)
                            $query->where('branch_id', $branchId);
                        else
                            $query->whereNull('branch_id');
                        $tables = $query->get();

                        $prefs = Session::get("qr_pdf_prefs_{$restaurant->id}", ['bg_type' => 'color', 'bg_color' => '#E2F0CB', 'bg_image' => null]);
                        $cardBackgroundStyle = '';

                        if ($prefs['bg_type'] === 'image' && !empty($prefs['bg_image'])) {
                            $bgImagePath = Storage::disk('public')->path($prefs['bg_image']);
                            if (file_exists($bgImagePath)) {
                                $mime = mime_content_type($bgImagePath);
                                $bgBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($bgImagePath));
                                $cardBackgroundStyle = 'background-image: url("' . $bgBase64 . '"); background-size: cover; background-position: center; background-repeat: no-repeat;';
                            }
                        }

                        if (empty($cardBackgroundStyle)) {
                            $cardBackgroundStyle = 'background-color: ' . $prefs['bg_color'] . ';';
                        }

                        $html = '<!DOCTYPE html><html><head><style>
                            @page { margin: 15px; size: A4 portrait; }
                            body { margin: 0; padding: 0; background-color: #ffffff; font-family: "Helvetica", "Arial", sans-serif; }
                            .page-table { width: 100%; border-collapse: separate; border-spacing: 15px; table-layout: fixed; page-break-after: always; }
                            .page-table:last-child { page-break-after: auto; }
                            .quadrant { width: 50%; height: 460px; padding: 0; vertical-align: top; }
                            .card { border: 1px dashed #cbd5e1; border-radius: 8px; height: 460px; box-sizing: border-box; text-align: center; ' . $cardBackgroundStyle . ' }
                            .content-wrapper { background-color: transparent; width: 100%; height: 100%; padding-top: 25px; box-sizing: border-box; }
                            .title { font-family: "Times", serif; font-size: 24px; font-weight: bold; color: #9A3B2A; margin: 0; text-transform: uppercase; letter-spacing: 1px; }
                            .orange-line { border-top: 3px solid #E47A33; width: 40px; margin: 6px auto; } 
                            .subtitle { font-size: 9px; color: #4B5320; font-weight: bold; letter-spacing: 1px; margin-bottom: 12px; }
                            .qr-bracket-table { margin: 0 auto 12px auto; border-collapse: collapse; }
                            .qr-bracket-table td { padding: 0; }
                            .br-tl { border-top: 3px solid #E47A33; border-left: 3px solid #E47A33; width: 25px; height: 25px; }
                            .br-br { border-bottom: 3px solid #E47A33; border-right: 3px solid #E47A33; width: 25px; height: 25px; }
                            .qr-img { width: 135px; height: 135px; border: 2px solid #8B5CF6; border-radius: 8px; padding: 4px; background-color: #ffffff; display: block; margin: 8px; }
                            .btn-wrapper { margin-bottom: 12px; } 
                            .scan-pill { background-color: #B85C4A; color: #ffffff; padding: 6px 25px; border-radius: 15px; font-size: 10px; font-weight: bold; display: inline-block; letter-spacing: 1px;}
                            .loc-label { font-size: 9px; color: #7F8A74; font-weight: bold; margin-bottom: 2px; letter-spacing: 0.5px; } 
                            .table-number { font-family: "Times", serif; font-size: 32px; font-style: italic; font-weight: bold; color: #32402A; margin: 0; }
                        </style></head><body>';

                        $pages = $tables->chunk(4);
                        foreach ($pages as $pageItems) {
                            $html .= '<table class="page-table">';
                            $rows = $pageItems->chunk(2);
                            foreach ($rows as $rowItems) {
                                $html .= '<tr>';
                                foreach ($rowItems as $table) {
                                    $imagePath = storage_path('app/public/' . $table->qr_path);
                                    $qrBase64 = file_exists($imagePath) ? 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($imagePath)) : '';
                                    $restaurantName = strtoupper($restaurant->name ?? 'RESTAURANT');

                                    $html .= '<td class="quadrant"><div class="card"><div class="content-wrapper">
                                        <div class="title">' . $restaurantName . '</div><div class="orange-line"></div><div class="subtitle">EXQUISITE DINING EXPERIENCE</div>
                                        <table class="qr-bracket-table"><tr><td class="br-tl"></td><td></td><td></td></tr><tr><td></td><td><img src="' . $qrBase64 . '" class="qr-img" /></td><td></td></tr><tr><td></td><td></td><td class="br-br"></td></tr></table>
                                        <div class="btn-wrapper"><div class="scan-pill">SCAN TO MENU</div></div>
                                        <div class="loc-label">YOUR LOCATION</div><div class="table-number">Table ' . $table->table_number . '</div>
                                        </div></div></td>';
                                }
                                if ($rowItems->count() == 1)
                                    $html .= '<td class="quadrant"></td>';
                                $html .= '</tr>';
                            }
                            if ($rows->count() == 1)
                                $html .= '<tr><td class="quadrant"></td><td class="quadrant"></td></tr>';
                            $html .= '</table>';
                        }
                        $html .= '</body></html>';

                        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4', 'portrait')->setWarnings(false);
                            return response()->streamDownload(function () use ($pdf) {
                                echo $pdf->output();
                            }, 'Restaurant_ALL_QRs.pdf');
                        }
                    }),
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