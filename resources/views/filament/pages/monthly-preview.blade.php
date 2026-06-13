<x-filament-panels::page>
    <div class="flex gap-4 mb-4">
        <select wire:model.live="selectedMonth"
            class="p-2 border rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500">
            @for ($i = 1; $i <= 12; $i++)
                <option value="{{ $i }}">{{ date('F', mktime(0, 0, 0, $i, 10)) }}</option>
            @endfor
        </select>
        <select wire:model.live="selectedYear"
            class="p-2 border rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500">
            @for ($i = 0; $i < 5; $i++)
                <option value="{{ date('Y') - $i }}">{{ date('Y') - $i }}</option>
            @endfor
        </select>
    </div>

    <div
        class="overflow-x-auto bg-white dark:bg-gray-900 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-1">
        <table class="w-full text-xs text-left border-collapse min-w-max">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800">
                    <th
                        class="border p-2 min-w-[150px] dark:border-gray-700 font-bold text-gray-700 dark:text-gray-300">
                        Staff Name</th>
                    <th class="border p-2 dark:border-gray-700 font-bold text-gray-700 dark:text-gray-300">Role</th>
                    <th class="border p-2 dark:border-gray-700 font-bold text-gray-700 dark:text-gray-300 text-center">
                        Shift</th>

                    @for ($i = 1; $i <= $daysInMonth; $i++)
                        <th class="border p-1 text-center dark:border-gray-700 text-gray-500 font-bold">{{ $i }}</th>
                    @endfor

                    <th
                        class="border p-2 text-center text-blue-600 font-bold bg-blue-50 dark:bg-blue-900/20 dark:border-gray-700">
                        Payable Days</th>

                    {{-- 🌟 SECURITY FIX: Capital/Small letter ka issue theek kar diya gaya hai 🌟 --}}
                    @php
                        $roleName = strtolower(str_replace([' ', '-'], '_', auth()->user()->role?->name ?? ''));
                        $canSeeSalary = auth()->user()->is_super_admin || in_array($roleName, ['restaurant_admin', 'branch_admin', 'manager']);
                    @endphp

                    @if($canSeeSalary)
                        <th
                            class="border p-2 text-center text-gray-700 bg-gray-100 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-700">
                            Base Salary</th>
                        <th class="border p-2 text-center text-red-600 bg-red-50 dark:bg-red-900/20 dark:border-gray-700">
                            Auto Cut (-)</th>
                        <th
                            class="border p-2 text-center text-green-600 bg-green-50 dark:bg-green-900/20 dark:border-gray-700">
                            OT Hours</th>
                        <th
                            class="border p-2 text-center text-green-600 bg-green-50 dark:bg-green-900/20 dark:border-gray-700">
                            OT Pay (+)</th>
                        <th class="border p-2 text-center text-red-600 bg-red-50 dark:bg-red-900/20 dark:border-gray-700">
                            Manual Cut (-)</th>
                        <th
                            class="border p-2 text-center text-green-600 bg-green-50 dark:bg-green-900/20 dark:border-gray-700">
                            Bonus (+)</th>
                        <th
                            class="border p-2 text-center text-green-800 font-black bg-green-200 dark:bg-green-800 dark:text-white dark:border-gray-700">
                            NET SALARY</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($staffMembers as $staff)
                    @php
                        $presentCount = 0;
                        $absentCount = 0;
                        $halfCount = 0;
                        $totalOT = 0;
                        $totalDeduction = 0;
                        $totalBonus = 0;
                    @endphp

                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="border p-2 font-bold text-gray-800 dark:text-gray-200 dark:border-gray-700">
                            {{ $staff->name }}
                        </td>
                        <td class="border p-2 text-xs text-gray-500 dark:text-gray-400 dark:border-gray-700">
                            {{ $staff->role->label ?? 'N/A' }}
                        </td>
                        <td class="border p-2 text-xs text-center text-gray-500 dark:text-gray-400 dark:border-gray-700">
                            {{ $staff->shift_hours ?? 8 }} Hrs
                        </td>

                        @for ($i = 1; $i <= $daysInMonth; $i++)
                            @php
                                $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $i);
                                $att = $attendances->get($staff->id)?->firstWhere('date', $dateStr);
                                $status = $att ? $att->status : 'pending';

                                if ($status === 'present')
                                    $presentCount++;
                                elseif ($status === 'absent')
                                    $absentCount++;
                                elseif ($status === 'half_day')
                                    $halfCount++;

                                if ($att) {
                                    $totalOT += $att->overtime_hours ?? 0;
                                    $totalDeduction += $att->manual_deduction ?? 0;
                                    $totalBonus += $att->manual_bonus ?? 0;
                                }

                                $text = match ($status) { 'present' => 'P', 'absent' => 'A', 'half_day' => 'HD', default => '-'};
                                $color = match ($status) {
                                    'present' => 'text-green-700 font-bold bg-green-100 dark:bg-green-900/30',
                                    'absent' => 'text-red-700 font-bold bg-red-100 dark:bg-red-900/30',
                                    'half_day' => 'text-yellow-700 font-bold bg-yellow-100 dark:bg-yellow-900/30',
                                    default => 'text-gray-400 bg-gray-50 dark:bg-gray-800'
                                };
                            @endphp
                            <td class="border p-1 text-center text-xs {{ $color }} dark:border-gray-700">{{ $text }}</td>
                        @endfor

                        @php
                            $payableDays = $presentCount + ($halfCount * 0.5);
                            $baseSalary = $staff->monthly_salary ?? 0;
                            $shiftHours = max(1, $staff->shift_hours ?? 8);

                            $perDaySalary = $baseSalary / max(1, $daysInMonth);
                            $perHourSalary = $perDaySalary / $shiftHours;

                            $autoCutDays = $daysInMonth - $payableDays;
                            $autoCutAmount = round($autoCutDays * $perDaySalary, 2);
                            $otAmount = round($totalOT * $perHourSalary, 2);

                            $netSalary = round($baseSalary - $autoCutAmount - $totalDeduction + $otAmount + $totalBonus);
                            if ($netSalary < 0)
                                $netSalary = 0; 
                        @endphp

                        <td
                            class="border p-2 text-center font-black text-blue-600 bg-blue-50/50 dark:bg-blue-900/10 dark:border-gray-700">
                            {{ $payableDays }}
                        </td>

                        @if($canSeeSalary)
                            <td
                                class="border p-2 text-center text-gray-700 bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700">
                                ₹{{ number_format($baseSalary) }}</td>
                            <td
                                class="border p-2 text-center text-red-600 bg-red-50/50 dark:bg-red-900/10 dark:border-gray-700">
                                ₹{{ number_format($autoCutAmount) }}</td>
                            <td
                                class="border p-2 text-center text-green-600 bg-green-50/50 dark:bg-green-900/10 dark:border-gray-700">
                                {{ $totalOT }} hrs
                            </td>
                            <td
                                class="border p-2 text-center text-green-600 bg-green-50/50 dark:bg-green-900/10 dark:border-gray-700">
                                ₹{{ number_format($otAmount) }}</td>
                            <td
                                class="border p-2 text-center text-red-600 bg-red-50/50 dark:bg-red-900/10 dark:border-gray-700">
                                ₹{{ number_format($totalDeduction) }}</td>
                            <td
                                class="border p-2 text-center text-green-600 bg-green-50/50 dark:bg-green-900/10 dark:border-gray-700">
                                ₹{{ number_format($totalBonus) }}</td>
                            <td
                                class="border p-2 text-center font-black text-green-800 bg-green-200 dark:bg-green-800 dark:text-white dark:border-gray-700">
                                ₹{{ number_format($netSalary) }}</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-filament-panels::page>