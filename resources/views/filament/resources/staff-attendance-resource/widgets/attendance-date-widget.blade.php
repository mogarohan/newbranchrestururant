@php
    // Ab direct $activeDate variable milega
    $current = \Carbon\Carbon::parse($activeDate);
    $previous = $current->copy()->subDay();
    $next = $current->copy()->addDay();
@endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-3">
    <button wire:click="changeDate('{{ $previous->toDateString() }}')" type="button"
        class="flex flex-col items-center justify-center p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm hover:border-primary-500 transition group w-full text-center">
        <span class="text-xs font-bold text-gray-400 dark:text-gray-500">← PREVIOUS DAY</span>
        <span class="text-base font-black text-gray-700 dark:text-gray-300 mt-1">{{ $previous->format('d M Y') }}</span>
    </button>

    <button wire:click="changeDate('{{ now()->toDateString() }}')" type="button"
        class="flex flex-col items-center justify-center p-4 bg-primary-50 dark:bg-primary-950/20 border-2 border-primary-500 rounded-xl shadow-sm text-center w-full group">
        <span class="text-xs font-black text-primary-600 dark:text-primary-400">📅 SELECTED SHEET</span>
        <span
            class="text-xl font-black text-primary-700 dark:text-primary-200 mt-1">{{ $current->format('d M Y') }}</span>
    </button>

    <button wire:click="changeDate('{{ $next->toDateString() }}')" type="button"
        class="flex flex-col items-center justify-center p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm hover:border-primary-500 transition group w-full text-center">
        <span class="text-xs font-bold text-gray-400 dark:text-gray-500">NEXT DAY →</span>
        <span class="text-base font-black text-gray-700 dark:text-gray-300 mt-1">{{ $next->format('d M Y') }}</span>
    </button>
</div>