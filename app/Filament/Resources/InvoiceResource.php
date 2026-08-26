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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\Colors\Color; 
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
        }
        // 🌟 FIX: For Restaurant Admins, we don't apply whereNull('branch_id'), so they can see all invoices!

        return $query->with(['qrSession.restaurantTable', 'roomSession.room', 'parcelQrSession.parcelQrCode']);
    }

     public static function canAccess(): bool
    {
        return auth()->check()
            && auth()->user()->restaurant_id
            && in_array(auth()->user()->role->name ?? null, ['manager', 'branch_admin','restaurant_admin']);
    }
    
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
                    
                Tables\Columns\TextColumn::make('bill_number')
                    ->label('Bill #')
                    ->searchable()
                    ->sortable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('location')
                    ->label('Location')
                    ->getStateUsing(function (Invoice $record) {
                        if ($record->room_session_id) {
                            return "🚪 ROOM " . ($record->roomSession->room->room_number ?? '?');
                        } elseif ($record->parcel_qr_session_id) {
                            $name = $record->parcelQrSession->parcelQrCode->name ?? 'Parcel';
                            return "🛍️ " . strtoupper($name);
                        } elseif ($record->qr_session_id) {
                            $tableNum = $record->qrSession->restaurantTable->table_number ?? 'Takeaway';
                            if (str_contains(strtolower($tableNum), 'takeaway')) {
                                return "🥡 TAKEAWAY";
                            }
                            $cleanNum = str_replace(['Table-', 'Table - ', 'Table ', 'T-', 't-'], '', $tableNum);
                            return "🍽️ TABLE-" . trim($cleanNum);
                        }
                        return 'Counter';
                    })
                    ->badge()
                    ->color(fn (Invoice $record): array => match (true) {
                        (bool) $record->parcel_qr_session_id => Color::Amber,
                        (bool) $record->room_session_id => Color::Blue,
                        default => Color::Emerald,
                    }),

                Tables\Columns\TextColumn::make('invoice_date')
                    ->date('d M Y')
                    ->timezone('Asia/Kolkata')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->weight('bold')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('grand_total')
                    ->money('INR')
                    ->sortable()
                    ->weight('bold'),
            ])
            ->defaultSort('invoice_sequence', 'desc')
            ->filters([
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
                Tables\Actions\Action::make('view_pdf')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn (Invoice $record) => 'Invoice: ' . $record->invoice_number)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalWidth(MaxWidth::FourExtraLarge)
                    ->modalContent(function (Invoice $record) {
                        $pdf = self::generatePdf($record);
                        $base64 = base64_encode($pdf->output());
                        return new HtmlString('<iframe src="data:application/pdf;base64,' . $base64 . '" width="100%" height="650px" style="border: none; border-radius: 8px;"></iframe>');
                    }),

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

                            $timestamp = now()->timezone('Asia/Kolkata')->format('Y_m_d_His');
                            $random = Str::uuid();

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

                                    self::generatePdf($invoice)->save($pdfTempPath);
                                    $zip->addFile($pdfTempPath, $pdfFileName);
                                    clearstatcache(true, $pdfTempPath);
                                }

                                $zip->close();
                                File::deleteDirectory($tempDir);

                                return response()
                                    ->download($zipPath)
                                    ->deleteFileAfterSend(true);

                            } catch (\Throwable $e) {
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

        $logoBase64 = '';
        if ($restaurant && $restaurant->logo_path) {
            $logoFullPath = Storage::disk('public')->path($restaurant->logo_path);
            if (file_exists($logoFullPath)) {
                $mime = mime_content_type($logoFullPath);
                $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoFullPath));
            }
        }
        $logoHtml = $logoBase64 ? "<img src='{$logoBase64}' style='max-height: 80px; max-width: 180px; object-fit: contain;' />" : "";

        $gstIn = $invoice->gstin ?? 'N/A';
        $pos = $invoice->place_of_supply ?? 'N/A';

        $locationName = 'Counter';
        if ($invoice->room_session_id && $invoice->roomSession) {
            $locationName = "Room " . ($invoice->roomSession->room->room_number ?? '');
        } elseif ($invoice->parcel_qr_session_id && $invoice->parcelQrSession) {
            $locationName = "Parcel Queue: " . ($invoice->parcelQrSession->parcelQrCode->name ?? 'PARCEL');
        } elseif ($invoice->qr_session_id && $invoice->qrSession) {
            $tableNum = $invoice->qrSession->restaurantTable->table_number ?? '';
            $cleanNum = str_replace(['Table-', 'Table - ', 'Table ', 'T-', 't-'], '', $tableNum);
            $locationName = "Table-" . trim($cleanNum);
        }

        $html = "
            <div style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
                
                <table style='width: 100%; border-bottom: 2px solid #000; padding-bottom: 15px; margin-bottom: 20px;'>
                    <tr>
                        <td style='width: 30%; vertical-align: middle; text-align: left;'>
                            {$logoHtml}
                        </td>
                        <td style='width: 70%; text-align: right; vertical-align: middle;'>
                            <h1 style='margin: 0; font-size: 26px; text-transform: uppercase;'>{$restaurant->name}</h1>
                            <p style='margin: 5px 0 0 0; font-size: 12px;'>GSTIN: <strong>{$gstIn}</strong> | POS: {$pos}</p>
                            <h2 style='color: #555; margin-top: 10px; margin-bottom: 0;'>TAX INVOICE</h2>
                        </td>
                    </tr>
                </table>
                
                <table style='width: 100%; margin-bottom: 30px;'>
                    <tr>
                        <td style='width: 50%; vertical-align: top;'>
                            <p style='margin: 3px 0;'><strong>Invoice No:</strong> {$invoice->invoice_number}</p>
                            <p style='margin: 3px 0;'><strong>Ref Bill No:</strong> {$invoice->bill_number}</p> 
                            <p style='margin: 3px 0;'><strong>Date:</strong> " . \Carbon\Carbon::parse($invoice->invoice_date)->timezone('Asia/Kolkata')->format('d M Y') . "</p>
                            <p style='margin: 3px 0;'><strong>Location:</strong> <span style='font-weight: bold; color: #1e40af;'>{$locationName}</span></p>
                        </td>
                        <td style='width: 50%; text-align: right; vertical-align: top;'>
                            <p style='margin: 3px 0;'><strong>Billed To:</strong></p>
                            <p style='margin: 3px 0; font-size: 16px; font-weight: bold;'>{$invoice->customer_name}</p>
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

        return Pdf::loadHTML($html)
            ->setPaper('a4')
            ->setWarnings(false)
            ->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false, 
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageInvoices::route('/'),
        ];
    }
}