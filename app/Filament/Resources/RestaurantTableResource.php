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
                        border: 1.5px solid #000000 !important; /* BLACK BORDER */
                        border-radius: 1.25rem !important;
                        box-shadow: 0 8px 32px rgba(42, 71, 149, 0.10) !important;
                        overflow: hidden !important;
                    }
                    .dark .fi-ta-ctn { background: rgba(15, 15, 20, 0.75) !important; border-color: #000 !important; }

                    /* --- CARD STYLING ── */
                    .fi-ta-record {
                        background: rgba(255, 255, 255, 0.40) !important;
                        border: 1.5px solid #000000 !important; /* BLACK BORDER ON CARDS */
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
                            // 👇 Table Number AND Seating Capacity Displayed Together 👇
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
                    ->action(function () {
                        // 1. Temporarily boost memory limits
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

                        // 2. Compress PNG into memory
                        $bgImagePath = public_path('images/b.png');
                        $bgBase64 = '';

                        if (file_exists($bgImagePath)) {
                            if (extension_loaded('gd')) {
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
                                $bgBase64 = 'data:image/png;base64,' . base64_encode($bgData);
                            }
                        }

                        // 3. Strict DomPDF HTML/CSS Layout with FIXED VERTICAL SPACING
                        $html = '<!DOCTYPE html><html><head><style>
                            @page { margin: 15px; size: A4 portrait; }
                            body { margin: 0; padding: 0; background-color: #ffffff; font-family: "Helvetica", "Arial", sans-serif; }
                            
                            .page-table { 
                                width: 100%; 
                                border-collapse: separate; 
                                border-spacing: 15px;
                                table-layout: fixed; 
                                page-break-after: always; 
                            }
                            .page-table:last-child { page-break-after: auto; }
                            
                            .quadrant { 
                                width: 50%; 
                                height: 460px; 
                                padding: 0; 
                                vertical-align: top; 
                            }

                            .card {
                                border: 1px dashed #cbd5e1;
                                border-radius: 8px;
                                height: 460px;
                                box-sizing: border-box;
                                text-align: center;
                                background-image: url("' . $bgBase64 . '");
                                background-size: cover;
                                background-position: center;
                                background-repeat: no-repeat;
                            }

                            .content-wrapper {
                                background-color: transparent; 
                                width: 100%;
                                height: 100%;
                                padding-top: 25px; 
                                box-sizing: border-box;
                            }

                            .title { font-family: "Times", serif; font-size: 24px; font-weight: bold; color: #9A3B2A; margin: 0; text-transform: uppercase; letter-spacing: 1px; }
                            .orange-line { border-top: 3px solid #E47A33; width: 40px; margin: 6px auto; } 
                            .subtitle { font-size: 9px; color: #4B5320; font-weight: bold; letter-spacing: 1px; margin-bottom: 12px; }

                            .qr-bracket-table {
                                margin: 0 auto 12px auto; 
                                border-collapse: collapse;
                            }
                            .qr-bracket-table td { padding: 0; }
                            .br-tl { border-top: 3px solid #E47A33; border-left: 3px solid #E47A33; width: 25px; height: 25px; }
                            .br-br { border-bottom: 3px solid #E47A33; border-right: 3px solid #E47A33; width: 25px; height: 25px; }
                            
                            .qr-img { 
                                width: 135px; 
                                height: 135px; 
                                border: 2px solid #8B5CF6;
                                border-radius: 8px;
                                padding: 4px;
                                background-color: #ffffff; 
                                display: block;
                                margin: 8px; 
                            }

                            .btn-wrapper { margin-bottom: 12px; } 
                            .scan-tag { background-color: #769772; color: #ffffff; padding: 4px 20px; border-radius: 4px; font-size: 10px; font-weight: bold; display: inline-block; margin-bottom: 4px; }
                            .scan-pill { background-color: #B85C4A; color: #ffffff; padding: 6px 25px; border-radius: 15px; font-size: 10px; font-weight: bold; display: inline-block; letter-spacing: 1px;}
                            
                            .loc-label { font-size: 9px; color: #7F8A74; font-weight: bold; margin-bottom: 2px; letter-spacing: 0.5px; } 
                            .table-number { font-family: "Times", serif; font-size: 32px; font-style: italic; font-weight: bold; color: #32402A; margin: 0; }
                            
                        </style></head><body>';

                        // 4. Generate 2x2 Grid Pages
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

                                    $restaurantName = strtoupper($restaurant->name ?? 'RESTAURANT');

                                    // Render Card
                                    $html .= '<td class="quadrant"><div class="card">';

                                    // Inner Content Box
                                    $html .= '<div class="content-wrapper">';

                                    $html .= '<div class="title">' . $restaurantName . '</div>';
                                    $html .= '<div class="orange-line"></div>';
                                    $html .= '<div class="subtitle">EXQUISITE DINING EXPERIENCE</div>';

                                    // QR Code Block
                                    $html .= '<table class="qr-bracket-table">
                                                <tr>
                                                    <td class="br-tl"></td><td></td><td></td>
                                                </tr>
                                                <tr>
                                                    <td></td><td><img src="' . $qrBase64 . '" class="qr-img" /></td><td></td>
                                                </tr>
                                                <tr>
                                                    <td></td><td></td><td class="br-br"></td>
                                                </tr>
                                              </table>';

                                    $html .= '<div class="btn-wrapper">';
                                    $html .= '<div class="scan-pill">SCAN TO MENU</div>';
                                    $html .= '</div>';

                                    $html .= '<div class="loc-label">YOUR LOCATION</div>';
                                    $html .= '<div class="table-number">Table ' . $table->table_number . '</div>';

                                    // Close wrappers
                                    $html .= '</div></div></td>';
                                }

                                // Fill empty column if only 1 item in the row
                                if ($rowItems->count() == 1) {
                                    $html .= '<td class="quadrant"></td>';
                                }
                                $html .= '</tr>';
                            }

                            // Fill empty row if only 1 row in the page
                            if ($rows->count() == 1) {
                                $html .= '<tr><td class="quadrant"></td><td class="quadrant"></td></tr>';
                            }
                            $html .= '</table>';
                        }

                        $html .= '</body></html>';

                        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                            // Render PDF
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

                Tables\Actions\Action::make('download_all_qr')
                    ->label('Download ZIP QRs')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->color('gray')
                    ->outlined()
                    ->action(function () {
                        $user = auth()->user();
                        $restaurant = $user->restaurant;
                        $zipPath = app(QrZipService::class)->createForRestaurant($restaurant, $user);

                        return response()
                            ->download($zipPath)
                            ->deleteFileAfterSend(true);
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