<x-filament-panels::page>
    <style>
        /* 🎨 SUPER ADMIN CUSTOM UI (PREMIUM GLASSMORPHISM THEME) */

        /* ☀️ VARIABLES WITH NEW COLOR PALETTE */
        .sa-scope {
            /* Text Colors */
            --text-main: #0f172a;
            --text-sub: #475569;
            
            /* 🟠 Brand Orange Palette */
            --brand-orange-primary: #f16b3f;
            --brand-orange-light: #fe9a54;
            --brand-orange-bg: rgba(241, 107, 63, 0.12);
            --brand-orange-border: rgba(241, 107, 63, 0.25);

            /* 🔵 Brand Blue Palette (#2a4795) */
            --brand-blue-primary: #2a4795; 
            --brand-blue-light: #456aba;
            --brand-blue-bg: rgba(42, 71, 149, 0.12);
            --brand-blue-border: rgba(42, 71, 149, 0.25);

            /* Status Colors */
            --brand-green: #10b981;
            --brand-red: #ef4444;

            /* Glassmorphism Effects */
            --glass-bg: rgba(255, 255, 255, 0.45);
            --glass-border: rgba(255, 255, 255, 0.6);
            --glass-shadow: 0 8px 32px rgba(42, 71, 149, 0.08);
            --glass-blur: blur(16px) saturate(140%);
        }

        /* 🌙 DARK THEME VARIABLES (Optional support) */
        .dark .sa-scope {
            --text-main: #f8fafc;
            --text-sub: #cbd5e1;
            --glass-bg: rgba(15, 23, 42, 0.6);
            --glass-border: rgba(255, 255, 255, 0.1);
            --brand-orange-bg: rgba(241, 107, 63, 0.2);
            --brand-blue-bg: rgba(42, 71, 149, 0.2);
        }

        .sa-container {
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
            color: var(--text-main);
        }

        /* Typography */
        .sa-title {
            font-family: 'Poppins', sans-serif;
            font-size: 1.85rem;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -0.02em;
            margin-bottom: 0.25rem;
            color: var(--brand-blue-primary);
        }

        .sa-subtitle {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-sub);
        }

        /* ==================================================
            🌟 DYNAMIC GLASS CARDS (ORANGE & BLUE THEME)
        ================================================== */
        .sa-stat-card {
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            
            /* 👇 यहाँ बॉर्डर का रंग BLACK सेट किया गया है 👇 */
            border: 1.5px solid #000000 !important; 
            
            border-radius: 1.25rem;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            box-shadow: var(--glass-shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 180px;
            z-index: 1;
        }

        .sa-stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(42, 71, 149, 0.15); 
        }

        /* Subtle inner glow for glass */
        .sa-stat-card::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            padding: 1px;
            background: linear-gradient(135deg, rgba(255,255,255,0.8), rgba(255,255,255,0.1));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            pointer-events: none;
        }

        .watermark-icon {
            position: absolute;
            right: -10%;
            bottom: -15%;
            width: 140px;
            height: 140px;
            opacity: 0.15;
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
            backdrop-filter: blur(4px);
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
            backdrop-filter: blur(4px);
        }

        /* 🟠 ORANGE CARD LOGIC */
        .card-orange .watermark-icon, 
        .card-orange .main-icon-wrapper svg { color: var(--brand-orange-primary) !important; }
        .card-orange .main-icon-wrapper { background-color: var(--brand-orange-bg) !important; border: 1px solid var(--brand-orange-border); }
        .card-orange .action-btn { background-color: var(--brand-orange-bg) !important; color: var(--brand-orange-primary) !important; border: 1px solid var(--brand-orange-border); }
        .card-orange .action-btn:hover { background: linear-gradient(135deg, var(--brand-orange-primary), var(--brand-orange-light)) !important; color: white !important; }

        /* 🔵 BLUE CARD LOGIC */
        .card-blue .watermark-icon, 
        .card-blue .main-icon-wrapper svg { color: var(--brand-blue-primary) !important; }
        .card-blue .main-icon-wrapper { background-color: var(--brand-blue-bg) !important; border: 1px solid var(--brand-blue-border); }
        .card-blue .action-btn { background-color: var(--brand-blue-bg) !important; color: var(--brand-blue-primary) !important; border: 1px solid var(--brand-blue-border); }
        .card-blue .action-btn:hover { background: linear-gradient(135deg, var(--brand-blue-primary), var(--brand-blue-light)) !important; color: white !important; }

        /* Card Text styling */
        .sa-stat-label {
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-sub);
        }

        .sa-stat-value {
            font-family: 'Poppins', sans-serif;
            font-size: 2.4rem;
            font-weight: 700;
            color: var(--brand-blue-primary);
            line-height: 1;
            display: block;
            margin-bottom: 0.5rem;
        }

        .sa-stat-desc {
            font-size: 0.8rem;
            font-weight: 600;
            display: block;
            color: var(--brand-orange-primary);
        }
        .card-blue .sa-stat-desc { color: var(--brand-blue-light); }

        /* IN-CARD ACTION BUTTONS FOR BRANCH SECTION */
        .sa-card-btn {
            display: inline-block;
            margin-top: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.4rem 0.85rem;
            border-radius: 0.5rem;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .sa-card-btn-primary {
            color: var(--brand-orange-primary);
            background-color: var(--brand-orange-bg);
            border: 1px solid var(--brand-orange-border);
        }

        .sa-card-btn-primary:hover {
            background: linear-gradient(135deg, var(--brand-orange-primary), var(--brand-orange-light));
            color: white;
            border-color: transparent;
        }

        /* Glass Table Layout */
        .sa-table-container {
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            
            /* 👇 यहाँ भी टेबल के बॉर्डर का रंग BLACK सेट किया गया है 👇 */
            border: 1.5px solid #000000;
            
            border-radius: 1.25rem;
            overflow: hidden;
            box-shadow: var(--glass-shadow);
            margin-top: 1rem;
        }

        .sa-table-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255,255,255,0.2);
        }

        .sa-search-input {
            background-color: rgba(255,255,255,0.5);
            border: 1.5px solid var(--brand-blue-bg);
            border-radius: 0.75rem;
            padding: 0.5rem 1rem 0.5rem 2.5rem;
            font-size: 0.875rem;
            color: var(--text-main);
            width: 250px;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="%23456aba"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>');
            background-repeat: no-repeat;
            background-position: 0.75rem center;
            background-size: 1rem;
            transition: all 0.3s;
        }
        
        .sa-search-input:focus {
            outline: none;
            border-color: var(--brand-blue-light);
            background-color: rgba(255,255,255,0.8);
            box-shadow: 0 0 0 3px var(--brand-blue-bg);
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
            color: var(--brand-blue-primary);
            text-transform: uppercase;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            background: rgba(255,255,255,0.3);
        }

        .sa-table td {
            padding: 1.25rem 1.5rem;
            font-size: 0.875rem;
            color: var(--text-main);
            border-bottom: 1px solid rgba(0,0,0,0.03);
            background: transparent;
        }

        .sa-progress-bg {
            background-color: rgba(0,0,0,0.08);
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
            background: linear-gradient(90deg, var(--brand-blue-primary), var(--brand-blue-light));
        }

        .sa-progress-fill.danger { background: linear-gradient(90deg, var(--brand-red), #f87171); }

        .sa-pagination {
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.875rem;
            color: var(--text-sub);
            background: rgba(255,255,255,0.2);
        }

        .sa-page-btn {
            padding: 0.25rem 0.75rem;
            border: 1px solid var(--brand-orange-border);
            border-radius: 0.5rem;
            background: rgba(255,255,255,0.5);
            cursor: pointer;
            color: var(--brand-blue-primary);
            font-weight: 600;
            margin-left: 0.25rem;
            transition: all 0.2s;
        }

        .sa-page-btn.active, .sa-page-btn:hover {
            background: linear-gradient(135deg, var(--brand-orange-primary), var(--brand-orange-light));
            color: white;
            border-color: transparent;
        }

        .status-badge {
            font-weight: 600;
            font-size: 0.8rem;
            padding: 4px 10px;
            border-radius: 6px;
            backdrop-filter: blur(4px);
        }

        .status-healthy { color: var(--brand-green); background-color: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); }
        .status-danger { color: var(--brand-red); background-color: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); }

        /* Grids */
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

        @media (min-width: 640px) { .sa-stats-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (min-width: 1024px) { .sa-stats-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (min-width: 1280px) { .sa-stats-grid { grid-template-columns: repeat(5, 1fr); } }

        /* Full Screen Background Wrapper */
        .custom-page-bg {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 0;
            background-image: url('{{ asset("images/bg.png") }}');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            opacity: 0.15;
            pointer-events: none;
            mix-blend-mode: multiply;
        }
    </style>

    <div class="custom-page-bg"></div>

    <div class="sa-scope sa-container" style="position: relative; z-index: 10;">

        {{-- HEADER SECTION --}}
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <h1 class="sa-title">Super Admin Dashboard</h1>
                <p class="sa-subtitle">Platform-wide overview and restaurant management</p>
            </div>
        </div>

        {{-- 5 STATS CARDS --}}
        <div class="sa-stats-grid">

            {{-- Card 1: Restaurants --}}
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

            {{-- Card 2: Users --}}
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

            {{-- Card 3: Total Orders --}}
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

            {{-- Card 4: Total Customers --}}
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

            {{-- Card 5: Revenue --}}
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
                <h2 style="font-family: 'Poppins', sans-serif; font-size: 1.25rem; font-weight: 700; color: var(--brand-blue-primary); margin-bottom: 1rem;">
                    Restaurant Branch Management</h2>

                <div class="sa-restaurant-grid">
                    @foreach($restaurants as $rest)
                        <div class="sa-stat-card {{ $loop->iteration % 2 == 0 ? 'card-blue' : 'card-orange' }}" style="padding: 1.25rem; min-height: auto;">
                            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.25rem;">
                                @if($rest->logo_path)
                                    <img src="{{ Storage::url($rest->logo_path) }}" style="width: 52px; height: 52px; border-radius: 50%; object-fit: cover; border: 1.5px solid #000000; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                                @else
                                    <div style="width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg, var(--brand-blue-light), var(--brand-blue-primary)); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.4rem; box-shadow: 0 4px 10px rgba(42, 71, 149, 0.2);">
                                        {{ strtoupper(substr($rest->name, 0, 1)) }}
                                    </div>
                                @endif
                                <div style="flex: 1;">
                                    <span style="font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 1.15rem; color: var(--brand-blue-primary); display: block; line-height: 1.2;">{{ $rest->name }}</span>
                                    <span style="font-size: 0.75rem; color: var(--text-sub); font-weight: 600;">ID: {{ $rest->id }}</span>
                                </div>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 1rem; border-top: 1px solid rgba(0,0,0,0.06);">
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-size: 0.75rem; color: var(--text-sub); text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Branches</span>
                                    <span style="font-size: 1.1rem; font-family: 'Poppins', sans-serif; font-weight: 700; color: var(--brand-orange-primary);">
                                        {{ $rest->current_branch_count }} / {{ $rest->max_branches ?? '∞' }}
                                    </span>
                                </div>
                                @if($rest->has_branches)
                                    <a href="{{ \App\Filament\Resources\BranchResource::getUrl('create', ['restaurant_id' => $rest->id]) }}" class="sa-card-btn sa-card-btn-primary" style="margin-top: 0;">
                                        + Add Branch
                                    </a>
                                @else
                                    <span style="margin-top: 0; font-size: 0.7rem; font-weight: 600; padding: 0.4rem 0.85rem; border-radius: 0.5rem; background-color: rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.05); color: var(--text-sub);">
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
                <h2 style="font-family: 'Poppins', sans-serif; font-size: 1.25rem; font-weight: 700; color: var(--brand-blue-primary); margin: 0;">
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
                                <td style="color: var(--text-sub); font-family: monospace; font-weight: 600;">#{{ $restaurant->id }}</td>
                                <td style="font-family: 'Poppins', sans-serif; font-weight: 600; color: var(--brand-blue-primary);">{{ $restaurant->name }}</td>
                                <td style="text-align: center;">
                                    @if($restaurant->has_branches)
                                        <x-heroicon-o-check-circle style="width: 24px; height: 24px; color: var(--brand-green); margin: 0 auto;" />
                                    @else
                                        <x-heroicon-o-minus-circle style="width: 24px; height: 24px; color: var(--text-sub); margin: 0 auto;" />
                                    @endif
                                </td>
                                <td style="text-align: center; font-weight: 700; font-size: 1rem; color: var(--brand-orange-primary);">
                                    {{ $restaurant->has_branches ? ($restaurant->max_branches ?? '∞') : '-' }}
                                </td>
                                <td style="font-weight: 600;">{{ $restaurant->user_limits }}</td>
                                <td>
                                    <span style="font-weight: 600;">{{ $restaurant->active_users_count }}</span>
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
                                    <a href="{{ \App\Filament\Resources\RestaurantResource::getUrl('edit', ['record' => $restaurant->id]) }}" style="color: var(--brand-blue-light); transition: color 0.2s; display: inline-flex; padding: 4px; background: rgba(255,255,255,0.5); border-radius: 6px; border: 1px solid #000000;" onmouseover="this.style.color='var(--brand-orange-primary)'" onmouseout="this.style.color='var(--brand-blue-light)'">
                                        <x-heroicon-o-pencil-square style="width: 20px; height: 20px;" />
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="sa-pagination">
                <span style="font-weight: 500;">Showing {{ $restaurants->count() }} Restaurants</span>
                <div>
                    <button class="sa-page-btn">Prev</button>
                    <button class="sa-page-btn active">1</button>
                    <button class="sa-page-btn">Next</button>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>