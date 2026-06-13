<?php

namespace App\Filament\Resources\StaffAttendanceResource\Pages;

use App\Filament\Resources\StaffAttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\User;
use App\Models\StaffAttendance;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Livewire\Attributes\On;

// 🌟 MULTI-SHEET EXCEL KE LIYE IMPORTS 🌟
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;

class ListStaffAttendances extends ListRecords
{
    protected static string $resource = StaffAttendanceResource::class;

    public $activeDate;

    public function mount(): void
    {
        parent::mount();

        if (!$this->activeDate) {
            $this->activeDate = now()->toDateString();
        }

        $manager = auth()->user();
        $restaurantId = $manager->restaurant_id;

        $startOfMonth = Carbon::today()->startOfMonth();
        $endOfMonth = Carbon::today()->endOfMonth();
        $now = now();

        $staffMembers = User::where('restaurant_id', $restaurantId)->where('role_id', '!=', 7)->get();
        $insertData = [];

        foreach ($staffMembers as $staff) {
            for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
                $insertData[] = [
                    'staff_id' => $staff->id,
                    'restaurant_id' => $restaurantId,
                    'role_id' => $staff->role_id,
                    'date' => $date->toDateString(),
                    'status' => 'pending',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        StaffAttendance::insertOrIgnore($insertData);
    }

    #[On('date-changed')]
    public function updateActiveDate($date)
    {
        $this->activeDate = $date;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            StaffAttendanceResource\Widgets\AttendanceDateWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_preview')
                ->label('Live Matrix Preview')
                ->icon('heroicon-o-eye')
                ->color('warning')
                ->url(fn(): string => StaffAttendanceResource::getUrl('preview')),

            // 🌟 2-SHEET EXCEL DOWNLOAD ACTION 🌟
            Actions\Action::make('download_monthly_sheet')
                ->label('Download Excel Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->form([
                    Select::make('month')
                        ->label('Select Month')
                        ->options([
                            '1' => 'January',
                            '2' => 'February',
                            '3' => 'March',
                            '4' => 'April',
                            '5' => 'May',
                            '6' => 'June',
                            '7' => 'July',
                            '8' => 'August',
                            '9' => 'September',
                            '10' => 'October',
                            '11' => 'November',
                            '12' => 'December',
                        ])
                        ->required()
                        ->default(now()->month),
                    Select::make('year')
                        ->label('Select Year')
                        ->options(function () {
                            $years = [];
                            $current = now()->year;
                            for ($i = 0; $i < 5; $i++)
                                $years[$current - $i] = $current - $i;
                            return $years;
                        })
                        ->required()
                        ->default(now()->year),
                ])
                ->action(function (array $data) {
                    $year = $data['year'];
                    $month = str_pad($data['month'], 2, '0', STR_PAD_LEFT);

                    $dateObj = Carbon::createFromDate($year, $month, 1);
                    $daysInMonth = $dateObj->daysInMonth;
                    $monthName = $dateObj->format('F');

                    $user = auth()->user();
                    $restaurantId = $user->restaurant_id;

                    $canSeeSalary = $user->isSuperAdmin() || $user->isRestaurantAdmin() || $user->isBranchAdmin() || $user->isManager();

                    $staffMembers = User::with('role')
                        ->where('restaurant_id', $restaurantId)
                        ->where('role_id', '!=', 7)
                        ->get();

                    $attendances = StaffAttendance::where('restaurant_id', $restaurantId)
                        ->whereYear('date', $year)
                        ->whereMonth('date', $month)
                        ->get()
                        ->groupBy('staff_id');

                    $statusMap = ['present' => 'P', 'absent' => 'A', 'half_day' => 'HD', 'pending' => '-'];

                    // Asli Excel File ka naam
                    $fileName = "Payroll_Report_{$monthName}_{$year}.xlsx";

                    // 🌟 TRICK: System ke Temp folder mein file create karna jahan kabhi permission error nahi aata 🌟
                    $tempFilePath = tempnam(sys_get_temp_dir(), 'excel_') . '.xlsx';

                    $writer = new Writer();
                    $writer->openToFile($tempFilePath);

                    /* ==========================================
                       📝 SHEET 1: ATTENDANCE (1 to 31 Days)
                    ========================================== */
                    $sheet1 = $writer->getCurrentSheet();
                    $sheet1->setName('Attendance');

                    $attHeaders = ['Staff Name', 'Role', 'Duty Hours'];
                    for ($i = 1; $i <= $daysInMonth; $i++) {
                        $attHeaders[] = $i;
                    }
                    $writer->addRow(Row::fromValues($attHeaders));

                    $payrollRows = [];

                    foreach ($staffMembers as $staff) {
                        $shiftHours = max(1, $staff->shift_hours ?? 8);
                        $attRow = [$staff->name, $staff->role?->label ?? 'N/A', $shiftHours . ' Hrs'];

                        $staffAtt = $attendances->get($staff->id, collect());

                        $present = 0;
                        $absent = 0;
                        $half = 0;
                        $totalOT = 0;
                        $totalDed = 0;
                        $totalBonus = 0;

                        for ($i = 1; $i <= $daysInMonth; $i++) {
                            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $i);
                            $attRecord = $staffAtt->first(fn($item) => $item->date === $dateStr);

                            if ($attRecord) {
                                if ($attRecord->status === 'present')
                                    $present++;
                                elseif ($attRecord->status === 'absent')
                                    $absent++;
                                elseif ($attRecord->status === 'half_day')
                                    $half++;

                                $totalOT += $attRecord->overtime_hours ?? 0;
                                $totalDed += $attRecord->manual_deduction ?? 0;
                                $totalBonus += $attRecord->manual_bonus ?? 0;
                            }
                            $attRow[] = $attRecord ? ($statusMap[$attRecord->status] ?? '-') : '-';
                        }

                        $writer->addRow(Row::fromValues($attRow));

                        /* --- Salary Calculations (For Sheet 2) --- */
                        $payableDays = $present + ($half * 0.5);
                        $baseSalary = $staff->monthly_salary ?? 0;

                        $perDaySalary = $baseSalary / max(1, $daysInMonth);
                        $perHourSalary = $perDaySalary / $shiftHours;

                        $autoCutDays = $daysInMonth - $payableDays;
                        $autoCutAmount = round($autoCutDays * $perDaySalary, 2);
                        $otAmount = round($totalOT * $perHourSalary, 2);

                        $netSalary = round($baseSalary - $autoCutAmount - $totalDed + $otAmount + $totalBonus);
                        if ($netSalary < 0)
                            $netSalary = 0;

                        $payrollRows[] = [
                            $staff->name,
                            $staff->role?->label ?? 'N/A',
                            $payableDays,
                            '₹ ' . number_format($baseSalary),
                            '₹ ' . number_format($autoCutAmount),
                            $totalOT . ' hrs',
                            '₹ ' . number_format($otAmount),
                            '₹ ' . number_format($totalDed),
                            '₹ ' . number_format($totalBonus),
                            '₹ ' . number_format($netSalary)
                        ];
                    }

                    /* ==========================================
                       💰 SHEET 2: PAYROLL CALCULATION
                    ========================================== */
                    if ($canSeeSalary) {
                        $writer->addNewSheetAndMakeItCurrent();
                        $sheet2 = $writer->getCurrentSheet();
                        $sheet2->setName('Payroll'); // Dusri sheet ka naam
        
                        $payrollHeaders = [
                            'Staff Name',
                            'Role',
                            'Payable Days',
                            'Base Salary',
                            'Auto Cut (-)',
                            'OT Hours',
                            'OT Pay (+)',
                            'Manual Cut (-)',
                            'Bonus (+)',
                            'NET SALARY'
                        ];
                        $writer->addRow(Row::fromValues($payrollHeaders));

                        foreach ($payrollRows as $pRow) {
                            $writer->addRow(Row::fromValues($pRow));
                        }
                    }

                    $writer->close(); // File ready ho gayi
        
                    // 🌟 DIRECT DOWNLOAD AUR AUTO DELETE 🌟
                    return response()->download($tempFilePath, $fileName)->deleteFileAfterSend(true);
                }),
        ];
    }
}