<x-filament-panels::page>
    {{-- ApexCharts for Professional Visuals --}}
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <style>
        /* 🎨 ULTRA-PREMIUM TRANSPARENT POS UI */
        .sa-scope {
            /* Light Theme - Glass Look */
            --surface-card: rgba(255, 255, 255, 0.45);
            --border-light: rgba(156, 163, 175, 0.2);
            --text-main: #111827;
            --text-sub: #6b7280;
            --brand-orange: #f97316;
            --brand-blue: #3b82f6;
            --brand-green: #10b981;
            --brand-red: #ef4444;
        }

        .dark .sa-scope {
            /* Dark Theme - Premium Glass Look */
            --surface-card: rgba(31, 41, 55, 0.5);
            --border-light: rgba(75, 85, 99, 0.3);
            --text-main: #f9fafb;
            --text-sub: #9ca3af;
        }

        /* Glass Effect Base */
        .glass-panel {
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            background-color: var(--surface-card) !important;
            border: 1px solid var(--border-light) !important;
            border-radius: 20px !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            transition: all 0.3s ease;
        }

        .glass-panel:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
        }

        /* Stats Cards Styling (FIXED: Horizontal 4 Columns) */
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
                grid-template-columns: repeat(4, 1fr);
            }
        }

        .stat-box {
            padding: 1.5rem;
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
            margin-bottom: 1rem;
        }

        .stat-label {
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            color: var(--text-sub);
            letter-spacing: 0.1em;
        }

        .stat-icon {
            padding: 0.5rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-value {
            font-size: 2.25rem;
            font-weight: 900;
            line-height: 1;
            color: var(--text-main);
            margin-bottom: 0.5rem;
        }

        .stat-desc {
            font-size: 0.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
        }
    </style>

    <div class="sa-scope">

        {{-- 2. TOP 4 WIDGETS (Strictly 1 line on Desktop) --}}
        <div class="sa-stats-grid">
            {{-- Total Staff (From users table) --}}
            <div class="glass-panel stat-box" style="border-bottom: 4px solid var(--brand-orange) !important;">
                <div class="stat-header">
                    <span class="stat-label">Total Users</span>
                    <div class="stat-icon" style="background: rgba(249, 115, 22, 0.1); color: var(--brand-orange);">
                        <x-heroicon-s-users class="w-5 h-5" />
                    </div>
                </div>
                <div class="stat-value">{{ $totalStaff }}</div>
                <div class="stat-desc text-emerald-500">Active Team</div>
            </div>

            {{-- Menu Items (From menu_items table) --}}
            <div class="glass-panel stat-box" style="border-bottom: 4px solid var(--brand-blue) !important;">
                <div class="stat-header">
                    <span class="stat-label">Menu Items</span>
                    <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: var(--brand-blue);">
                        <x-heroicon-s-clipboard-document-list class="w-5 h-5" />
                    </div>
                </div>
                <div class="stat-value">{{ $totalItems }}</div>
                <div class="stat-desc text-blue-500">Total Dishes</div>
            </div>

            {{-- Today's Orders (From orders table) --}}
            <div class="glass-panel stat-box" style="border-bottom: 4px solid var(--brand-red) !important;">
                <div class="stat-header">
                    <span class="stat-label">Today's Orders</span>
                    <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: var(--brand-red);">
                        <x-heroicon-s-shopping-bag class="w-5 h-5" />
                    </div>
                </div>
                <div class="stat-value">{{ $todayOrders }}</div>
                <div class="text-orange-500 text-[0.65rem] font-black uppercase tracking-widest mt-2">Trending High 🔥
                </div>
            </div>

            {{-- Total Revenue (From payments or orders table) --}}
            <div class="glass-panel stat-box" style="border-bottom: 4px solid var(--brand-green) !important;">
                <div class="stat-header">
                    <span class="stat-label">Total Revenue</span>
                    <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--brand-green);">
                        <x-heroicon-s-currency-dollar class="w-5 h-5" />
                    </div>
                </div>
                <div class="stat-value">₹{{ number_format($totalRevenue, 0) }}</div>
                <div class="stat-desc text-emerald-500">Lifetime Income</div>
            </div>

            {{-- 5TH WIDGET: Total Branches (Clickable, Only for Restaurant Admin) --}}
            @if($showBranchesWidget)
                <a href="{{ App\Filament\Resources\BranchResource::getUrl('index') }}"
                    class="glass-panel stat-box block cursor-pointer transition-transform hover:scale-105"
                    style="border-bottom: 4px solid #8b5cf6 !important; text-decoration: none;">
                    <div class="stat-header">
                        <span class="stat-label">Total Branches</span>
                        <div class="stat-icon" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                            <x-heroicon-s-building-storefront class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="stat-value">{{ $totalBranches }}</div>
                    <div class="stat-desc text-purple-500 font-bold mt-1">Manage Locations &rarr;</div>
                </a>
            @endif

        </div>

        {{-- 3. MIDDLE SECTION (CHARTS) --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <div class="lg:col-span-1 glass-panel p-6">
                <h3 class="font-black text-sm uppercase tracking-widest mb-6" style="color: var(--text-main);">Top
                    Categories</h3>
                <div id="donut-chart"></div>
            </div>

            <div class="lg:col-span-2 glass-panel p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-black text-sm uppercase tracking-widest" style="color: var(--text-main);">Orders
                        Volume Analysis</h3>
                </div>
                <div id="volume-chart"></div>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Theme check dynamically for ApexCharts
            const isDark = document.documentElement.classList.contains('dark');

            // Donut Chart (Dynamic Data from Categories & Menu Items)
            new ApexCharts(document.querySelector("#donut-chart"), {
                series: {!! json_encode($categoryCounts) !!},
                chart: { type: 'donut', height: 320, background: 'transparent' },
                labels: {!! json_encode($categoryNames) !!},
                colors: ['#f97316', '#3b82f6', '#10b981', '#64748b', '#ef4444', '#8b5cf6'],
                legend: { position: 'bottom', labels: { colors: isDark ? '#9ca3af' : '#4b5563' } },
                plotOptions: { pie: { donut: { size: '70%' } } },
                stroke: { show: false },
                theme: { mode: isDark ? 'dark' : 'light' }
            }).render();

            // Generate 24-hour labels (12 AM, 1 AM ... 11 PM) for the X-axis
            const hourLabels = Array.from({ length: 24 }, (_, i) => {
                const ampm = i >= 12 ? 'PM' : 'AM';
                const hour = i % 12 || 12;
                return `${hour} ${ampm}`;
            });

            // Bar Chart (Dynamic Data from Orders Volume)
            new ApexCharts(document.querySelector("#volume-chart"), {
                series: [{ name: 'Orders', data: {!! json_encode($hourlyOrders) !!} }],
                chart: { type: 'bar', height: 280, toolbar: { show: false }, background: 'transparent' },
                plotOptions: { bar: { borderRadius: 6, columnWidth: '45%' } },
                colors: ['#3b82f6'],
                xaxis: {
                    categories: hourLabels,
                    labels: { style: { colors: isDark ? '#9ca3af' : '#6b7280' } }
                },
                yaxis: { labels: { style: { colors: isDark ? '#9ca3af' : '#6b7280' } } },
                grid: { borderColor: isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)' },
                theme: { mode: isDark ? 'dark' : 'light' }
            }).render();
        });
    </script>
</x-filament-panels::page>