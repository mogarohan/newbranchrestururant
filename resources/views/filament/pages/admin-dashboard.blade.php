<x-filament-panels::page>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <style>
        /* 🎨 ULTRA-PREMIUM TRANSPARENT POS UI (ORANGE & BLUE STRICT THEME) */
        .sa-scope {
            --surface-card: rgba(255, 255, 255, 0.45);
            --border-light: rgba(156, 163, 175, 0.2);
            --text-main: #111827;
            --text-sub: #6b7280;
            --brand-orange: #ea580c;
            --brand-blue: #3b82f6;
        }

        .dark .sa-scope {
            --surface-card: rgba(31, 41, 55, 0.5);
            --border-light: rgba(75, 85, 99, 0.3);
            --text-main: #f9fafb;
            --text-sub: #9ca3af;
        }

        .glass-panel {
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            background-color: var(--surface-card) !important;
            border: 1px solid var(--border-light) !important;
            border-radius: 16px !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }

        .glass-panel:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }

        .sa-stats-grid {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        @media (min-width: 640px) {
            .sa-stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 1024px) {
            .sa-stats-grid {
                grid-template-columns: repeat(5, 1fr);
            }
        }

        .stat-box {
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
        }

        .stat-label {
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            color: var(--text-sub);
            letter-spacing: 0.1em;
        }

        .stat-icon {
            padding: 0.4rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 900;
            line-height: 1;
            color: var(--text-main);
            margin-bottom: 1rem;
        }

        .sa-card-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .sa-card-btn {
            display: inline-block;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.35rem 0.75rem;
            border-radius: 0.375rem;
            text-decoration: none;
            transition: background-color 0.2s ease;
            text-align: center;
            cursor: pointer;
            border: none;
            /* In case it's a button element */
        }

        .sa-card-btn.orange-solid {
            color: white;
            background-color: var(--brand-orange);
            border: 1px solid var(--brand-orange);
        }

        .sa-card-btn.orange-solid:hover {
            background-color: #c2410c;
            border-color: #c2410c;
        }

        .sa-card-btn.blue-solid {
            color: white;
            background-color: var(--brand-blue);
            border: 1px solid var(--brand-blue);
        }

        .sa-card-btn.blue-solid:hover {
            background-color: #2563eb;
            border-color: #2563eb;
        }

        .sa-card-btn.orange-outline {
            color: var(--brand-orange);
            background-color: rgba(234, 88, 12, 0.1);
            border: 1px solid rgba(234, 88, 12, 0.2);
        }

        .sa-card-btn.orange-outline:hover {
            background-color: rgba(234, 88, 12, 0.2);
        }

        .sa-card-btn.blue-outline {
            color: var(--brand-blue);
            background-color: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .sa-card-btn.blue-outline:hover {
            background-color: rgba(59, 130, 246, 0.2);
        }
    </style>

    <div class="sa-scope">

        {{-- TOP WIDGETS --}}
        <div class="sa-stats-grid">

            {{-- 1. Total Staff (Blue) --}}
            <div class="glass-panel stat-box" style="border-bottom: 4px solid var(--brand-blue) !important;">
                <div class="stat-header">
                    <span class="stat-label">Total Users</span>
                    <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: var(--brand-blue);">
                        <x-heroicon-s-users class="w-5 h-5" />
                    </div>
                </div>
                <div class="stat-value">{{ $totalStaff }}</div>
                <div class="sa-card-actions">
                    @if($isRestaurantAdmin)
                        <a href="{{ App\Filament\Resources\UserResource::getUrl('create') }}"
                            class="sa-card-btn blue-solid">+ Add</a>
                    @endif
                    <a href="{{ App\Filament\Resources\UserResource::getUrl('index') }}"
                        class="sa-card-btn blue-outline">Manage</a>
                </div>
            </div>

            {{-- 2. Categories (Orange) --}}
            <div class="glass-panel stat-box" style="border-bottom: 4px solid var(--brand-orange) !important;">
                <div class="stat-header">
                    <span class="stat-label">Categories</span>
                    <div class="stat-icon" style="background: rgba(234, 88, 12, 0.1); color: var(--brand-orange);">
                        <x-heroicon-s-folder class="w-5 h-5" />
                    </div>
                </div>
                <div class="stat-value">{{ $totalCategories }}</div>
                <div class="sa-card-actions">
                    {{-- 👇 LIVEWIRE TRIGGER FOR SLIDE-OVER 👇 --}}
                    @if($isRestaurantAdmin)
                        <button wire:click="mountAction('addCategory')" type="button" class="sa-card-btn orange-solid">+
                            Add</button>
                    @endif
                    {{-- The categories manage link still goes to menus because of the unified page --}}
                    <a href="{{ App\Filament\Resources\MenuResource::getUrl('index') }}"
                        class="sa-card-btn orange-outline">Manage</a>
                </div>
            </div>

            {{-- 3. Menu Items (Blue) --}}
            <div class="glass-panel stat-box" style="border-bottom: 4px solid var(--brand-blue) !important;">
                <div class="stat-header">
                    <span class="stat-label">Menu Items</span>
                    <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: var(--brand-blue);">
                        <x-heroicon-s-clipboard-document-list class="w-5 h-5" />
                    </div>
                </div>
                <div class="stat-value">{{ $totalItems }}</div>
                <div class="sa-card-actions">
                    {{-- 👇 LIVEWIRE TRIGGER FOR SLIDE-OVER 👇 --}}
                    @if($isRestaurantAdmin)
                        <button wire:click="mountAction('addItem')" type="button" class="sa-card-btn blue-solid">+
                            Add</button>
                    @endif
                    <a href="{{ App\Filament\Resources\MenuResource::getUrl('index') }}"
                        class="sa-card-btn blue-outline">Manage</a>
                </div>
            </div>

            {{-- 4. Today's Orders (Orange) --}}
            <div class="glass-panel stat-box" style="border-bottom: 4px solid var(--brand-orange) !important;">
                <div class="stat-header">
                    <span class="stat-label">Today's Orders</span>
                    <div class="stat-icon" style="background: rgba(234, 88, 12, 0.1); color: var(--brand-orange);">
                        <x-heroicon-s-shopping-bag class="w-5 h-5" />
                    </div>
                </div>
                <div class="stat-value">{{ $todayOrders }}</div>
                <div class="sa-card-actions">
                    <a href="{{ App\Filament\Resources\OrderResource::getUrl('index') ?? '#' }}"
                        class="sa-card-btn orange-outline">View Orders</a>
                </div>
            </div>

            {{-- 5. Total Revenue (Blue) --}}
            <div class="glass-panel stat-box" style="border-bottom: 4px solid var(--brand-blue) !important;">
                <div class="stat-header">
                    <span class="stat-label">Total Revenue</span>
                    <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: var(--brand-blue);">
                        <x-heroicon-s-currency-rupee class="w-5 h-5" />
                    </div>
                </div>
                <div class="stat-value">₹{{ number_format($totalRevenue, 0) }}</div>
                <div class="sa-card-actions">
                    <a href="#" class="sa-card-btn blue-outline">View Finances</a>
                </div>
            </div>

            {{-- 6. Total Branches (Only for Restaurant Admin) --}}
            @if($showBranchesWidget)
                <div class="glass-panel stat-box" style="border-bottom: 4px solid var(--brand-orange) !important;">
                    <div class="stat-header">
                        <span class="stat-label">Total Branches</span>
                        <div class="stat-icon" style="background: rgba(234, 88, 12, 0.1); color: var(--brand-orange);">
                            <x-heroicon-s-building-storefront class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="stat-value">{{ $totalBranches }}</div>
                    <div class="sa-card-actions">
                        <a href="{{ App\Filament\Resources\BranchResource::getUrl('create') }}"
                            class="sa-card-btn orange-solid">+ Add</a>
                        <a href="{{ App\Filament\Resources\BranchResource::getUrl('index') }}"
                            class="sa-card-btn orange-outline">Manage</a>
                    </div>
                </div>
            @endif

        </div>

        {{-- REVENUE LINE CHART --}}
        <div class="glass-panel p-6 mb-8">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-black text-sm uppercase tracking-widest" style="color: var(--text-main);">
                    Revenue Trend (Last 30 Days)
                </h3>
            </div>
            <div id="revenue-line-chart"></div>
        </div>

    </div>

    {{-- This invisible div is necessary for Filament to mount and render the slide-over forms! --}}
    <x-filament-actions::modals />

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const isDark = document.documentElement.classList.contains('dark');

            // Strictly using variations of Blue and Orange for the lines
            const lineColors = ['#3b82f6', '#ea580c', '#60a5fa', '#f97316', '#1d4ed8', '#c2410c'];

            const chartOptions = {
                series: {!! json_encode($chartSeries) !!},
                chart: {
                    type: 'area',
                    height: 350,
                    toolbar: { show: false },
                    background: 'transparent',
                    fontFamily: 'inherit'
                },
                colors: lineColors,
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.4,
                        opacityTo: 0.05,
                        stops: [0, 100]
                    }
                },
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 3 },
                xaxis: {
                    categories: {!! json_encode($chartDates) !!},
                    labels: { style: { colors: isDark ? '#9ca3af' : '#6b7280' } },
                    axisBorder: { show: false },
                    axisTicks: { show: false }
                },
                yaxis: {
                    labels: {
                        style: { colors: isDark ? '#9ca3af' : '#6b7280' },
                        formatter: function (value) {
                            return "₹" + value;
                        }
                    }
                },
                grid: {
                    borderColor: isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)',
                    strokeDashArray: 4,
                    yaxis: { lines: { show: true } },
                    xaxis: { lines: { show: false } }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right',
                    labels: { colors: isDark ? '#f9fafb' : '#111827' }
                },
                theme: { mode: isDark ? 'dark' : 'light' }
            };

            new ApexCharts(document.querySelector("#revenue-line-chart"), chartOptions).render();
        });
    </script>
</x-filament-panels::page>