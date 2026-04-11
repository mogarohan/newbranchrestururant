<x-filament-panels::page>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <style>
        /* --- 🌟 MAKE FILAMENT WRAPPERS TRANSPARENT --- */
        html,
        body,
        .fi-layout,
        .fi-main,
        .fi-page {
            background-color: transparent !important;
            background: transparent !important;
        }

        /* --- 🌟 BACKGROUND IMAGE WITH 0.15 OPACITY --- */
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url("/images/bg.png") !important;
            background-size: cover !important;
            background-position: center !important;
            background-attachment: fixed !important;
            opacity: 0.15 !important;
            z-index: -999 !important;
            pointer-events: none;
        }

        /* 🎨 ULTRA-PREMIUM TRANSPARENT POS UI (ORANGE & BLUE STRICT THEME) */
        .sa-scope {
            /* Text Colors */
            --text-main: #0f172a;
            --text-sub: #475569;

            /* 🟠 Brand Orange Palette */
            --brand-orange-primary: #f16b3f;
            --brand-orange-light: #fe9a54;

            /* 🔵 Brand Blue Palette */
            --brand-blue-primary: #2a4795;
            --brand-blue-light: #456aba;

            /* Glassmorphism Effects */
            --glass-bg: rgba(255, 255, 255, 0.45);
            --glass-shadow: 0 8px 32px rgba(42, 71, 149, 0.08);
            --glass-blur: blur(16px) saturate(140%);
        }

        .dark .sa-scope {
            --text-main: #f8fafc;
            --text-sub: #cbd5e1;
            --glass-bg: rgba(15, 15, 20, 0.7);
        }

        /* 🎨 GLASS PANELS (CARDS & CHART BOX) WITH BLACK BORDER */
        .glass-panel {
            background: var(--glass-bg) !important;
            backdrop-filter: var(--glass-blur) !important;
            -webkit-backdrop-filter: var(--glass-blur) !important;
            border: 1.5px solid #000000 !important;
            /* BLACK BORDER AS REQUESTED */
            border-radius: 1.25rem !important;
            box-shadow: var(--glass-shadow) !important;
            transition: all 0.3s ease !important;
            position: relative;
            overflow: hidden;
        }

        .glass-panel:hover {
            transform: translateY(-4px) !important;
            box-shadow: 0 12px 40px rgba(42, 71, 149, 0.15) !important;
        }

        /* Inner Glow */
        .glass-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            padding: 1px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.8), rgba(255, 255, 255, 0.1));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            pointer-events: none;
        }

        .dark .glass-panel::before {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.15), rgba(255, 255, 255, 0.02));
        }

        /* 📊 GRIDS */
        .sa-stats-grid {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 1.5rem;
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

        /* 📦 STAT BOX INTERNALS */
        .stat-box {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 180px;
            z-index: 1;
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .stat-label {
            font-family: 'Inter', sans-serif;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-sub);
            letter-spacing: 0.05em;
            margin-top: 0.5rem;
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }

        .stat-icon svg {
            width: 24px;
            height: 24px;
        }

        .stat-value {
            font-family: 'Poppins', sans-serif;
            font-size: 2.4rem;
            font-weight: 700;
            line-height: 1;
            color: var(--text-main);
            margin-bottom: 1rem;
        }

        /* BUTTONS */
        .sa-card-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: auto;
            flex-wrap: wrap;
        }

        .sa-card-btn {
            display: inline-block;
            font-family: 'Inter', sans-serif;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.4rem 0.85rem;
            border-radius: 0.5rem;
            text-decoration: none;
            transition: all 0.2s ease;
            text-align: center;
            cursor: pointer;
            border: 1px solid #000000;
            /* Black border on buttons for premium matching */
        }

        .sa-card-btn.orange-solid {
            color: white;
            background: linear-gradient(135deg, var(--brand-orange-primary), var(--brand-orange-light));
        }

        .sa-card-btn.orange-solid:hover {
            opacity: 0.9;
        }

        .sa-card-btn.blue-solid {
            color: white;
            background: linear-gradient(135deg, var(--brand-blue-primary), var(--brand-blue-light));
        }

        .sa-card-btn.blue-solid:hover {
            opacity: 0.9;
        }

        .sa-card-btn.orange-outline {
            color: var(--brand-orange-primary);
            background-color: rgba(241, 107, 63, 0.1);
        }

        .sa-card-btn.orange-outline:hover {
            background-color: var(--brand-orange-primary);
            color: white;
        }

        .sa-card-btn.blue-outline {
            color: var(--brand-blue-primary);
            background-color: rgba(42, 71, 149, 0.1);
        }

        .sa-card-btn.blue-outline:hover {
            background-color: var(--brand-blue-primary);
            color: white;
        }
    </style>

    <div class="sa-scope">

        {{-- TOP WIDGETS --}}
        <div class="sa-stats-grid">

            {{-- 1. Total Staff (Blue Color Scheme) --}}
            <div class="glass-panel stat-box">
                <div class="stat-header">
                    <span class="stat-label">Total Users</span>
                    <div class="stat-icon"
                        style="background: rgba(42, 71, 149, 0.15); color: var(--brand-blue-primary); border: 1px solid rgba(42, 71, 149, 0.3);">
                        <x-heroicon-s-users />
                    </div>
                </div>
                <div class="stat-value" style="color: var(--brand-blue-primary);">{{ $totalStaff }}</div>
                <div class="sa-card-actions">
                    @if($isRestaurantAdmin)
                        <a href="{{ App\Filament\Resources\UserResource::getUrl('create') }}"
                            class="sa-card-btn blue-solid">+ Add</a>
                    @endif
                    <a href="{{ App\Filament\Resources\UserResource::getUrl('index') }}"
                        class="sa-card-btn blue-outline">Manage</a>
                </div>
            </div>

            {{-- 2. Categories (Orange Color Scheme) --}}
            <div class="glass-panel stat-box">
                <div class="stat-header">
                    <span class="stat-label">Categories</span>
                    <div class="stat-icon"
                        style="background: rgba(241, 107, 63, 0.15); color: var(--brand-orange-primary); border: 1px solid rgba(241, 107, 63, 0.3);">
                        <x-heroicon-s-folder />
                    </div>
                </div>
                <div class="stat-value" style="color: var(--brand-orange-primary);">{{ $totalCategories }}</div>
                <div class="sa-card-actions">
                    <a href="{{ App\Filament\Resources\MenuResource::getUrl('index') }}"
                        class="sa-card-btn orange-outline">Manage</a>
                </div>
            </div>

            {{-- 3. Menu Items (Blue Color Scheme) --}}
            <div class="glass-panel stat-box">
                <div class="stat-header">
                    <span class="stat-label">Menu Items</span>
                    <div class="stat-icon"
                        style="background: rgba(42, 71, 149, 0.15); color: var(--brand-blue-primary); border: 1px solid rgba(42, 71, 149, 0.3);">
                        <x-heroicon-s-clipboard-document-list />
                    </div>
                </div>
                <div class="stat-value" style="color: var(--brand-blue-primary);">{{ $totalItems }}</div>
                <div class="sa-card-actions">
                    <a href="{{ App\Filament\Resources\MenuResource::getUrl('index') }}"
                        class="sa-card-btn blue-outline">Manage</a>
                </div>
            </div>

            {{-- 4. Today's Orders (Orange Color Scheme) --}}
            <div class="glass-panel stat-box">
                <div class="stat-header">
                    <span class="stat-label">Today's Orders</span>
                    <div class="stat-icon"
                        style="background: rgba(241, 107, 63, 0.15); color: var(--brand-orange-primary); border: 1px solid rgba(241, 107, 63, 0.3);">
                        <x-heroicon-s-shopping-bag />
                    </div>
                </div>
                <div class="stat-value" style="color: var(--brand-orange-primary);">{{ $todayOrders }}</div>
                <div class="sa-card-actions">
                    <a href="{{ App\Filament\Resources\OrderResource::getUrl('index') ?? '#' }}"
                        class="sa-card-btn orange-outline">View Orders</a>
                </div>
            </div>

            {{-- 5. Total Revenue (Blue Color Scheme) --}}
            <div class="glass-panel stat-box">
                <div class="stat-header">
                    <span class="stat-label">Total Revenue</span>
                    <div class="stat-icon"
                        style="background: rgba(42, 71, 149, 0.15); color: var(--brand-blue-primary); border: 1px solid rgba(42, 71, 149, 0.3);">
                        <x-heroicon-s-currency-rupee />
                    </div>
                </div>
                <div class="stat-value" style="color: var(--brand-blue-primary);">₹{{ number_format($totalRevenue, 0) }}
                </div>
                <div class="sa-card-actions">
                    <a href="#" class="sa-card-btn blue-outline">View Finances</a>
                </div>
            </div>

            {{-- 6. Total Branches (Only for Restaurant Admin) (Orange Color Scheme) --}}
            @if($showBranchesWidget)
                <div class="glass-panel stat-box">
                    <div class="stat-header">
                        <span class="stat-label">Total Branches</span>
                        <div class="stat-icon"
                            style="background: rgba(241, 107, 63, 0.15); color: var(--brand-orange-primary); border: 1px solid rgba(241, 107, 63, 0.3);">
                            <x-heroicon-s-building-storefront />
                        </div>
                    </div>
                    <div class="stat-value" style="color: var(--brand-orange-primary);">{{ $totalBranches }}</div>
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
                <h3 class="font-bold text-base uppercase tracking-widest"
                    style="color: var(--brand-blue-primary); font-family: 'Poppins', sans-serif;">
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

            // Strictly using variations of Brand Blue and Brand Orange
            const lineColors = ['#2a4795', '#f16b3f', '#456aba', '#fe9a54'];

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
                    labels: { style: { colors: isDark ? '#9ca3af' : '#475569' } },
                    axisBorder: { show: false },
                    axisTicks: { show: false }
                },
                yaxis: {
                    labels: {
                        style: { colors: isDark ? '#9ca3af' : '#475569' },
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
                    labels: { colors: isDark ? '#f9fafb' : '#0f172a' }
                },
                theme: { mode: isDark ? 'dark' : 'light' }
            };

            new ApexCharts(document.querySelector("#revenue-line-chart"), chartOptions).render();
        });
    </script>
</x-filament-panels::page>