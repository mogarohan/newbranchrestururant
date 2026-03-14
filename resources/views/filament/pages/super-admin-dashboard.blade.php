<x-filament-panels::page>
    <style>
        /* 🎨 SUPER ADMIN CUSTOM UI (TRANSPARENT & THEME-AWARE) */

        /* ☀️ LIGHT THEME VARIABLES */
        .sa-scope {
            --bg-transparent: transparent;
            --border-color: #e5e7eb;
            --text-main: #111827;
            --text-sub: #6b7280;
            --bg-badge: #f3f4f6;
            --brand-orange: #ea580c;
            --brand-blue: #3b82f6;
            --brand-green: #10b981;
            --brand-red: #e11d48;
            --brand-purple: #8b5cf6;
        }

        /* 🌙 DARK THEME VARIABLES */
        .dark .sa-scope {
            --bg-transparent: transparent;
            --border-color: #374151;
            --text-main: #ffffff;
            --text-sub: #9ca3af;
            --bg-badge: #1f2937;
        }

        .sa-container {
            font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
            color: var(--text-main);
        }

        /* Typography */
        .sa-title {
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -0.02em;
            margin-bottom: 0.25rem;
            color: var(--text-main);
        }

        .sa-subtitle {
            font-size: 0.875rem;
            color: var(--text-sub);
        }

        /* Buttons & Badges */
        .sa-date-badge {
            background-color: var(--bg-badge);
            color: var(--text-sub);
            padding: 0.4rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            border: 1px solid var(--border-color);
        }

        .sa-btn-primary {
            background-color: var(--brand-orange);
            color: white;
            padding: 0.5rem 1.25rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: background-color 0.2s;
            text-decoration: none;
        }

        .sa-btn-primary:hover {
            background-color: #c2410c;
        }

        /* Stats Grid - Updated for 5 Columns */
        .sa-stats-grid {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 1.5rem;
            margin-top: 2rem;
            margin-bottom: 2.5rem;
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
                grid-template-columns: repeat(5, 1fr);
            }
        }

        /* Stat Cards - Fully Transparent */
        .sa-stat-card {
            background: var(--bg-transparent);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            box-shadow: none;
        }

        .sa-stat-label {
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: block;
            margin-bottom: 1rem;
        }

        .sa-stat-value {
            font-size: 2.25rem;
            font-weight: 800;
            color: var(--text-main);
            line-height: 1;
            display: block;
            margin-bottom: 0.5rem;
        }

        .sa-stat-desc {
            font-size: 0.75rem;
            font-weight: 500;
        }

        .sa-stat-icon {
            position: absolute;
            top: 1.25rem;
            right: 1.25rem;
            padding: 0.5rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Table Layout - Fully Transparent */
        .sa-table-container {
            background-color: var(--bg-transparent);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: none;
        }

        .sa-table-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sa-search-input {
            background-color: var(--bg-transparent);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 0.5rem 1rem 0.5rem 2.5rem;
            font-size: 0.875rem;
            color: var(--text-main);
            width: 250px;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="%239ca3af"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>');
            background-repeat: no-repeat;
            background-position: 0.75rem center;
            background-size: 1rem;
        }

        .sa-search-input:focus {
            outline: none;
            border-color: var(--brand-orange);
        }

        .sa-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .sa-table th {
            padding: 1rem 1.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-sub);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--border-color);
            background-color: var(--bg-transparent);
        }

        .sa-table td {
            padding: 1.25rem 1.5rem;
            font-size: 0.875rem;
            color: var(--text-main);
            border-bottom: 1px solid var(--border-color);
        }

        .sa-table tr:last-child td {
            border-bottom: none;
        }

        /* Progress Bar */
        .sa-progress-bg {
            background-color: var(--bg-badge);
            height: 6px;
            width: 80px;
            border-radius: 9999px;
            overflow: hidden;
            display: inline-block;
            vertical-align: middle;
            margin-left: 0.5rem;
        }

        .sa-progress-fill {
            height: 100%;
            border-radius: 9999px;
            background-color: var(--brand-orange);
        }

        .sa-progress-fill.danger {
            background-color: var(--brand-red);
        }

        /* Table Footer Pagination */
        .sa-pagination {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.875rem;
            color: var(--text-sub);
            background-color: var(--bg-transparent);
        }

        .sa-page-btn {
            padding: 0.25rem 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 0.375rem;
            background: var(--bg-transparent);
            cursor: pointer;
            color: var(--text-main);
            font-weight: 500;
            margin-left: 0.25rem;
            transition: all 0.2s;
        }

        .sa-page-btn.active {
            background-color: rgba(234, 88, 12, 0.1);
            border-color: var(--brand-orange);
            color: var(--brand-orange);
        }

        .sa-page-btn:hover:not(.active) {
            border-color: var(--text-sub);
        }
    </style>

    <div class="sa-scope sa-container">

        {{-- HEADER SECTION --}}
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <h1 class="sa-title">Super Admin Dashboard</h1>
                <p class="sa-subtitle">Platform-wide overview and restaurant management</p>
            </div>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <span class="sa-date-badge">{{ $currentDate }}</span>
                <a href="{{ \App\Filament\Resources\RestaurantResource::getUrl('create') }}" class="sa-btn-primary">
                    + Add New Restaurant
                </a>
            </div>
        </div>

        {{-- 5 STATS CARDS --}}
        <div class="sa-stats-grid">

            {{-- Card 1: Restaurants --}}
            <div class="sa-stat-card">
                <span class="sa-stat-label" style="color: var(--brand-orange);">All Restaurants</span>
                <span class="sa-stat-value">{{ number_format($totalRestaurants) }}</span>
                <span class="sa-stat-desc" style="color: var(--brand-green);">
                    @if($restaurantGrowth > 0) ↑ @elseif($restaurantGrowth < 0) ↓ @else • @endif
                    {{ abs($restaurantGrowth) }}% from last month
                </span>
                <div class="sa-stat-icon" style="color: var(--brand-orange); background-color: rgba(234, 88, 12, 0.1);">
                    <x-heroicon-o-building-storefront style="width: 20px; height: 20px;" />
                </div>
            </div>

            {{-- Card 2: Users --}}
            <div class="sa-stat-card">
                <span class="sa-stat-label" style="color: var(--brand-blue);">Registered Users</span>
                <span class="sa-stat-value">{{ number_format($totalUsers) }}</span>
                <span class="sa-stat-desc" style="color: var(--brand-blue);">
                    @if($userGrowth > 0) ↑ @elseif($userGrowth < 0) ↓ @else • @endif
                    {{ abs($userGrowth) }}% from last month
                </span>
                <div class="sa-stat-icon" style="color: var(--brand-blue); background-color: rgba(59, 130, 246, 0.1);">
                    <x-heroicon-o-users style="width: 20px; height: 20px;" />
                </div>
            </div>

            {{-- Card 3: Total Orders --}}
            <div class="sa-stat-card">
                <span class="sa-stat-label" style="color: var(--brand-green);">Lifetime Orders</span>
                <span class="sa-stat-value">{{ $totalOrders }}</span>
                <span class="sa-stat-desc" style="color: var(--text-sub);">+ {{ $todayOrders }} orders today</span>
                <div class="sa-stat-icon" style="color: var(--brand-green); background-color: rgba(16, 185, 129, 0.1);">
                    <x-heroicon-o-shopping-bag style="width: 20px; height: 20px;" />
                </div>
            </div>

            {{-- Card 4: Total Customers --}}
            <div class="sa-stat-card">
                <span class="sa-stat-label" style="color: var(--brand-red);">Total Diners</span>
                <span class="sa-stat-value">{{ $totalCustomers }}</span>
                <span class="sa-stat-desc" style="color: var(--brand-red);">+ {{ $todayCustomers }} joined today</span>
                <div class="sa-stat-icon" style="color: var(--brand-red); background-color: rgba(225, 29, 72, 0.1);">
                    <x-heroicon-o-user-group style="width: 20px; height: 20px;" />
                </div>
            </div>

            {{-- Card 5: Revenue --}}
            <div class="sa-stat-card">
                <span class="sa-stat-label" style="color: var(--brand-purple);">Total Revenue</span>
                <span class="sa-stat-value">₹{{ $totalRevenue }}</span>
                <span class="sa-stat-desc" style="color: var(--brand-purple);">+ ₹{{ $todayRevenue }} today</span>
                <div class="sa-stat-icon"
                    style="color: var(--brand-purple); background-color: rgba(139, 92, 246, 0.1);">
                    <x-heroicon-o-currency-rupee style="width: 20px; height: 20px;" />
                </div>
            </div>
        </div>

        {{-- RESTAURANT TABLE SECTION --}}
        <div class="sa-table-container">
            <div class="sa-table-header">
                <h2 style="font-size: 1.125rem; font-weight: 700; color: var(--text-main);">Restaurant Overview</h2>
                <input type="text" class="sa-search-input" placeholder="Search restaurant...">
            </div>

            <div style="overflow-x: auto;">
                <table class="sa-table">
                    <thead>
                        <tr>
                            <th>Restaurant ID</th>
                            <th>Restaurant Name</th>
                            <th>Max User Limit</th>
                            <th>Active Users</th>
                            <th>Remaining Capacity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($restaurants as $restaurant)
                            <tr>
                                <td style="color: var(--text-sub); font-family: monospace;">{{ $restaurant->id }}</td>
                                <td style="font-weight: 700; color: var(--text-main);">{{ $restaurant->name }}</td>
                                <td>{{ $restaurant->user_limits }}</td>
                                <td>
                                    {{ $restaurant->active_users_count }}
                                    <div class="sa-progress-bg">
                                        <div class="sa-progress-fill {{ $restaurant->remaining_capacity <= 2 ? 'danger' : '' }}"
                                            style="width: {{ $restaurant->occupancy_percent }}%;"></div>
                                    </div>
                                </td>
                                <td>
                                    @if($restaurant->remaining_capacity <= 2)
                                        <span style="color: var(--brand-red); font-style: italic; font-weight: 600;">Nearly Full
                                            ({{ $restaurant->remaining_capacity }})</span>
                                    @else
                                        <span
                                            style="color: var(--brand-orange); font-weight: 500;">{{ $restaurant->remaining_capacity }}
                                            slots left</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ \App\Filament\Resources\RestaurantResource::getUrl('edit', ['record' => $restaurant->id]) }}"
                                        style="color: var(--text-sub); transition: color 0.2s;"
                                        onmouseover="this.style.color='var(--brand-orange)'"
                                        onmouseout="this.style.color='var(--text-sub)'">
                                        <x-heroicon-o-pencil-square style="width: 20px; height: 20px;" />
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Table Footer / Pagination --}}
            <div class="sa-pagination">
                <span>Showing {{ $restaurants->count() }} Restaurants</span>
                <div>
                    <button class="sa-page-btn">Prev</button>
                    <button class="sa-page-btn active">1</button>
                    <button class="sa-page-btn">Next</button>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>