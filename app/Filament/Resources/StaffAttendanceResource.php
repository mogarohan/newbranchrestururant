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

    /* -----------------------------------------------------------------
     | 🌟 SECURITY HELPER 0: Menu Visibility
     |-----------------------------------------------------------------*/
    /* -----------------------------------------------------------------
       | 🌟 SECURITY HELPER 0: Menu Visibility (SaaS Logic)
       |-----------------------------------------------------------------*/
    public static function canAccess(): bool
    {
        $user = auth()->user();

        // 1. Super Admin ko yeh menu apne panel me nahi dikhega
        if ($user->isSuperAdmin()) {
            return false;
        }

        // 2. 🌟 SAAS LOGIC: Check agar is restaurant ko Super Admin ne permission di hai ya nahi
        $restaurant = $user->restaurant;
        if (!$restaurant || !$restaurant->has_attendance) {
            // Agar permission nahi hai, toh menu bilkul hide ho jayega!
            return false;
        }

        // 3. Agar permission mili hai, toh sirf in 3 logo ko access do
        return $user->isRestaurantAdmin() || $user->isBranchAdmin() || $user->isManager();
    }
    /* -----------------------------------------------------------------
     | 🌟 SECURITY HELPER 1: Kaun Salary/Payroll Data Dekh Sakta Hai
     |-----------------------------------------------------------------*/
    public static function canViewSalary(): bool
    {
        $user = auth()->user();
        // Sirf Admin aur Branch Admin ko salary dikhegi, Manager ko nahi.
        return $user->isRestaurantAdmin() || $user->isBranchAdmin();
    }

    /* -----------------------------------------------------------------
     | 🌟 SECURITY HELPER 2: Kaun Kiski Attendance Laga Sakta Hai
     |-----------------------------------------------------------------*/
    public static function canMarkAttendance(StaffAttendance $record): bool
    {
        $currentUser = auth()->user();

        $targetRoleName = strtolower(str_replace([' ', '-'], '_', $record->staff->role?->name ?? ''));

        // Admin log (manager, chef, waiter) sabko edit kar sakte hain
        if ($currentUser->isRestaurantAdmin() || $currentUser->isBranchAdmin()) {
            return in_array($targetRoleName, ['manager', 'chef', 'waiter']);
        }

        // Manager SIRF chef aur waiter ko edit kar sakta hai
        if ($currentUser->isManager()) {
            return in_array($targetRoleName, ['chef', 'waiter']);
        }

        return false;
    }

    /* -----------------------------------------------------------------
     | TABLE CONFIGURATION
     |-----------------------------------------------------------------*/
    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query, $livewire) {
                $date = $livewire->activeDate ?? now()->toDateString();

                // Active date filter
                $query->whereDate('date', $date);

                $currentUser = auth()->user();

                // 🌟 FIX: Manager ko data na dikhne wali problem yahan theek ki hai 🌟
                if ($currentUser->isManager()) {
                    $query->whereHas('staff.role', function ($q) {
                        // Case-insensitive array takki DB me chhota/bada kaisa bhi naam ho, list me show ho!
                        $q->whereIn('name', ['chef', 'Chef', 'CHEF', 'waiter', 'Waiter', 'WAITER']);
                    });
                }

                return $query;
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
                    ->extraAttributes(['style' => 'min-width: 140px; font-weight: 700;'])
                    // Dropdown lock logic
                    ->disabled(fn(StaffAttendance $record) => !self::canMarkAttendance($record)),

                TextInputColumn::make('overtime_hours')
                    ->label('OT (Hrs)')
                    ->type('number')
                    ->visible(fn() => self::canViewSalary())
                    ->disabled(fn(StaffAttendance $record) => !self::canMarkAttendance($record))
                    ->extraAttributes(['style' => 'width: 80px;']),

                TextInputColumn::make('manual_deduction')
                    ->label('Cut (-) ₹')
                    ->type('number')
                    ->visible(fn() => self::canViewSalary())
                    ->disabled(fn(StaffAttendance $record) => !self::canMarkAttendance($record))
                    ->extraAttributes(['style' => 'width: 90px; color: red; font-weight: bold;']),

                TextInputColumn::make('manual_bonus')
                    ->label('Add (+) ₹')
                    ->type('number')
                    ->visible(fn() => self::canViewSalary())
                    ->disabled(fn(StaffAttendance $record) => !self::canMarkAttendance($record))
                    ->extraAttributes(['style' => 'width: 90px; color: green; font-weight: bold;']),
            ])
            ->defaultSort('staff.name', 'asc')
            ->filters([
                SelectFilter::make('Role')
                    ->relationship('staff.role', 'label')
                    ->label('Filter by Role'),
            ])
            ->actions([
                // Salary Setup button
                Tables\Actions\Action::make('setup_payroll')
                    ->label('Salary Setup')
                    ->icon('heroicon-o-banknotes')
                    ->color('warning')
                    ->button()
                    ->outlined()
                    ->visible(fn() => self::canViewSalary()) // Sirf Admin/Branch Admin dekhega
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
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                if (self::canMarkAttendance($record)) {
                                    $record->update(['status' => 'present']);
                                }
                            });
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('mark_all_absent')
                        ->label('Mark Selected as Absent')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                if (self::canMarkAttendance($record)) {
                                    $record->update(['status' => 'absent']);
                                }
                            });
                        })
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