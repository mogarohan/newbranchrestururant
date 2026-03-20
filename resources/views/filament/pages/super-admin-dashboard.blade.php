<x-filament-panels::page>
    <style>
        /* 🎨 SUPER ADMIN CUSTOM UI (PREMIUM & THEME-AWARE) */

        /* ☀️ LIGHT THEME VARIABLES */
        .sa-scope {
            --surface-card: #ffffff;
            --surface-bg: #f8fafc;
            --border-color: #e2e8f0;
            --text-main: #0f172a;
            --text-sub: #64748b;
            --bg-badge: #f1f5f9;

            /* Branding Colors based on your request */
            --brand-primary: #F47D20; /* Orange */
            --brand-secondary: #3B82F6; /* Blue */
            
            --brand-primary-light: rgba(244, 125, 32, 0.1);
            --brand-primary-border: rgba(244, 125, 32, 0.2);
            
            --brand-secondary-light: rgba(59, 130, 246, 0.1);

            /* Status Colors */
            --brand-green: #10b981;
            --brand-orange: #F47D20;
            --brand-red: #ef4444;
        }

        /* 🌙 DARK THEME VARIABLES */
        .dark .sa-scope {
            --surface-card: #1e293b;
            --surface-bg: #0f172a;
            --border-color: #334155;
            --text-main: #f8fafc;
            --text-sub: #94a3b8;
            --bg-badge: #334155;

            --brand-primary-light: rgba(244, 125, 32, 0.15);
            --brand-primary-border: rgba(244, 125, 32, 0.3);
        }

        .sa-container {
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
            color: var(--text-main);
        }

        /* Typography */
        .sa-title {
            font-family: 'Poppins', sans-serif;
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
            background-color: var(--brand-primary);
            color: white;
            padding: 0.5rem 1.25rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: opacity 0.2s;
            text-decoration: none;
        }

        .sa-btn-primary:hover {
            opacity: 0.9;
        }

        /* IN-CARD ACTION BUTTONS FOR BRANCH SECTION */
        .sa-card-btn {
            display: inline-block;
            margin-top: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.35rem 0.75rem;
            border-radius: 0.375rem;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .sa-card-btn-primary {
            color: var(--brand-primary);
            background-color: var(--brand-primary-light);
            border: 1px solid var(--brand-primary-border);
        }

        .sa-card-btn-primary:hover {
            background-color: var(--brand-primary-border);
        }

        .dark .sa-card-btn-primary {
            color: #bfdbfe;
        }

        /* Stats Grid */
        .sa-stats-grid {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 1.5rem;
            margin-top: 2rem;
            margin-bottom: 2.5rem;
        }

        .sa-restaurant-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        @media (min-width: 640px) {
            .sa-stats-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (min-width: 1024px) {
            .sa-stats-grid { grid-template-columns: repeat(3, 1fr); }
        }

        @media (min-width: 1280px) {
            .sa-stats-grid { grid-template-columns: repeat(5, 1fr); }
        }

        /* ==================================================
            🌟 DYNAMIC CARDS (ORANGE & BLUE THEME)
           ================================================== */
        .sa-stat-card {
            background-color: var(--surface-card);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 180px;
            z-index: 1;
        }

        .sa-stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
        }

        .watermark-icon {
            position: absolute;
            right: -10%;
            bottom: -15%;
            width: 140px;
            height: 140px;
            opacity: 0.08;
            transform: rotate(-15deg);
            z-index: -1;
            pointer-events: none;
            transition: all 0.3s ease;
        }

        .card-top-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }

        .main-icon-wrapper {
            padding: 0.75rem;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.2s;
        }

        /* 🟠 ORANGE CARD LOGIC */
        .card-orange .watermark-icon, 
        .card-orange .main-icon-wrapper { color: var(--brand-primary) !important; }
        .card-orange .main-icon-wrapper, 
        .card-orange .action-btn { background-color: var(--brand-primary-light) !important; }
        .card-orange .action-btn { color: var(--brand-primary) !important; }
        .card-orange .action-btn:hover { background-color: var(--brand-primary) !important; color: white !important; }

        /* 🔵 BLUE CARD LOGIC */
        .card-blue .watermark-icon, 
        .card-blue .main-icon-wrapper { color: var(--brand-secondary) !important; }
        .card-blue .main-icon-wrapper, 
        .card-blue .action-btn { background-color: var(--brand-secondary-light) !important; }
        .card-blue .action-btn { color: var(--brand-secondary) !important; }
        .card-blue .action-btn:hover { background-color: var(--brand-secondary) !important; color: white !important; }

        /* Card Text styling */
        .sa-stat-label {
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: block;
            margin-bottom: 1rem;
            color: var(--text-sub);
        }

        .sa-stat-value {
            font-family: 'Poppins', sans-serif;
            font-size: 2.25rem;
            font-weight: 700;
            color: var(--text-main);
            line-height: 1;
            display: block;
            margin-bottom: 0.5rem;
        }

        .sa-stat-desc {
            font-size: 0.75rem;
            font-weight: 600;
            display: block;
            color: var(--brand-primary);
        }

        .card-blue .sa-stat-desc { color: var(--brand-secondary); }

        /* Table Layout */
        .sa-table-container {
            background-color: var(--surface-card);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        }

        .sa-table-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sa-search-input {
            background-color: var(--bg-badge);
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
            border-bottom: 1px solid var(--border-color);
            background-color: var(--bg-badge);
        }

        .sa-table td {
            padding: 1.25rem 1.5rem;
            font-size: 0.875rem;
            color: var(--text-main);
            border-bottom: 1px solid var(--border-color);
        }

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
            background-color: var(--brand-secondary);
        }

        .sa-progress-fill.danger { background-color: var(--brand-red); }

        .sa-pagination {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.875rem;
            color: var(--text-sub);
        }

        .sa-page-btn {
            padding: 0.25rem 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 0.375rem;
            background: var(--surface-card);
            cursor: pointer;
            color: var(--text-main);
            font-weight: 500;
            margin-left: 0.25rem;
            transition: all 0.2s;
        }

        .sa-page-btn.active {
            background-color: var(--brand-primary-light);
            border-color: var(--brand-primary);
            color: var(--brand-primary);
        }

        .status-badge {
            font-weight: 600;
            font-size: 0.8rem;
            padding: 2px 8px;
            border-radius: 4px;
        }

        .status-healthy { color: var(--brand-green); background-color: rgba(16, 185, 129, 0.1); }
        .status-danger { color: var(--brand-red); background-color: rgba(239, 68, 68, 0.1); }
    </style>

    <div class="sa-scope sa-container">

        {{-- HEADER SECTION --}}
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <h1 class="sa-title">Super Admin Dashboard</h1>
                <p class="sa-subtitle">Platform-wide overview and restaurant management</p>
            </div>
        </div>

        {{-- 5 STATS CARDS (ALTERNATING ORANGE AND BLUE) --}}
        <div class="sa-stats-grid">

            {{-- Card 1: Restaurants (ORANGE) --}}
            <div class="sa-stat-card card-orange">
                <x-heroicon-s-building-office-2 class="watermark-icon" />
                <div class="card-top-row">
                    <div class="main-icon-wrapper">
                        <x-heroicon-s-building-office-2 class="w-6 h-6" />
                    </div>
                    <a href="{{ \App\Filament\Resources\RestaurantResource::getUrl('create') }}" class="action-btn" title="Add Restaurant">
                        <x-heroicon-o-plus style="width: 18px; font-weight: bold;" />
                    </a>
                </div>
                <div>
                    <span class="sa-stat-label">All Restaurants</span>
                    <span class="sa-stat-value">{{ number_format($totalRestaurants) }}</span>
                    <span class="sa-stat-desc">
                        @if($restaurantGrowth > 0) ↑ @elseif($restaurantGrowth < 0) ↓ @else • @endif
                        {{ abs($restaurantGrowth) }}% growth
                    </span>
                </div>
            </div>

            {{-- Card 2: Users (BLUE) --}}
            <div class="sa-stat-card card-blue">
                <x-heroicon-s-user-group class="watermark-icon" />
                <div class="card-top-row">
                    <div class="main-icon-wrapper">
                        <x-heroicon-s-user-group class="w-6 h-6" />
                    </div>
                    <a href="{{ \App\Filament\Resources\UserResource::getUrl('create') }}" class="action-btn" title="Add User">
                        <x-heroicon-o-plus style="width: 18px; font-weight: bold;" />
                    </a>
                </div>
                <div>
                    <span class="sa-stat-label">Registered Users</span>
                    <span class="sa-stat-value">{{ number_format($totalUsers) }}</span>
                    <span class="sa-stat-desc">
                        @if($userGrowth > 0) ↑ @elseif($userGrowth < 0) ↓ @else • @endif
                        {{ abs($userGrowth) }}% growth
                    </span>
                </div>
            </div>

            {{-- Card 3: Total Orders (ORANGE) --}}
            <div class="sa-stat-card card-orange">
                <x-heroicon-s-shopping-cart class="watermark-icon" />
                <div class="card-top-row">
                    <div class="main-icon-wrapper">
                        <x-heroicon-s-shopping-cart class="w-6 h-6" />
                    </div>
                </div>
                <div>
                    <span class="sa-stat-label">Lifetime Orders</span>
                    <span class="sa-stat-value">{{ $totalOrders }}</span>
                    <span class="sa-stat-desc">+ {{ $todayOrders }} orders today</span>
                </div>
            </div>

            {{-- Card 4: Total Customers (BLUE) --}}
            <div class="sa-stat-card card-blue">
                <x-heroicon-s-qr-code class="watermark-icon" />
                <div class="card-top-row">
                    <div class="main-icon-wrapper">
                        <x-heroicon-s-qr-code class="w-6 h-6" />
                    </div>
                </div>
                <div>
                    <span class="sa-stat-label">Total Diners</span>
                    <span class="sa-stat-value">{{ $totalCustomers }}</span>
                    <span class="sa-stat-desc">+ {{ $todayCustomers }} joined today</span>
                </div>
            </div>

            {{-- Card 5: Revenue (ORANGE) --}}
            <div class="sa-stat-card card-orange">
                <x-heroicon-s-receipt-percent class="watermark-icon" />
                <div class="card-top-row">
                    <div class="main-icon-wrapper">
                        <x-heroicon-s-currency-rupee class="w-6 h-6" />
                    </div>
                </div>
                <div>
                    <span class="sa-stat-label">Total Revenue</span>
                    <span class="sa-stat-value">₹{{ $totalRevenue }}</span>
                    <span class="sa-stat-desc">+ ₹{{ $todayRevenue }} today</span>
                </div>
            </div>
        </div>

        {{-- RESTAURANT CARDS WITH BRANCH INFO --}}
        @if($restaurants->count() > 0)
            <div>
                <h2 style="font-family: 'Poppins', sans-serif; font-size: 1.25rem; font-weight: 700; color: var(--text-main); margin-bottom: 1rem;">
                    Restaurant Branch Management</h2>

                <div class="sa-restaurant-grid">
                    @foreach($restaurants as $rest)
                        <div class="sa-stat-card" style="padding: 1.25rem;">
                            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.25rem;">
                                @if($rest->logo_path)
                                    <img src="{{ Storage::url($rest->logo_path) }}" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border-color);">
                                @else
                                    <div style="width: 48px; height: 48px; border-radius: 50%; background-color: var(--brand-primary-light); color: var(--brand-primary); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.2rem;">
                                        {{ strtoupper(substr($rest->name, 0, 1)) }}
                                    </div>
                                @endif
                                <div style="flex: 1;">
                                    <span style="font-weight: 800; font-size: 1.1rem; color: var(--text-main); display: block; line-height: 1.2;">{{ $rest->name }}</span>
                                    <span style="font-size: 0.75rem; color: var(--text-sub); font-weight: 500;">ID: {{ $rest->id }}</span>
                                </div>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-size: 0.75rem; color: var(--text-sub); text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Branches</span>
                                    <span style="font-size: 1rem; font-weight: 800; color: var(--text-main);">
                                        {{ $rest->current_branch_count }} / {{ $rest->max_branches ?? '∞' }}
                                    </span>
                                </div>
                                @if($rest->has_branches)
                                    <a href="{{ \App\Filament\Resources\BranchResource::getUrl('create', ['restaurant_id' => $rest->id]) }}" class="sa-card-btn sa-card-btn-primary" style="margin-top: 0;">
                                        + Add Branch
                                    </a>
                                @else
                                    <span style="margin-top: 0; font-size: 0.7rem; font-weight: 600; padding: 0.35rem 0.75rem; border-radius: 0.375rem; background-color: var(--bg-badge); color: var(--text-sub);">
                                        Multi-Branch Disabled
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- RESTAURANT TABLE SECTION --}}
        <div class="sa-table-container">
            <div class="sa-table-header">
                <h2 style="font-family: 'Poppins', sans-serif; font-size: 1.25rem; font-weight: 700; color: var(--text-main); margin: 0;">
                    Restaurant Overview</h2>
                <input type="text" class="sa-search-input" placeholder="Search restaurant...">
            </div>

            <div style="overflow-x: auto;">
                <table class="sa-table">
                    <thead>
                        <tr>
                            <th>Restaurant ID</th>
                            <th>Restaurant Name</th>
                            <th style="text-align: center;">Multi-Branch</th>
                            <th style="text-align: center;">Max Branches</th>
                            <th>Max User Limit</th>
                            <th>Active Users</th>
                            <th>Remaining Capacity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($restaurants as $restaurant)
                            <tr>
                                <td style="color: var(--text-sub); font-family: monospace;">#{{ $restaurant->id }}</td>
                                <td style="font-weight: 700; color: var(--text-main);">{{ $restaurant->name }}</td>
                                <td style="text-align: center;">
                                    @if($restaurant->has_branches)
                                        <x-heroicon-o-check-circle style="width: 24px; height: 24px; color: var(--brand-green); margin: 0 auto;" />
                                    @else
                                        <x-heroicon-o-minus-circle style="width: 24px; height: 24px; color: var(--text-sub); margin: 0 auto;" />
                                    @endif
                                </td>
                                <td style="text-align: center; font-weight: 600; font-size: 1rem;">
                                    {{ $restaurant->has_branches ? ($restaurant->max_branches ?? '∞') : '-' }}
                                </td>
                                <td>{{ $restaurant->user_limits }}</td>
                                <td>
                                    {{ $restaurant->active_users_count }}
                                    <div class="sa-progress-bg">
                                        <div class="sa-progress-fill {{ $restaurant->remaining_capacity <= 2 ? 'danger' : '' }}" style="width: {{ $restaurant->occupancy_percent }}%;"></div>
                                    </div>
                                </td>
                                <td>
                                    @if($restaurant->remaining_capacity <= 2)
                                        <span class="status-badge status-danger">Nearly Full ({{ $restaurant->remaining_capacity }})</span>
                                    @else
                                        <span class="status-badge status-healthy">{{ $restaurant->remaining_capacity }} slots left</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ \App\Filament\Resources\RestaurantResource::getUrl('edit', ['record' => $restaurant->id]) }}" style="color: var(--text-sub); transition: color 0.2s;" onmouseover="this.style.color='var(--brand-primary)'" onmouseout="this.style.color='var(--text-sub)'">
                                        <x-heroicon-o-pencil-square style="width: 20px; height: 20px;" />
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

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