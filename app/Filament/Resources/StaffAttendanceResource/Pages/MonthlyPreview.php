<?php

namespace App\Filament\Resources\StaffAttendanceResource\Pages;

use App\Filament\Resources\StaffAttendanceResource;
use Filament\Resources\Pages\Page;
use App\Models\User;
use App\Models\StaffAttendance;
use Carbon\Carbon;

class MonthlyPreview extends Page
{
    protected static string $resource = StaffAttendanceResource::class;

    // Naya Blade View link kar rahe hain
    protected static string $view = 'filament.pages.monthly-preview';
    protected static ?string $title = 'Live Matrix Preview';

    public $selectedMonth;
    public $selectedYear;

    public function mount()
    {
        // Default current mahina aur saal
        $this->selectedMonth = now()->month;
        $this->selectedYear = now()->year;
    }

    public function getViewData(): array
    {
        $restaurantId = auth()->user()->restaurant_id;
        $year = $this->selectedYear;
        $month = str_pad($this->selectedMonth, 2, '0', STR_PAD_LEFT);

        $dateObj = Carbon::createFromDate($year, $month, 1);
        $daysInMonth = $dateObj->daysInMonth;

        $staffMembers = User::with('role')
            ->where('restaurant_id', $restaurantId)
            ->where('role_id', '!=', 7)
            ->get();

        $attendances = StaffAttendance::where('restaurant_id', $restaurantId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->groupBy('staff_id');

        return [
            'daysInMonth' => $daysInMonth,
            'staffMembers' => $staffMembers,
            'attendances' => $attendances,
            'year' => $year,
            'month' => $month,
        ];
    }
}