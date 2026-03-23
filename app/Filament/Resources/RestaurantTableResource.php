<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RestaurantTableResource\Pages;
use App\Filament\Resources\RestaurantTableResource\RelationManagers;
use App\Models\RestaurantTable;
use App\Models\Branch; // 👈 Import Branch model
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use App\Services\Restaurant\QrCodeService;
use Filament\Tables\Columns\ImageColumn;
use App\Services\Restaurant\QrZipService;
use Filament\Tables\Actions\Action;
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

        // 👇 FIX: Branch isolation. Main Restaurant Admin only sees branch_id NULL.
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
                // 👇 Dropdown removed as per request for Main Admin
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
                    .fi-ta-ctn {
                        background-color: transparent !important;
                        box-shadow: none !important;
                        border: none !important; 
                    }
                    /* Toolbars */
                    .fi-ta-header-toolbar, .fi-ta-footer {
                        background-color: transparent !important;
                        border-color: rgba(156, 163, 175, 0.2) !important;
                    }
                    /* Inner Content wrapper */
                    .fi-ta-content {
                        background-color: transparent !important;
                    }
                    
                    /* 👇 NEW: Premium Gradient Layout for Table Cards 👇 */
                    .fi-ta-record {
                        background-color: #ffffff !important;
                        /* Sleek Blue-to-Orange border using border-image or pseudo element */
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
                    
                    /* Hover Effect */
                    .fi-ta-record:hover {
                        transform: translateY(-5px);
                        box-shadow: 0 10px 25px rgba(59, 130, 246, 0.15), 0 5px 15px rgba(244, 125, 32, 0.1) !important;
                    }

                    /* Table Number Styling */
                    .fi-ta-record .fi-ta-text-item {
                        color: #1e293b !important;
                    }
                    .dark .fi-ta-record .fi-ta-text-item {
                        color: #f8fafc !important;
                    }

                    /* Active Icon Color fix */
                    .fi-ta-record svg.text-success-500 {
                        color: #3B82F6 !important; /* Replaced default green with Blue */
                    }
                    
                    /* Buttons styling */
                    /* Edit Button */
                    .fi-ta-record .fi-ta-actions button:nth-of-type(1) {
                        background-color: #3B82F6 !important;
                        color: #ffffff !important;
                        border: none !important;
                        transition: 0.2s;
                    }
                    .fi-ta-record .fi-ta-actions button:nth-of-type(1):hover {
                        background-color: #2563eb !important;
                    }
                    
                    /* Delete Button */
                    .fi-ta-record .fi-ta-actions button:nth-of-type(2) {
                        color: #ef4444 !important;
                        border: none !important;
                        background-color: rgba(239, 68, 68, 0.05) !important;
                    }
                    .fi-ta-record .fi-ta-actions button:nth-of-type(2):hover {
                        background-color: rgba(239, 68, 68, 0.1) !important;
                    }
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

                            // Show branch name ONLY if it's not the Main Restaurant admin (optional)
                            Tables\Columns\TextColumn::make('branch.name')
                                ->size('sm')
                                ->color('gray')
                                ->visible(fn() => auth()->user()->isRestaurantAdmin() && false), // Hidden as per request
                        ]),
                        Tables\Columns\IconColumn::make('is_active')
                            ->boolean()
                            ->grow(false),
                    ]),

                    // QR Code Display Customization - Updated Padding and Height
                    ImageColumn::make('qr_path')
                        ->label('QR')
                        ->disk('public')
                        ->height(250) // 👈 Increased height from 200 to 250
                        ->width('100%')
                        ->extraImgAttributes([
                            // 👈 Reduced padding to 0.5rem so QR looks bigger inside the container
                            'style' => 'background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), rgba(244, 125, 32, 0.05)); padding: 0.5rem; border-radius: 12px; object-fit: contain; margin-top: 1rem; margin-bottom: 0.5rem; border: 1px solid rgba(59, 130, 246, 0.2); box-shadow: 0 4px 6px rgba(0,0,0,0.05);',
                        ])
                        ->visibility('public'),
                ])->space(3),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->button()
                    // Removed outline for solid blue button
                    ->visible(fn() => in_array(auth()->user()->role->name, ['restaurant_admin', 'branch_admin', 'manager'])),

                Tables\Actions\DeleteAction::make()
                    ->iconButton() // Convert to modern icon button
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn() => in_array(auth()->user()->role->name, ['restaurant_admin', 'branch_admin', 'manager'])),
            ])
            // Bulk Delete Option maintained
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => in_array(auth()->user()->role->name, ['restaurant_admin', 'branch_admin', 'manager'])),
                ]),
            ])
            ->headerActions([
                // 👇 NEW ACTION: Delete All QRs (Visible beside the PDF button) 👇
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

                        // Delete files from storage before deleting from DB
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

                // 👇 EXISTING ACTION: Download as PDF (Exact 4 QRs per page format in 2x2 grid) 👇
                Tables\Actions\Action::make('download_pdf_qr')
                    ->label('Download QRs as PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('info')
                    ->outlined()
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

                        // Create HTML for PDF (Strict 2x2 Grid using Fixed Pixels)
                        $html = '<!DOCTYPE html><html><head><style>
                            @page { margin: 15px; size: A4 portrait; }
                            body { font-family: sans-serif; text-align: center; margin: 0; padding: 0; }
                            
                            .page-table { width: 100%; border-collapse: collapse; table-layout: fixed; page-break-after: always; }
                            .page-table:last-child { page-break-after: auto; }
                            
                            .quadrant { width: 50%; padding: 10px; vertical-align: top; }
                            
                            /* Strict height to STOP DomPDF from stretching the row to full page */
                            .qr-container { 
                                border: 3px solid #3B82F6; 
                                border-radius: 15px; 
                                padding: 15px; 
                                text-align: center; 
                                height: 360px; /* Exact height prevents stretching */
                                box-sizing: border-box; 
                            }
                            
                            /* Big and Bold QR Image */
                            .qr-img { width: 220px; height: 220px; margin: 15px auto; display: block; object-fit: contain; }
                            
                            h2 { margin: 0; padding-top: 5px; color: #1e293b; font-size: 32px; font-weight: bold; }
                            h4 { margin: 10px 0 0 0; color: #F47D20; font-size: 22px; text-transform: uppercase; }
                        </style></head><body>';

                        // Group tables into chunks of 4 for each page
                        $pages = $tables->chunk(4);

                        foreach ($pages as $pageItems) {
                            $html .= '<table class="page-table">';

                            // Break 4 items into 2 rows of 2
                            $rows = $pageItems->chunk(2);

                            foreach ($rows as $rowItems) {
                                $html .= '<tr>';
                                foreach ($rowItems as $table) {
                                    // Convert image to base64 for DomPDF compatibility
                                    $imagePath = storage_path('app/public/' . $table->qr_path);
                                    $base64 = '';
                                    if ($table->qr_path && file_exists($imagePath)) {
                                        $type = pathinfo($imagePath, PATHINFO_EXTENSION);
                                        $data = file_get_contents($imagePath);
                                        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                                    }

                                    $html .= '<td class="quadrant"><div class="qr-container">';
                                    $html .= '<h2>Table: ' . $table->table_number . '</h2>';
                                    if ($base64) {
                                        $html .= '<img src="' . $base64 . '" alt="QR" class="qr-img">';
                                    } else {
                                        $html .= '<p style="margin: 80px 0; font-size: 20px; color: red;">QR Not Found</p>';
                                    }
                                    $html .= '<h4>' . ($restaurant->name ?? 'Restaurant') . '</h4>';
                                    $html .= '</div></td>';
                                }

                                // Fill empty column if row only has 1 item
                                if ($rowItems->count() == 1) {
                                    $html .= '<td class="quadrant"></td>';
                                }
                                $html .= '</tr>';
                            }

                            // Fill empty row if page only has 1 row (less than 3 items total on page)
                            if ($rows->count() == 1) {
                                $html .= '<tr><td class="quadrant"></td><td class="quadrant"></td></tr>';
                            }

                            $html .= '</table>';
                        }

                        $html .= '</body></html>';

                        // Generate and Download PDF
                        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4', 'portrait');
                            return response()->streamDownload(function () use ($pdf) {
                                echo $pdf->output();
                            }, 'Table_QRs_Document.pdf');
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('PDF Library Missing')
                                ->body('Please run: composer require barryvdh/laravel-dompdf to download PDFs.')
                                ->danger()
                                ->send();
                        }
                    }),

                // EXISTING ACTION: Download ZIP
                Tables\Actions\Action::make('download_all_qr')
                    ->label('Download ZIP QRs')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->color('gray')
                    ->outlined()
                    ->action(function () {
                        $user = auth()->user();
                        $restaurant = $user->restaurant;
                        // Passing the user object to the service to isolate QRs if needed
                        $zipPath = app(QrZipService::class)->createForRestaurant($restaurant, $user);

                        return response()
                            ->download($zipPath)
                            ->deleteFileAfterSend(true);
                    }),

                // EXISTING ACTION: Generate Tables
                Tables\Actions\Action::make('generateTables')
                    ->label('Generate Tables')
                    ->icon('heroicon-o-qr-code')
                    ->color('primary') // Changed to primary (blue)
                    ->form(function () {
                        // 👇 Form updated: Select Branch removed for all
                        return [
                            \Filament\Forms\Components\TextInput::make('total_tables')
                                ->numeric()
                                ->minValue(1)
                                ->required(),

                            \Filament\Forms\Components\TextInput::make('seating_capacity')
                                ->numeric()
                                ->default(1),
                        ];
                    })
                    ->action(function (array $data) {
                        $user = auth()->user();
                        $restaurant = $user->restaurant;

                        // 1. Identify current branch scope
                        $branchId = ($user->isBranchAdmin() || $user->isManager()) ? $user->branch_id : null;

                        // 2. Count tables specifically for THIS branch or MAIN restaurant
                        $startQuery = \App\Models\RestaurantTable::where('restaurant_id', $restaurant->id);

                        if ($branchId) {
                            $startQuery->where('branch_id', $branchId);
                        } else {
                            // Agar branch admin nahi hai, toh main restaurant ke null branch wale count karo
                            $startQuery->whereNull('branch_id');
                        }

                        $currentCount = $startQuery->count();

                        $qrService = app(\App\Services\Restaurant\QrCodeService::class);

                        // 3. Generate new tables with unique prefix per branch scope
                        for ($i = 1; $i <= $data['total_tables']; $i++) {
                            $table = \App\Models\RestaurantTable::create([
                                'restaurant_id' => $restaurant->id,
                                'branch_id' => $branchId,
                                'table_number' => 'T' . ($currentCount + $i),
                                'seating_capacity' => $data['seating_capacity'],
                            ]);

                            $qrService->generate($table);
                        }
                    })
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