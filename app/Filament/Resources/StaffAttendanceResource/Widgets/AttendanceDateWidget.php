<?php

namespace App\Filament\Resources\StaffAttendanceResource\Widgets;

use Filament\Widgets\Widget;

class AttendanceDateWidget extends Widget
{
    protected static string $view = 'filament.resources.staff-attendance-resource.widgets.attendance-date-widget';
    protected int | string | array $columnSpan = 'full'; 

    public $activeDate;

    public function mount()
    {
        // Shuru mein aaj ki date
        $this->activeDate = now()->toDateString();
    }

    public function changeDate($date)
    {
        $this->activeDate = $date;
        
        // 🌟 LIVEWIRE 3 EVENT: Page ko batao ki date change hui hai 🌟
        $this->dispatch('date-changed', date: $date);
    }
}