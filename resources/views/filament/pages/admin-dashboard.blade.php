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
            border-radius: 1.25rem !important;
            box-shadow: var(--glass-shadow) !important;
            transition: all 0.3s ease !important;
            position: relative;
            overflow: hidden;
        }

        .glass-panel:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 12px 40px rgba(42, 71, 149, 0.15) !important;
        }

        /* 📊 GRIDS */
        .sa-stats-grid {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        @media (min-width: 640px) {
            .sa-stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 1024px) {
            .sa-stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (min-width: 1280px) {
            .sa-stats-grid {
                grid-template-columns: repeat(6, 1fr);
            }
        }

        /* 📦 STAT BOX INTERNALS */
        .stat-box {
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 160px;
            z-index: 1;
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
        }

        .stat-label {
            font-family: 'Inter', sans-serif;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-sub);
            letter-spacing: 0.05em;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
            flex-shrink: 0;
        }

        .stat-icon svg {
            width: 22px;
            height: 22px;
        }

        .stat-value {
            font-family: 'Poppins', sans-serif;
            font-size: 1.85rem;
            font-weight: 700;
            line-height: 1.1;
            color: var(--text-main);
            margin-bottom: 0.5rem;
        }

        .stat-subtitle {
            font-size: 0.75rem;
            color: var(--text-sub);
            font-weight: 500;
            margin-bottom: 0.75rem;
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
            font-size: 0.72rem;
            font-weight: 600;
            padding: 0.35rem 0.75rem;
            border-radius: 0.5rem;
            text-decoration: none;
            transition: all 0.2s ease;
            text-align: center;
            cursor: pointer;
            border: 1px solid #000000;
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

        /* 🏢 HEADER SECTION */
        .dash-header-panel {
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        @media (min-width: 768px) {
            .dash-header-panel {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }
        }

        /* 🏪 BRANCH CARD GRID */
        .branch-grid {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 1.25rem;
            margin-bottom: 2rem;
        }
        @media (min-width: 768px) {
            .branch-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (min-width: 1200px) {
            .branch-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .branch-card {
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* 📜 TABLES */
        .dash-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
        }
        .dash-table th {
            text-align: left;
            padding: 0.75rem 0.5rem;
            color: var(--brand-blue-primary);
            font-weight: 700;
            text-transform: uppercase;
            border-bottom: 1.5px solid #000000;
            letter-spacing: 0.05em;
        }
        .dash-table td {
            padding: 0.75rem 0.5rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            color: var(--text-main);
        }
        .dark .dash-table td {
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        .dash-table tr:hover {
            background-color: rgba(42, 71, 149, 0.04);
        }
    </style>

    <div class="sa-scope">

        {{-- 🌟 DASHBOARD CONTROL HEADER --}}
        <div class="glass-panel dash-header-panel">
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-2xl font-black uppercase tracking-wide" style="color: var(--brand-blue-primary); font-family: 'Poppins', sans-serif;">
                        {{ $restaurant->name ?? 'Restaurant Administration' }}
                    </h2>
                    @if($isRestaurantAdmin && $showBranchesWidget)
                        <span class="px-2.5 py-1 text-xs font-bold rounded-full border border-black bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300">
                            HQ Master Control ({{ $totalBranches }} Outlets)
                        </span>
                    @elseif($isBranchAdmin)
                        <span class="px-2.5 py-1 text-xs font-bold rounded-full border border-black bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                            🏪 Branch Portal
                        </span>
                    @endif
                </div>
                <p class="text-xs font-medium text-gray-600 dark:text-gray-300 mt-1">
                    Real-time operational metrics, multi-branch revenue tracking, and live order control.
                </p>
            </div>

            {{-- QUICK SHORTCUT ACTIONS --}}
            <div class="flex items-center gap-2 flex-wrap">
               
                <a href="{{ App\Filament\Resources\MenuResource::getUrl('index') }}" class="sa-card-btn blue-solid">
                    + Menu Catalog
                </a>
                <a href="{{ App\Filament\Resources\OrderResource::getUrl('index') }}" class="sa-card-btn blue-outline">
                    Orders Feed
                </a>
                <a href="{{ App\Filament\Resources\RestaurantTableResource::getUrl('index') }}" class="sa-card-btn orange-outline">
                    Tables & QRs
                </a>
            </div>
        </div>

        {{-- 🌟 TOP 6 EXECUTIVE KPI CARDS --}}
        <div class="sa-stats-grid">

            {{-- 1. Today's Revenue (Orange Scheme) --}}
            <div class="glass-panel stat-box">
                <div class="stat-header">
                    <span class="stat-label">Today's Revenue</span>
                    <div class="stat-icon" style="background: rgba(241, 107, 63, 0.15); color: var(--brand-orange-primary); border: 1px solid rgba(241, 107, 63, 0.3);">
                        <x-heroicon-s-currency-rupee />
                    </div>
                </div>
                <div class="stat-value" style="color: var(--brand-orange-primary);">₹{{ number_format($todayRevenue, 0) }}</div>
                <div class="stat-subtitle">This Month: ₹{{ number_format($monthRevenue, 0) }}</div>
                <div class="sa-card-actions">
                    <a href="{{ App\Filament\Resources\OrderResource::getUrl('index') }}" class="sa-card-btn orange-outline">View Orders</a>
                </div>
            </div>

            {{-- 2. Today's Orders (Blue Scheme) --}}
            <div class="glass-panel stat-box">
                <div class="stat-header">
                    <span class="stat-label">Today's Orders</span>
                    <div class="stat-icon" style="background: rgba(42, 71, 149, 0.15); color: var(--brand-blue-primary); border: 1px solid rgba(42, 71, 149, 0.3);">
                        <x-heroicon-s-shopping-bag />
                    </div>
                </div>
                <div class="stat-value" style="color: var(--brand-blue-primary);">{{ $todayOrders }}</div>
                <div class="stat-subtitle">Month Orders: {{ $monthOrders }}</div>
                <div class="sa-card-actions">
                    <a href="{{ App\Filament\Resources\OrderResource::getUrl('index') }}" class="sa-card-btn blue-outline">Live Tracking</a>
                </div>
            </div>

            {{-- 3. All-Time Revenue (Orange Scheme) --}}
            <div class="glass-panel stat-box">
                <div class="stat-header">
                    <span class="stat-label">Total Revenue</span>
                    <div class="stat-icon" style="background: rgba(241, 107, 63, 0.15); color: var(--brand-orange-primary); border: 1px solid rgba(241, 107, 63, 0.3);">
                        <x-heroicon-s-banknotes />
                    </div>
                </div>
                <div class="stat-value" style="color: var(--brand-orange-primary);">₹{{ number_format($totalRevenue, 0) }}</div>
                <div class="stat-subtitle">Total Orders: {{ $totalOrdersCount }}</div>
                <div class="sa-card-actions">
                    <a href="{{ App\Filament\Resources\InvoiceResource::getUrl('index') ?? '#' }}" class="sa-card-btn orange-outline">Invoices</a>
                </div>
            </div>

            {{-- 4. Active Dine-In Tables (Blue Scheme) --}}
            <div class="glass-panel stat-box">
                <div class="stat-header">
                    <span class="stat-label">Active Tables</span>
                    <div class="stat-icon" style="background: rgba(42, 71, 149, 0.15); color: var(--brand-blue-primary); border: 1px solid rgba(42, 71, 149, 0.3);">
                        <x-heroicon-s-table-cells />
                    </div>
                </div>
                <div class="stat-value" style="color: var(--brand-blue-primary);">{{ $activeTablesCount }} <span class="text-sm font-normal text-gray-500">/ {{ $totalTablesCount }}</span></div>
                <div class="stat-subtitle">Currently Occupied</div>
                <div class="sa-card-actions">
                    <a href="{{ App\Filament\Resources\RestaurantTableResource::getUrl('index') }}" class="sa-card-btn blue-outline">Table Layout</a>
                </div>
            </div>

            {{-- 5. Outlets & Branches (Orange Scheme) --}}
            @if($showBranchesWidget)
                <div class="glass-panel stat-box">
                    <div class="stat-header">
                        <span class="stat-label">Outlets</span>
                        <div class="stat-icon" style="background: rgba(241, 107, 63, 0.15); color: var(--brand-orange-primary); border: 1px solid rgba(241, 107, 63, 0.3);">
                            <x-heroicon-s-building-storefront />
                        </div>
                    </div>
                    <div class="stat-value" style="color: var(--brand-orange-primary);">{{ $totalBranches }}</div>
                    <div class="stat-subtitle">Operational Branches</div>
                    <div class="sa-card-actions">
                        <a href="{{ App\Filament\Resources\BranchResource::getUrl('index') }}" class="sa-card-btn orange-outline">Manage</a>
                        <a href="{{ App\Filament\Resources\BranchResource::getUrl('create') }}" class="sa-card-btn orange-solid">+ Add</a>
                    </div>
                </div>
            @else
                <div class="glass-panel stat-box">
                    <div class="stat-header">
                        <span class="stat-label">Staff Members</span>
                        <div class="stat-icon" style="background: rgba(241, 107, 63, 0.15); color: var(--brand-orange-primary); border: 1px solid rgba(241, 107, 63, 0.3);">
                            <x-heroicon-s-users />
                        </div>
                    </div>
                    <div class="stat-value" style="color: var(--brand-orange-primary);">{{ $totalStaff }}</div>
                    <div class="stat-subtitle">Active Team Members</div>
                    <div class="sa-card-actions">
                        <a href="{{ App\Filament\Resources\UserResource::getUrl('index') }}" class="sa-card-btn orange-outline">Manage</a>
                    </div>
                </div>
            @endif

            {{-- 6. Menu Catalog (Blue Scheme) --}}
            <div class="glass-panel stat-box">
                <div class="stat-header">
                    <span class="stat-label">Menu Items</span>
                    <div class="stat-icon" style="background: rgba(42, 71, 149, 0.15); color: var(--brand-blue-primary); border: 1px solid rgba(42, 71, 149, 0.3);">
                        <x-heroicon-s-clipboard-document-list />
                    </div>
                </div>
                <div class="stat-value" style="color: var(--brand-blue-primary);">{{ $totalItems }}</div>
                <div class="stat-subtitle">{{ $totalCategories }} Categories Listed</div>
                <div class="sa-card-actions">
                    <a href="{{ App\Filament\Resources\MenuResource::getUrl('index') }}" class="sa-card-btn blue-outline">Menu Hub</a>
                </div>
            </div>

        </div>

        {{-- 🌟 MULTI-BRANCH OUTLET PERFORMANCE OVERVIEW (For Restaurant Admin) --}}
        @if($showBranchesWidget && count($branchStats) > 0)
            <div class="mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-black text-lg uppercase tracking-wider" style="color: var(--brand-blue-primary); font-family: 'Poppins', sans-serif;">
                        🏢 Branch Outlets Performance Breakdown
                    </h3>
                    
                </div>

                <div class="branch-grid">
                    @foreach($branchStats as $bs)
                        <div class="glass-panel branch-card">
                            <div>
                                <div class="flex justify-between items-start mb-3">
                                    <div class="flex items-center gap-3">
                                        @if($bs['logo_path'])
                                            <img src="{{ Storage::disk('public')->url($bs['logo_path']) }}" class="w-10 h-10 rounded-full object-cover border border-black" />
                                        @else
                                            <div class="w-10 h-10 rounded-full flex items-center justify-center font-black text-white border border-black" style="background: var(--brand-blue-primary);">
                                                {{ strtoupper(substr($bs['name'], 0, 2)) }}
                                            </div>
                                        @endif
                                        <div>
                                            <h4 class="font-black text-sm text-gray-900 dark:text-white uppercase">{{ $bs['name'] }}</h4>
                                            <p class="text-xs text-gray-500">{{ $bs['phone'] }}</p>
                                        </div>
                                    </div>
                                    <span class="px-2 py-0.5 text-xs font-bold rounded-full border border-black {{ $bs['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $bs['is_active'] ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>

                                {{-- INHERITANCE BADGE --}}
                                <div class="mb-3">
                                    @if($bs['inherit_menu'])
                                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 border border-blue-200">
                                            ✓ Shared Restaurant Menu
                                        </span>
                                    @else
                                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 border border-amber-200">
                                            ★ Independent Branch Menu
                                        </span>
                                    @endif
                                </div>

                                {{-- METRICS MINI-GRID --}}
                                <div class="grid grid-cols-2 gap-2 text-xs py-2 border-t border-b border-gray-200 dark:border-gray-800 my-2">
                                    <div>
                                        <span class="text-gray-500 font-medium">Today's Sales:</span>
                                        <div class="font-bold text-sm text-gray-900 dark:text-white">₹{{ number_format($bs['today_revenue'], 0) }}</div>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 font-medium">Month Sales:</span>
                                        <div class="font-bold text-sm text-gray-900 dark:text-white">₹{{ number_format($bs['month_revenue'], 0) }}</div>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 font-medium">Today Orders:</span>
                                        <div class="font-bold text-sm text-gray-900 dark:text-white">{{ $bs['today_orders'] }}</div>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 font-medium">Active Tables:</span>
                                        <div class="font-bold text-sm text-gray-900 dark:text-white">{{ $bs['active_tables'] }} / {{ $bs['tables_count'] }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between mt-3 pt-2">
                                <span class="text-xs text-gray-500">{{ $bs['staff_count'] }} Staff | {{ $bs['items_count'] }} Items</span>
                                <div class="flex gap-1.5">
                                    
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- 🌟 ANALYTICS CHARTS SECTION --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-8">

            {{-- 30-Day Revenue Trend (Area Chart) --}}
            <div class="glass-panel p-6 {{ $showBranchesWidget && count($donutSeries) > 0 ? 'lg:col-span-8' : 'lg:col-span-12' }}">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-black text-sm uppercase tracking-widest" style="color: var(--brand-blue-primary); font-family: 'Poppins', sans-serif;">
                        📈 30-Day Revenue Performance Trend
                    </h3>
                </div>
                <div id="revenue-line-chart"></div>
            </div>

            {{-- Branch Revenue Contribution (Donut Chart) --}}
            @if($showBranchesWidget && count($donutSeries) > 0)
                <div class="glass-panel p-6 lg:col-span-4 flex flex-col justify-between">
                    <div>
                        <h3 class="font-black text-sm uppercase tracking-widest mb-4" style="color: var(--brand-orange-primary); font-family: 'Poppins', sans-serif;">
                            🥧 Revenue Share by Outlet
                        </h3>
                        <div id="branch-donut-chart"></div>
                    </div>
                    <div class="text-center text-xs text-gray-500 mt-2">
                        Total All-Time: ₹{{ number_format($totalRevenue, 0) }}
                    </div>
                </div>
            @endif

        </div>

        {{-- 🌟 OPERATIONAL FEEDS: RECENT ORDERS & TOP PERFORMERS --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-8">

            {{-- Recent Orders Feed (Left) --}}
            <div class="glass-panel p-6 lg:col-span-8">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-black text-sm uppercase tracking-widest" style="color: var(--brand-blue-primary); font-family: 'Poppins', sans-serif;">
                        🛒 Recent Orders Live Feed
                    </h3>
                    <a href="{{ App\Filament\Resources\OrderResource::getUrl('index') }}" class="text-xs font-bold text-blue-600 hover:underline">
                        View Order History →
                    </a>
                </div>

                @if($recentOrders->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="dash-table">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    @if($showBranchesWidget)
                                        <th>Outlet</th>
                                    @endif
                                    <th>Table</th>
                                    <th>Customer</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentOrders as $ro)
                                    <tr>
                                        <td class="font-bold text-blue-700">#{{ $ro->id }}</td>
                                        @if($showBranchesWidget)
                                            <td>
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-orange-100 text-orange-800 border border-orange-300">
                                                    {{ $ro->branch?->name ?? 'Main Restaurant' }}
                                                </span>
                                            </td>
                                        @endif
                                        <td class="font-bold">{{ $ro->table?->table_number ? 'T-' . $ro->table->table_number : 'Takeaway' }}</td>
                                        <td>{{ $ro->customer_name ?? 'Guest' }}</td>
                                        <td class="text-gray-600 dark:text-gray-300 text-xs">{{ $ro->items->count() }} item(s)</td>
                                        <td class="font-black text-green-700">₹{{ number_format($ro->total_amount, 2) }}</td>
                                        <td>
                                            @php
                                                $statusColors = [
                                                    'placed' => 'bg-red-100 text-red-800 border-red-300',
                                                    'preparing' => 'bg-amber-100 text-amber-800 border-amber-300',
                                                    'ready' => 'bg-green-100 text-green-800 border-green-300',
                                                    'served' => 'bg-blue-100 text-blue-800 border-blue-300',
                                                    'completed' => 'bg-indigo-100 text-indigo-800 border-indigo-300',
                                                ];
                                            @endphp
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full border {{ $statusColors[$ro->status] ?? 'bg-gray-100 text-gray-800' }}">
                                                {{ ucfirst($ro->status) }}
                                            </span>
                                        </td>
                                        <td class="text-xs text-gray-500">{{ $ro->created_at->diffForHumans(null, true) }} ago</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-8 text-center text-gray-500 text-sm">
                        No orders recorded yet. Live orders will populate here automatically.
                    </div>
                @endif
            </div>

            {{-- Top Selling Items (Right) --}}
            <div class="glass-panel p-6 lg:col-span-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-black text-sm uppercase tracking-widest" style="color: var(--brand-orange-primary); font-family: 'Poppins', sans-serif;">
                        ⭐ Top Menu Favorites
                    </h3>
                    <a href="{{ App\Filament\Resources\MenuResource::getUrl('index') }}" class="text-xs font-bold text-orange-600 hover:underline">
                        Menu Hub →
                    </a>
                </div>

                @if($topSellingItems->count() > 0)
                    <div class="space-y-3">
                        @foreach($topSellingItems as $index => $item)
                            <div class="flex items-center justify-between p-2.5 rounded-lg border border-black bg-white/40 dark:bg-black/20">
                                <div class="flex items-center gap-3">
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center font-black text-xs text-black border border-black {{ $index === 0 ? 'bg-amber-500' : 'bg-gray-700' }}">
                                        {{ $index + 1 }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-xs text-gray-900 dark:text-white">{{ $item->item_name }}</div>
                                        <div class="text-[10px] text-gray-500">{{ $item->total_qty }} orders served</div>
                                    </div>
                                </div>
                                <div class="font-black text-xs text-blue-800 dark:text-blue-300">
                                    ₹{{ number_format($item->total_sales, 0) }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-center text-gray-500 text-sm">
                        Popular item rankings will display once orders are fulfilled.
                    </div>
                @endif
            </div>

        </div>

    </div>

    {{-- Filament modal mounting point --}}
    <x-filament-actions::modals />

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const isDark = document.documentElement.classList.contains('dark');
            const lineColors = ['#2a4795', '#f16b3f', '#456aba', '#fe9a54', '#10b981', '#6366f1'];

            // 1. REVENUE LINE CHART
            const chartOptions = {
                series: {!! json_encode($chartSeries) !!},
                chart: {
                    type: 'area',
                    height: 320,
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

            const lineEl = document.querySelector("#revenue-line-chart");
            if (lineEl) {
                new ApexCharts(lineEl, chartOptions).render();
            }

            // 2. BRANCH DONUT CHART
            @if($showBranchesWidget && count($donutSeries) > 0)
                const donutSeriesData = {!! json_encode($donutSeries) !!};
                const donutLabelsData = {!! json_encode($donutLabels) !!};

                const donutOptions = {
                    series: donutSeriesData,
                    labels: donutLabelsData,
                    chart: {
                        type: 'donut',
                        height: 280,
                        background: 'transparent',
                        fontFamily: 'inherit'
                    },
                    colors: lineColors,
                    stroke: { width: 1, colors: ['#000000'] },
                    dataLabels: { enabled: true, formatter: function(val) { return val.toFixed(0) + "%"; } },
                    legend: {
                        position: 'bottom',
                        labels: { colors: isDark ? '#f9fafb' : '#0f172a' }
                    },
                    tooltip: {
                        y: { formatter: function(val) { return "₹" + val.toLocaleString(); } }
                    },
                    theme: { mode: isDark ? 'dark' : 'light' }
                };

                const donutEl = document.querySelector("#branch-donut-chart");
                if (donutEl) {
                    new ApexCharts(donutEl, donutOptions).render();
                }
            @endif
        });
    </script>
</x-filament-panels::page>