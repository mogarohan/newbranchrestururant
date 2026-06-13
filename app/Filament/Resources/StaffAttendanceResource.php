<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StaffAttendanceResource\Pages;
use App\Models\StaffAttendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Filament\Notifications\Notification;

class StaffAttendanceResource extends Resource
{
    protected static ?string $model = StaffAttendance::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Access Control';
    protected static ?string $pluralLabel = 'Attendance & Reports';

    // 🌟 YEH FUNCTION CHECK KAREGA KI SALARY DIKHANI HAI YA NAHI 🌟
    public static function canViewSalary(): bool
    {
        $user = auth()->user();
        return $user->isSuperAdmin() || $user->isRestaurantAdmin() || $user->isBranchAdmin() || $user->isManager();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query, $livewire) {
                $date = $livewire->activeDate ?? now()->toDateString();
                return $query->whereDate('date', $date);
            })
            ->columns([
                TextColumn::make('date')
                    ->label('Date')
                    ->date('d M Y')
                    ->weight('bold'),

                TextColumn::make('staff.name')
                    ->label('Staff Name')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('staff.role.label')
                    ->label('Role')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Manager' => 'warning',
                        'Chef' => 'danger',
                        'Waiter' => 'info',
                        default => 'gray',
                    }),

                SelectColumn::make('status')
                    ->label('Mark Attendance')
                    ->options([
                        'pending' => '- (Pending)',
                        'present' => 'P - Present',
                        'absent' => 'A - Absent',
                        'half_day' => 'H - Half Day',
                    ])
                    ->selectablePlaceholder(false)
                    ->extraAttributes(['style' => 'min-width: 140px; font-weight: 700;']),

                // 🌟 VISIBILITY FIX 🌟
                TextInputColumn::make('overtime_hours')
                    ->label('OT (Hrs)')
                    ->type('number')
                    ->visible(fn() => self::canViewSalary())
                    ->extraAttributes(['style' => 'width: 80px;']),

                TextInputColumn::make('manual_deduction')
                    ->label('Cut (-) ₹')
                    ->type('number')
                    ->visible(fn() => self::canViewSalary())
                    ->extraAttributes(['style' => 'width: 90px; color: red; font-weight: bold;']),

                TextInputColumn::make('manual_bonus')
                    ->label('Add (+) ₹')
                    ->type('number')
                    ->visible(fn() => self::canViewSalary())
                    ->extraAttributes(['style' => 'width: 90px; color: green; font-weight: bold;']),
            ])
            ->defaultSort('staff.name', 'asc')
            ->filters([
                SelectFilter::make('Role')
                    ->relationship('staff.role', 'label')
                    ->label('Filter by Role'),
            ])

            ->actions([
                // 🌟 VISIBILITY FIX FOR SETUP PAYROLL BUTTON 🌟
                Tables\Actions\Action::make('setup_payroll')
                    ->label('Salary Setup')
                    ->icon('heroicon-o-banknotes')
                    ->color('warning')
                    ->button()
                    ->outlined()
                    ->visible(fn() => self::canViewSalary())
                    ->mountUsing(function (Forms\ComponentContainer $form, StaffAttendance $record) {
                        $form->fill([
                            'monthly_salary' => $record->staff->monthly_salary ?? 25000,
                            'shift_hours' => $record->staff->shift_hours ?? 8,
                        ]);
                    })
                    ->form([
                        Forms\Components\TextInput::make('monthly_salary')
                            ->label('Base Monthly Salary')
                            ->numeric()
                            ->prefix('₹')
                            ->required(),

                        Forms\Components\Select::make('shift_hours')
                            ->label('Shift Duty (Hours)')
                            ->options([
                                8 => '8 Hours (Standard Shift)',
                                9 => '9 Hours',
                                10 => '10 Hours (Extended Shift)',
                                11 => '11 Hours',
                                12 => '12 Hours (Double Shift)',
                            ])
                            ->required(),
                    ])
                    ->action(function (array $data, StaffAttendance $record) {
                        $record->staff->update([
                            'monthly_salary' => $data['monthly_salary'],
                            'shift_hours' => $data['shift_hours'],
                        ]);

                        Notification::make()
                            ->title('Payroll Updated')
                            ->body("{$record->staff->name} ki salary aur shift update ho gayi hai.")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_all_present')
                        ->label('Mark Selected as Present')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn(Collection $records) => $records->each->update(['status' => 'present']))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('mark_all_absent')
                        ->label('Mark Selected as Absent')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn(Collection $records) => $records->each->update(['status' => 'absent']))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaffAttendances::route('/'),
            'preview' => Pages\MonthlyPreview::route('/preview'),
        ];
    }
}