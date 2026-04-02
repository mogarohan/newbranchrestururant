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
        return $table
            ->heading(new HtmlString('
                <style>
                    /* Main Table Container */
                    .fi-ta-ctn { background-color: transparent !important; box-shadow: none !important; border: none !important; }
                    .fi-ta-header-toolbar, .fi-ta-footer { background-color: transparent !important; border-color: rgba(156, 163, 175, 0.2) !important; }
                    .fi-ta-content { background-color: transparent !important; }
                    
                    /* Premium Gradient Layout for Table Cards */
                    .fi-ta-record {
                        background-color: #ffffff !important;
                        border: 2px solid transparent !important;
                        background-image: linear-gradient(#ffffff, #ffffff), linear-gradient(135deg, #3B82F6, #F47D20);
                        background-origin: border-box;
                        background-clip: padding-box, border-box;
                        border-radius: 16px !important;
                        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05) !important;
                        transition: all 0.3s ease;
                    }
                    .dark .fi-ta-record {
                        background-color: #1e293b !important;
                        background-image: linear-gradient(#1e293b, #1e293b), linear-gradient(135deg, #3B82F6, #F47D20);
                    }
                    .fi-ta-record:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(59, 130, 246, 0.15), 0 5px 15px rgba(244, 125, 32, 0.1) !important; }
                    
                    .fi-ta-record .fi-ta-text-item { color: #1e293b !important; }
                    .dark .fi-ta-record .fi-ta-text-item { color: #f8fafc !important; }
                    .fi-ta-record svg.text-success-500 { color: #3B82F6 !important; }
                    
                    .fi-ta-record .fi-ta-actions button:nth-of-type(1) { background-color: #3B82F6 !important; color: #ffffff !important; border: none !important; transition: 0.2s; }
                    .fi-ta-record .fi-ta-actions button:nth-of-type(1):hover { background-color: #2563eb !important; }
                    .fi-ta-record .fi-ta-actions button:nth-of-type(2) { color: #ef4444 !important; border: none !important; background-color: rgba(239, 68, 68, 0.05) !important; }
                    .fi-ta-record .fi-ta-actions button:nth-of-type(2):hover { background-color: rgba(239, 68, 68, 0.1) !important; }
                </style>
                <span style="font-size: 1.25rem; font-weight: 800;">Tables & QR Codes</span>
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
                                ->label('Table No')
                                ->weight(\Filament\Support\Enums\FontWeight::Bold)
                                ->size('xl'),
                        ]),
                        Tables\Columns\IconColumn::make('is_active')
                            ->boolean()
                            ->grow(false),
                    ]),

                    // QR Code Display Customization - Updated to fit the new portrait design
                    ImageColumn::make('qr_path')
                        ->label('QR')
                        ->disk('public')
                        ->height(350) // 👈 Increased significantly to show the new portrait design
                        ->width('100%')
                        ->extraImgAttributes([
                            'style' => 'object-fit: contain; margin-top: 1rem; margin-bottom: 0.5rem; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));',
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
                                'table_number' => 'T-0' . ($currentCount + $i), // Formatted to match T-01 design
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
                        $bgImagePath = public_path('images/QR-BG.png'); 
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
                                padding-top: 25px; /* 👇 Reduced from 35px to pull content up */
                                box-sizing: border-box;
                            }

                            .title { font-family: "Times", serif; font-size: 24px; font-weight: bold; color: #9A3B2A; margin: 0; text-transform: uppercase; letter-spacing: 1px; }
                            .orange-line { border-top: 3px solid #E47A33; width: 40px; margin: 6px auto; } /* 👇 Reduced margin */
                            .subtitle { font-size: 9px; color: #4B5320; font-weight: bold; letter-spacing: 1px; margin-bottom: 12px; } /* 👇 Reduced from 20px */

                            .qr-bracket-table {
                                margin: 0 auto 12px auto; /* 👇 Reduced from 20px */
                                border-collapse: collapse;
                            }
                            .qr-bracket-table td { padding: 0; }
                            .br-tl { border-top: 3px solid #E47A33; border-left: 3px solid #E47A33; width: 25px; height: 25px; }
                            .br-br { border-bottom: 3px solid #E47A33; border-right: 3px solid #E47A33; width: 25px; height: 25px; }
                            
                            .qr-img { 
                                width: 135px; /* 👇 Scaled down slightly to guarantee fit */
                                height: 135px; 
                                border: 2px solid #8B5CF6;
                                border-radius: 8px;
                                padding: 4px;
                                background-color: #ffffff; 
                                display: block;
                                margin: 8px; /* 👇 Reduced from 10px */
                            }

                            .btn-wrapper { margin-bottom: 12px; } /* 👇 Reduced from 15px */
                            .scan-tag { background-color: #769772; color: #ffffff; padding: 4px 20px; border-radius: 4px; font-size: 10px; font-weight: bold; display: inline-block; margin-bottom: 4px; }
                            .scan-pill { background-color: #B85C4A; color: #ffffff; padding: 6px 25px; border-radius: 15px; font-size: 10px; font-weight: bold; display: inline-block; letter-spacing: 1px;}
                            
                            .loc-label { font-size: 9px; color: #7F8A74; font-weight: bold; margin-bottom: 2px; letter-spacing: 0.5px; } /* 👇 Reduced from 5px */
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

                // 👇 PDF DOWNLOAD ACTION (UPDATED FOR 2x2 GRID WITH NEW DESIGN) 👇
                // Tables\Actions\Action::make('download_pdf_qr')
                //     ->label('Download QRs as PDF')
                //     ->icon('heroicon-o-document-arrow-down')
                //     ->color('info')
                //     ->outlined()
                //     ->action(function () {
                //         $user = auth()->user();
                //         $restaurant = $user->restaurant;
                //         $branchId = ($user->isBranchAdmin() || $user->isManager()) ? $user->branch_id : null;

                //         $query = \App\Models\RestaurantTable::where('restaurant_id', $restaurant->id);
                //         if ($branchId) {
                //             $query->where('branch_id', $branchId);
                //         } else {
                //             $query->whereNull('branch_id');
                //         }
                //         $tables = $query->get();

                //         // 2x2 Grid PDF Styling
                //         $html = '<!DOCTYPE html><html><head><style>
                //             @page { margin: 10px; size: A4 portrait; }
                //             @import url("https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,900;1,900&family=Inter:wght@600;700;800&display=swap");
                //             body { font-family: "Inter", sans-serif; text-align: center; margin: 0; padding: 0; background: #fff; }
                            
                //             .page-table { width: 100%; border-collapse: collapse; table-layout: fixed; page-break-after: always; height: 100vh; }
                //             .page-table:last-child { page-break-after: auto; }
                            
                //             .quadrant { width: 50%; height: 50%; padding: 15px; vertical-align: middle; }
                            
                //             /* Clean wrapper for the SVG */
                //             .qr-container { 
                //                 border: 1px dashed #cbd5e1; 
                //                 border-radius: 4px; 
                //                 padding: 10px; 
                //                 text-align: center; 
                //                 box-sizing: border-box; 
                //                 display: inline-block;
                //             }
                            
                //             .qr-img { width: auto; height: 480px; display: block; margin: 0 auto; object-fit: contain; }
                //         </style></head><body>';

                //         $pages = $tables->chunk(4);

                //         foreach ($pages as $pageItems) {
                //             $html .= '<table class="page-table">';
                //             $rows = $pageItems->chunk(2);

                //             foreach ($rows as $rowItems) {
                //                 $html .= '<tr>';
                //                 foreach ($rowItems as $table) {
                //                     $imagePath = storage_path('app/public/' . $table->qr_path);
                //                     $base64 = '';
                //                     if ($table->qr_path && file_exists($imagePath)) {
                //                         $type = pathinfo($imagePath, PATHINFO_EXTENSION);
                //                         $data = file_get_contents($imagePath);
                //                         // Because it's an SVG, we use image/svg+xml
                //                         $base64 = 'data:image/svg+xml;base64,' . base64_encode($data);
                //                     }

                //                     $html .= '<td class="quadrant"><div class="qr-container">';
                //                     if ($base64) {
                //                         $html .= '<img src="' . $base64 . '" alt="QR" class="qr-img">';
                //                     } else {
                //                         $html .= '<p style="color: red;">QR Not Found</p>';
                //                     }
                //                     $html .= '</div></td>';
                //                 }

                //                 if ($rowItems->count() == 1) {
                //                     $html .= '<td class="quadrant"></td>';
                //                 }
                //                 $html .= '</tr>';
                //             }

                //             if ($rows->count() == 1) {
                //                 $html .= '<tr><td class="quadrant"></td><td class="quadrant"></td></tr>';
                //             }
                //             $html .= '</table>';
                //         }

                //         $html .= '</body></html>';

                //         if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                //             // Enable SVG processing in DomPDF
                //             $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4', 'portrait')->setWarnings(false);
                //             return response()->streamDownload(function () use ($pdf) {
                //                 echo $pdf->output();
                //             }, 'Restaurant_QRs_Premium.pdf');
                //         } else {
                //             \Filament\Notifications\Notification::make()
                //                 ->title('PDF Library Missing')
                //                 ->body('Please run: composer require barryvdh/laravel-dompdf to download PDFs.')
                //                 ->danger()
                //                 ->send();
                //         }
                //     }),

                // Tables\Actions\Action::make('download_all_qr')
                //     ->label('Download ZIP QRs')
                //     ->icon('heroicon-o-archive-box-arrow-down')
                //     ->color('gray')
                //     ->outlined()
                //     ->action(function () {
                //         $user = auth()->user();
                //         $restaurant = $user->restaurant;
                //         $zipPath = app(QrZipService::class)->createForRestaurant($restaurant, $user);

                //         return response()
                //             ->download($zipPath)
                //             ->deleteFileAfterSend(true);
                //     }),

                // 👇 PDF DOWNLOAD ACTION (FIXED FOR PERFECT WHITE RENDERING) 👇
                // 👇 PDF DOWNLOAD ACTION (EXACT SVGs IN 2x2 GRID) 👇
               // 👇 PDF DOWNLOAD ACTION (USING IMAGICK TO CONVERT SVG TO PNG FOR DOMPDF) 👇
               
               // 👇 PDF DOWNLOAD ACTION (PERFECT 2x2 GRID & STRICT DESIGN) 👇
                // 👇 PDF DOWNLOAD ACTION (PERFECT 2x2 GRID WITH GUARANTEED DOODLE BACKGROUND) 👇
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