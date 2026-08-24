<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ParcelQrCodeResource\Pages;
use App\Models\ParcelQrCode;
use App\Services\Restaurant\ParcelQrCodeService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ParcelQrCodeResource extends Resource
{
    protected static ?string $model = ParcelQrCode::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
   // protected static ?string $navigationGroup = 'Restaurant Management';
        protected static ?string $navigationGroup = 'Restaurant Table Setup';

    protected static ?string $modelLabel = 'Parcel Counter QR';
    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->restaurant_id !== null 
            && in_array($user->role->name ?? '', ['restaurant_admin', 'branch_admin', 'manager']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Counter Name (e.g. Main Desk, Express Pickup)')
                    ->required()
                    ->maxLength(255)
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn ($rule) => $rule->where('restaurant_id', auth()->user()->restaurant_id)
                    ),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\ImageColumn::make('qr_path')
                    ->label('QR Code')
                    ->size(80)
                    ->disk('public')
                    ->visibility('public')
                    ->extraImgAttributes(['style' => 'border: 1px solid #ccc; background: #fff; border-radius: 8px;']),
                Tables\Columns\ToggleColumn::make('is_active'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('regenerate_qr')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        if ($record->qr_path && Storage::disk('public')->exists($record->qr_path)) {
                            Storage::disk('public')->delete($record->qr_path);
                        }
                        
                        $record->update([
                            'qr_token' => Str::uuid(),
                        ]);
                        
                        app(ParcelQrCodeService::class)->generate($record);
                    }),
                    
                Tables\Actions\Action::make('download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->label('Download')
                    ->url(fn (ParcelQrCode $record) => asset('storage/' . $record->qr_path))
                    ->openUrlInNewTab(),
                
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListParcelQrCodes::route('/'),
            'create' => Pages\CreateParcelQrCode::route('/create'),
            'edit' => Pages\EditParcelQrCode::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
{
    $user = auth()->user();

    $query = parent::getEloquentQuery()
        ->where('restaurant_id', $user->restaurant_id);

    if ($user->branch_id !== null) {
        // Branch-level user
        $query->where('branch_id', $user->branch_id);
    } else {
        // Restaurant-level user
        $query->whereNull('branch_id');
    }

    return $query;
}
}