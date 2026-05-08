<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\Filter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-currency-rupee';
    protected static ?string $navigationGroup = 'Financials';
    protected static ?string $navigationLabel = 'Tax Invoices';

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery()->where('restaurant_id', $user->restaurant_id);

        if ($user->isBranchAdmin() || $user->isManager()) {
            $query->where('branch_id', $user->branch_id);
        } else {
            $query->whereNull('branch_id');
        }

        return $query;
    }

     public static function canAccess(): bool
    {
        return auth()->check()
            && auth()->user()->restaurant_id
            && in_array(auth()->user()->role->name ?? null, ['manager', 'branch_admin','restauranrt_admin']);
    }
    // 🔒 STRICTLY IMMUTABLE RESOURCE
    public static function canCreate(): bool { return false; }
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool { return false; }
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool { return false; }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),
                Tables\Columns\TextColumn::make('invoice_date')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('grand_total')
                    ->money('INR') // Update to your locale if needed
                    ->sortable()
                    ->weight('bold'),
            ])
            ->defaultSort('invoice_sequence', 'desc')
            ->filters([
                // 1. By Date
                Filter::make('invoice_date')
                    ->form([
                        DatePicker::make('created_from')->label('From Date'),
                        DatePicker::make('created_until')->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'], fn (Builder $q, $date) => $q->whereDate('invoice_date', '>=', $date))
                            ->when($data['created_until'], fn (Builder $q, $date) => $q->whereDate('invoice_date', '<=', $date));
                    }),
                
                // 2. By Invoice Range (Sequence)
                Filter::make('invoice_sequence')
                    ->form([
                        TextInput::make('seq_from')->numeric()->label('Start Sequence (e.g. 1)'),
                        TextInput::make('seq_until')->numeric()->label('End Sequence (e.g. 50)'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['seq_from'], fn (Builder $q, $seq) => $q->where('invoice_sequence', '>=', $seq))
                            ->when($data['seq_until'], fn (Builder $q, $seq) => $q->where('invoice_sequence', '<=', $seq));
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('download_pdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function (Invoice $record) {
                        return response()->streamDownload(function () use ($record) {
                            echo self::generatePdf($record)->output();
                        }, "{$record->invoice_number}.pdf");
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // 📦 ENTERPRISE BULK DOWNLOAD (Memory Safe + Collision Proof + Cleaned Up)
                    Tables\Actions\BulkAction::make('export_zip')
                        ->label('Export Selected as ZIP')
                        ->icon('heroicon-o-archive-box-arrow-down')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {

                            if ($records->isEmpty()) {
                                return;
                            }

                            $timestamp = now()->format('Y_m_d_His');
                            $random = Str::uuid();

                            // Safer isolated temp paths (NOT exposed to the public symlink)
                            $tempDir = storage_path("app/temp/invoices_{$timestamp}_{$random}");
                            $zipPath = storage_path("app/temp/invoices_{$timestamp}_{$random}.zip");

                            File::ensureDirectoryExists($tempDir);

                            $zip = new ZipArchive();

                            try {
                                if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                                    throw new \Exception('Unable to create ZIP archive.');
                                }

                                foreach ($records as $invoice) {
                                    $pdfFileName = "{$invoice->invoice_number}.pdf";
                                    $pdfTempPath = "{$tempDir}/{$pdfFileName}";

                                    // Generate PDF directly to disk
                                    self::generatePdf($invoice)->save($pdfTempPath);

                                    // Add physical file into zip
                                    $zip->addFile($pdfTempPath, $pdfFileName);
                                    
                                    // Optional: Immediately release filesystem cache
                                    clearstatcache(true, $pdfTempPath);
                                }

                                $zip->close();

                                // Cleanup temp PDFs BEFORE response
                                File::deleteDirectory($tempDir);

                                return response()
                                    ->download($zipPath)
                                    ->deleteFileAfterSend(true);

                            } catch (\Throwable $e) {
                                // Ensure cleanup even on failure
                                if (isset($zip)) {
                                    @$zip->close();
                                }

                                File::deleteDirectory($tempDir);

                                if (File::exists($zipPath)) {
                                    File::delete($zipPath);
                                }

                                throw $e;
                            }
                        }),
                ]),
            ]);
    }

    // PDF Generator Helper
    private static function generatePdf(Invoice $invoice)
    {
        $restaurant = $invoice->restaurant;
        $itemsHtml = '';
        foreach ($invoice->items_snapshot as $item) {
            $hsn = isset($item['hsn_code']) && $item['hsn_code'] ? "<br><small style='color:gray;'>HSN: {$item['hsn_code']}</small>" : '';
            $itemsHtml .= "<tr>
                <td style='padding: 10px; border-bottom: 1px solid #ddd;'>{$item['name']} {$hsn}</td>
                <td style='padding: 10px; border-bottom: 1px solid #ddd; text-align: center;'>{$item['qty']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #ddd; text-align: right;'>{$item['unit_price']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #ddd; text-align: right;'>{$item['total']}</td>
            </tr>";
        }

        $html = "
            <div style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
                <div style='text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 20px;'>
                    <h1 style='margin: 0; font-size: 28px;'>{$restaurant->name}</h1>
                    <p style='margin: 5px 0 0 0;'>GSTIN: <strong>" . ($invoice->gstin ?? 'N/A') . "</strong> | POS: " . ($invoice->place_of_supply ?? 'N/A') . "</p>
                    <h2 style='color: #555; margin-top: 15px;'>TAX INVOICE</h2>
                </div>
                
                <table style='width: 100%; margin-bottom: 30px;'>
                    <tr>
                        <td style='width: 50%;'>
                            <p><strong>Invoice No:</strong> {$invoice->invoice_number}</p>
                            <p><strong>Date:</strong> {$invoice->invoice_date->format('d M Y')}</p>
                        </td>
                        <td style='width: 50%; text-align: right;'>
                            <p><strong>Billed To:</strong> {$invoice->customer_name}</p>
                        </td>
                    </tr>
                </table>
                
                <table style='width: 100%; border-collapse: collapse; margin-top: 20px;'>
                    <thead>
                        <tr style='background: #f8f9fa; border-bottom: 2px solid #ddd;'>
                            <th style='padding: 10px; text-align: left;'>Item Description</th>
                            <th style='padding: 10px; text-align: center;'>Qty</th>
                            <th style='padding: 10px; text-align: right;'>Rate</th>
                            <th style='padding: 10px; text-align: right;'>Total</th>
                        </tr>
                    </thead>
                    <tbody>{$itemsHtml}</tbody>
                </table>
                
                <div style='width: 50%; float: right; margin-top: 30px;'>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px; text-align: right;'><strong>Subtotal:</strong></td>
                            <td style='padding: 8px; text-align: right;'>{$invoice->subtotal}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; text-align: right;'><strong>Tax (GST):</strong></td>
                            <td style='padding: 8px; text-align: right;'>{$invoice->tax_amount}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; text-align: right;'><strong>Extra Charges:</strong></td>
                            <td style='padding: 8px; text-align: right;'>{$invoice->extra_charges}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; text-align: right;'><strong>Discount:</strong></td>
                            <td style='padding: 8px; text-align: right; color: red;'>-{$invoice->discount_amount}</td>
                        </tr>
                        <tr style='background: #f8f9fa; border-top: 2px solid #000; border-bottom: 2px solid #000;'>
                            <td style='padding: 12px; text-align: right; font-size: 18px;'><strong>GRAND TOTAL:</strong></td>
                            <td style='padding: 12px; text-align: right; font-size: 18px;'><strong>{$invoice->grand_total}</strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        ";

        // 🚀 DOMPDF Optimizations for Performance & Security
        return Pdf::loadHTML($html)
            ->setPaper('a4')
            ->setWarnings(false)
            ->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false, // Disables external networks/images to prevent memory spikes
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageInvoices::route('/'),
        ];
    }
}