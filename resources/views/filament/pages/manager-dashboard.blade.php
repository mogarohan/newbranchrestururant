<x-filament-panels::page>
    <style>
        /* 🎨 ULTRA-PREMIUM POS UI (EXACT IMAGE MATCH & STRICT THEME) */
        .pos-container {
            width: 100%;
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
        }

        /* 📐 Main Layout Grid (FIXED OVERLAP ISSUE) */
        .pos-layout {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        @media (min-width: 1024px) {
            .pos-layout {
                display: grid;
                /* minmax(0, 1fr) ensures left column never pushes right column out of bounds */
                grid-template-columns: minmax(0, 1fr) 320px;
                align-items: flex-start;
            }
        }

        @media (min-width: 1280px) {
            .pos-layout {
                grid-template-columns: minmax(0, 1fr) 380px;
            }
        }

        /* 🌙 THEME ADAPTIVE VARIABLES */
        .pos-scope {
            /* Light Theme Basics */
            --surface-card: #ffffff;
            --surface-bg: #f8fafc;
            --border-light: #e5e7eb;
            --border-strong: #d1d5db;
            --text-primary: #111827;
            --text-secondary: #374151;
            --text-muted: #6b7280;

            /* Status & Brand Colors */
            --brand-blue: #3B82F6;
            --brand-blue-light: rgba(59, 130, 246, 0.12);

            --brand-orange: #F47D20;
            --brand-orange-light: rgba(244, 125, 32, 0.12);

            --accent-green: #10b981;
            /* Available */
            --accent-green-light: rgba(16, 185, 129, 0.1);

            --accent-pink: #f395a3;
            /* Reserved (Baby Pink) */
            --accent-pink-light: rgba(255, 182, 193, 0.3);
            --accent-pink-light-1: rgba(255, 182, 193, 1);
            --accent-red: #ef4444;

            --card-radius: 12px;
            --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.3);
            --shadow-md: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        /* 👇 DARK THEME 👇 */
        .dark .pos-scope {
            --surface-card: #0f172a;
            --surface-bg: transparent;
            --border-light: rgba(255, 255, 255, 0.08);
            --border-strong: rgba(255, 255, 255, 0.15);
            --text-primary: #f9fafb;
            --text-secondary: #e5e7eb;
            --text-muted: #9ca3af;

            --brand-blue-light: rgba(59, 130, 246, 0.15);
            --brand-orange-light: rgba(244, 125, 32, 0.15);
            --accent-green-light: rgba(16, 185, 129, 0.15);
            --accent-pink-light: rgba(255, 182, 193, 0.15);
            --accent-pink-light-1: rgba(255, 182, 193, 1);
            --shadow-sm: none;
            --shadow-md: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        /* -------------------------------------------
           📊 TOP STATS GRID (EXACT SCREENSHOT LAYOUT)
        ----------------------------------------------*/
        .pos-stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        @media (min-width: 1024px) {
            .pos-stats {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        .stat-card-h {
            background-color: var(--surface-card);
            border: 1px solid var(--border-light);
            border-radius: var(--card-radius);
            padding: 1rem;
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.2s ease;
        }

        .stat-card-h:hover {
            transform: translateY(-2px);
        }

        .stat-icon-wrapper {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .stat-icon-wrapper svg {
            width: 26px;
            height: 26px;
        }

        .stat-h-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .stat-label {
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 0.1rem;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 900;
            line-height: 1.1;
            color: var(--text-primary);
            font-family: 'Poppins', sans-serif;
        }

        /* 🎨 THEME CLASSES FOR TOP CARDS */
        .theme-blue .stat-icon-wrapper {
            background-color: var(--brand-blue-light);
            color: var(--brand-blue);
        }

        .theme-orange .stat-icon-wrapper {
            background-color: var(--brand-orange-light);
            color: var(--brand-orange);
        }


        /* -------------------------------------------
           🪑 TABLE GRID (EXACT IMAGE DESIGN)
        ----------------------------------------------*/
        .pos-table-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
            gap: 1.25rem;
        }

        .ts-table {
            background-color: var(--surface-card);
            border-radius: 12px;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            min-height: 220px;
            position: relative;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            border: 2px solid var(--border-light);
        }

        .ts-table:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }

        /* 🎨 TABLE STATUS DESIGNS (FULL BORDER) */
        .ts-table.available {
            border-color: var(--accent-green);
            border-style: dashed;
        }

        .ts-table.occupied {
            border-color: var(--brand-orange);
            border-style: solid;
        }

        .ts-table.reserved {
            border-color: var(--accent-pink);
            border-style: solid;
        }

        .ts-table.selected {
            border-width: 3px;
            border-color: var(--text-primary) !important;
        }

        .dark .ts-table.selected {
            border-color: white !important;
        }

        .ts-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.25rem;
        }

        .ts-title {
            font-size: 1.15rem;
            font-weight: 900;
            color: var(--text-primary);
            line-height: 1;
        }

        .ts-subtitle {
            font-size: 0.6rem;
            font-weight: 700;
            color: var(--text-muted);
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Badges */
        .ts-badge {
            font-size: 0.6rem;
            font-weight: 800;
            padding: 4px 8px;
            border-radius: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .badge-available {
            background-color: var(--accent-green-light);
            color: var(--accent-green);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .badge-occupied {
            background-color: var(--brand-orange-light);
            color: var(--brand-orange);
            border: 1px solid rgba(244, 125, 32, 0.2);
        }

        .badge-reserved {
            background-color: var(--accent-pink-light);
            color: var(--accent-pink);
            border: 1px solid rgba(255, 182, 193, 100);
        }

        /* Body Info */
        .ts-info-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 0.75rem;
        }

        .ts-info-icon {
            color: var(--text-muted);
            width: 16px;
        }

        .ts-info-text {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-secondary);
        }

        /* Available Dashed Box & Button */
        .ts-avail-box {
            width: 56px;
            height: 56px;
            margin: 0 auto 1.25rem auto;
            border: 2px dashed var(--accent-green);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-green);
            background: var(--accent-green-light);
        }

        .ts-btn-assign {
            margin-top: auto;
            width: 100%;
            padding: 0.5rem;
            background-color: var(--accent-green-light);
            color: var(--accent-green);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 8px;
            font-size: 0.65rem;
            font-weight: 800;
            text-align: center;
            text-transform: uppercase;
            transition: all 0.2s;
        }

        .ts-btn-assign:hover {
            background-color: var(--accent-green);
            color: white;
        }

        /* -------------------------------------------
           🧾 RECEIPT SIDEBAR (FIXED SIZING)
        ----------------------------------------------*/
        .pos-receipt {
            height: calc(100vh - 6.5rem);
            position: sticky;
            top: 5.5rem;
            display: flex;
            flex-direction: column;
            background-color: var(--surface-card);
            border: 1px solid var(--border-light);
            border-radius: var(--card-radius);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            width: 100%;
            /* Ensures it stays within grid bounds */
        }

        .pos-receipt-header {
            padding: 1.5rem;
            border-bottom: 2px dashed var(--border-strong);
            background-color: var(--surface-bg);
        }

        .pos-receipt-body {
            flex-grow: 1;
            overflow-y: auto;
            padding: 1.5rem;
        }

        .pos-receipt-footer {
            padding: 1.5rem;
            background-color: var(--surface-bg);
            border-top: 2px dashed var(--border-strong);
        }

        /* SCROLLBAR */
        .pos-scroll::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }

        .pos-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .pos-scroll::-webkit-scrollbar-thumb {
            background: var(--border-strong);
            border-radius: 10px;
        }
    </style>

    {{-- 👇 FIX: Removed wire:poll.5s to prevent DOM disruption crashes 👇 --}}
    <div class="pos-scope pos-container">

        <div class="pos-layout">

            {{-- ========================================== --}}
            {{-- LEFT COLUMN: MASTER VIEW --}}
            {{-- ========================================== --}}
            <div class="flex flex-col w-full min-w-0">

                {{-- 1. TOP STATS WIDGETS (ALTERNATING BLUE & ORANGE) --}}
                <div class="pos-stats">

                    {{-- 1. Total Tables (BLUE) --}}
                    <div class="stat-card-h theme-blue">
                        <div class="stat-icon-wrapper"><x-heroicon-s-squares-2x2 /></div>
                        <div class="stat-h-info">
                            <span class="stat-label">Total Tables</span>
                            <span class="stat-value">{{ $totalTables }}</span>
                        </div>
                    </div>

                    {{-- 2. Active Tables (ORANGE) --}}
                    <div class="stat-card-h theme-orange">
                        <div class="stat-icon-wrapper"><x-heroicon-s-play-circle /></div>
                        <div class="stat-h-info">
                            <span class="stat-label">Active Tables</span>
                            <span class="stat-value">{{ $activeTables }}</span>
                        </div>
                    </div>

                    {{-- 3. Occupancy (BLUE) --}}
                    <div class="stat-card-h theme-blue">
                        <div class="stat-icon-wrapper"><x-heroicon-s-chart-pie /></div>
                        <div class="stat-h-info">
                            <span class="stat-label">Occupancy</span>
                            <span class="stat-value">{{ $occupancyRate }}%</span>
                        </div>
                    </div>

                    {{-- 4. Active Diners (ORANGE) --}}
                    <div class="stat-card-h theme-orange">
                        <div class="stat-icon-wrapper"><x-heroicon-s-users /></div>
                        <div class="stat-h-info">
                            <span class="stat-label">Active Diners</span>
                            <span class="stat-value">{{ $activeSessions }}</span>
                        </div>
                    </div>

                </div>

                {{-- 2. URGENT ACTION STRIP (KITCHEN TICKETS) --}}
                @if($incomingOrders->count() > 0)
                    <div class="mb-6"
                        style="border: 2px solid var(--accent-red); background: rgba(239, 68, 68, 0.05); padding: 1.25rem; border-radius: 12px;">
                        <h2
                            style="color: var(--accent-red); font-size: 0.9rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                            <x-heroicon-s-bell-alert style="width: 18px;" class="animate-bounce" /> Kitchen Action Required
                            ({{ $incomingOrders->count() }})
                        </h2>

                        <div class="pos-scroll flex gap-4 overflow-x-auto pb-2">
                            @foreach($incomingOrders as $order)
                                <div class="min-w-[280px] flex-shrink-0 flex flex-col p-4 shadow-sm"
                                    style="border-left: 6px solid var(--accent-red); background: var(--surface-card); border-radius: 8px;">

                                    <div class="flex justify-between items-start border-b pb-3 mb-3"
                                        style="border-color: var(--border-light);">
                                        <div>
                                            <span
                                                style="color: var(--text-primary); font-weight: 900; font-size: 1.1rem; display: block; line-height: 1;">Table
                                                {{ $order->restaurantTable->table_number ?? 'TW' }}</span>
                                            <span
                                                style="color: var(--text-muted); font-size: 0.75rem; font-weight: 600; margin-top: 4px; display: block;">Order
                                                #{{ $order->id }} • {{ $order->customer_name ?? 'Guest' }}</span>
                                        </div>
                                        <div class="text-right">
                                            <span
                                                style="color: var(--accent-green); font-weight: 900; font-size: 1rem; display: block;">₹{{ number_format($order->total_amount, 0) }}</span>
                                            <span
                                                style="color: var(--brand-orange); font-size: 0.7rem; font-weight: 800;">{{ $order->created_at->diffForHumans(null, true, true) }}</span>
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-2 flex-grow mb-4">
                                        @foreach($order->items as $item)
                                            <div class="flex items-start gap-2">
                                                <span
                                                    style="background: var(--surface-bg); border: 1px solid var(--border-strong); color: var(--text-muted); font-size: 0.6rem; font-weight: 900; padding: 2px 6px; border-radius: 4px; margin-top: 2px;">
                                                    {{ strtoupper($item->menuItem?->category?->name ?? 'GEN') }}
                                                </span>
                                                <div>
                                                    <span style="color: var(--text-primary); font-size: 0.85rem; font-weight: 700;">
                                                        <strong>{{ $item->quantity }}x</strong>
                                                        {{ $item->menuItem->name ?? $item->item_name }}
                                                    </span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="flex gap-2 mt-auto">
                                        <button wire:click="updateStatus({{ $order->id }}, 'accepted')"
                                            style="background: var(--brand-orange); color: white; border: none; padding: 0.6rem; border-radius: 6px; font-weight: 800; font-size: 0.8rem; flex: 1; transition: opacity 0.2s;">
                                            Accept & Cook
                                        </button>
                                        <button wire:click="updateStatus({{ $order->id }}, 'cancelled')"
                                            onclick="confirm('Reject this order?')"
                                            style="background: var(--surface-bg); color: var(--text-primary); border: 1px solid var(--border-strong); padding: 0.6rem 0.8rem; border-radius: 6px; font-weight: 800; font-size: 0.8rem;">
                                            Reject
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                <br />
                {{-- 3. FLOOR PLAN GRID (EXACT DESIGN MATCH) --}}
                <div>
                    <div class="flex flex-col md:flex-row justify-center md:items-center mb-6 pb-2 gap-4">
                        <div class="flex gap-4 px-4 py-2 rounded-full"
                            style="background: var(--surface-card); border: 1px solid var(--border-light);">
                            <span
                                style="display: flex; align-items: center; gap: 6px; font-size: 0.7rem; font-weight: 800; color: var(--text-primary);">
                                <div class="w-3 h-3 rounded-full" style="background: var(--accent-green);"></div>
                                Available
                            </span>
                            <span
                                style="display: flex; align-items: center; gap: 6px; font-size: 0.7rem; font-weight: 800; color: var(--text-primary);">
                                <div class="w-3 h-3 rounded-full" style="background: var(--brand-orange);"></div>
                                Occupied
                            </span>
                            <span
                                style="display: flex; align-items: center; gap: 6px; font-size: 0.7rem; font-weight: 800; color: var(--text-primary);">
                                <div class="w-3 h-3 rounded-full" style="background: var(--accent-pink);"></div>
                                Reserved
                            </span>
                        </div>
                    </div>
                    <br />
                    <div class="pos-table-grid">
                        @foreach($tables as $table)
                            @php
                                $isOccupied = $table->active_sessions_count > 0;
                                // Explicitly ensure an occupied table cannot be marked reserved visually.
                                $isReserved = !$isOccupied && (($table->status ?? '') === 'reserved' || ($table->is_reserved ?? false));
                                $isSelected = $selectedTableId === $table->id;

                                $tableStateClass = 'available';
                                $statusText = 'AVAILABLE';
                                $badgeClass = 'badge-available';

                                if ($isOccupied) {
                                    $tableStateClass = 'occupied';
                                    $statusText = 'OCCUPIED';
                                    $badgeClass = 'badge-occupied';
                                } elseif ($isReserved) {
                                    $tableStateClass = 'reserved';
                                    $statusText = 'RESERVED';
                                    $badgeClass = 'badge-reserved';
                                }

                                // Format Table number to T-01, T-02 etc.
                                $formattedTableNum = is_numeric($table->table_number) ? sprintf('%02d', $table->table_number) : $table->table_number;
                            @endphp

                            <div wire:click="openTable({{ $table->id }})"
                                class="ts-table {{ $tableStateClass }} {{ $isSelected ? 'selected' : '' }}">

                                {{-- Header: T-01 & Badge --}}
                                <div class="ts-header">
                                    <div>
                                        <div class="ts-title">T-{{ $formattedTableNum }}</div>
                                        <div class="ts-subtitle">{{ $table->capacity ?? 4 }}-SEATER</div>
                                    </div>
                                    <div class="ts-badge {{ $badgeClass }}">{{ $statusText }}</div>
                                </div>

                                {{-- Body Info --}}
                                <div class="flex-grow flex flex-col justify-center">
                                    @if($isOccupied)
                                        <div class="ts-info-row">
                                            <x-heroicon-s-clock class="ts-info-icon" />
                                            <span class="ts-info-text">{{ $table->active_sessions_count }} Active
                                                Diner(s)</span>
                                        </div>
                                        <div class="ts-info-row">
                                            <x-heroicon-s-currency-rupee class="ts-info-icon" />
                                            <span class="ts-info-text">₹{{ number_format($table->total_bill, 2) }}</span>
                                        </div>
                                    @elseif($isReserved)
                                        <div class="ts-info-row justify-center mt-2">
                                            <x-heroicon-s-calendar class="w-10 h-10" style="color: var(--accent-pink-light-1);" />
                                        </div>
                                        <div class="text-center mt-2"
                                            style="font-size: 0.85rem; font-weight: 800; color: var(--accent-pink);">
                                            Reserved
                                        </div>
                                    @else
                                        {{-- Available: Dashed Icon Box & Button --}}
                                        <div class="ts-avail-box">
                                            <x-heroicon-s-user-plus class="w-8 h-8" />
                                        </div>
                                        <div class="ts-btn-assign">
                                            Assign Guests
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>

            {{-- ========================================== --}}
            {{-- RIGHT COLUMN: DIGITAL RECEIPT SIDEBAR --}}
            {{-- ========================================== --}}
            <div class="w-full lg:w-auto">
                @if($selectedTableData && $selectedTableData->active_sessions_count > 0)
                    @php
                        $groupedOrders = $selectedTableData->orders->groupBy('status');
                        $runningTotal = $selectedTableData->orders->sum('total_amount');
                    @endphp

                    <div class="pos-receipt">

                        {{-- Receipt Header --}}
                        <div class="pos-receipt-header">
                            <div class="flex justify-between items-start">
                                <div>
                                    <span
                                        style="color: var(--text-muted); font-size: 0.7rem; font-weight: 800; letter-spacing: 0.05em;">CURRENTLY
                                        VIEWING</span>
                                    <h3
                                        style="color: var(--text-primary); font-size: 1.75rem; font-weight: 900; line-height: 1; margin-top: 4px; margin-bottom: 0.5rem;">
                                        Table {{ $selectedTableData->table_number }}
                                    </h3>
                                    <p
                                        style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600; display: flex; align-items: center; gap: 4px;">
                                        <x-heroicon-s-clock style="width: 14px;" /> Seated at
                                        {{ $selectedTableData->qrSessions->first()?->created_at->format('h:i A') ?? 'N/A' }}
                                    </p>
                                </div>
                                <button wire:click="$set('selectedTableId', null)"
                                    style="background: transparent; border: none; cursor: pointer; color: var(--text-muted); transition: color 0.2s;"
                                    onmouseover="this.style.color='var(--accent-red)'"
                                    onmouseout="this.style.color='var(--text-muted)'">
                                    <x-heroicon-s-x-circle style="width: 28px; height: 28px;" />
                                </button>
                            </div>
                        </div>

                        {{-- Receipt Body (Orders) --}}
                        <div class="pos-receipt-body pos-scroll">
                            <div style="text-align: center; margin-bottom: 1.5rem;">
                                <span
                                    style="background: var(--text-primary); color: var(--surface-card); padding: 4px 12px; border-radius: 20px; font-size: 0.65rem; font-weight: 900; letter-spacing: 0.1em; text-transform: uppercase;">Active
                                    Orders</span>
                            </div>

                            <div class="flex flex-col gap-6">
                                @foreach(['preparing' => 'Cooking', 'ready' => 'Ready to Serve', 'served' => 'Served'] as $statusKey => $label)
                                    @if(isset($groupedOrders[$statusKey]) && $groupedOrders[$statusKey]->count() > 0)
                                        <div>
                                            <div
                                                style="font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: {{ $statusKey === 'preparing' ? 'var(--brand-orange)' : ($statusKey === 'ready' ? 'var(--brand-blue)' : 'var(--text-muted)') }}; margin-bottom: 0.75rem; border-bottom: 2px solid var(--border-light); padding-bottom: 4px;">
                                                {{ $label }}
                                            </div>

                                            <div class="flex flex-col gap-4">
                                                @foreach($groupedOrders[$statusKey] as $order)
                                                    <div class="flex flex-col gap-2">
                                                        @if($order->notes)
                                                            <div
                                                                style="color: var(--accent-red); font-size: 0.75rem; font-style: italic; font-weight: 700; background: rgba(239, 68, 68, 0.05); padding: 4px 8px; border-radius: 4px; border-left: 2px solid var(--accent-red);">
                                                                Note: {{ $order->notes }}
                                                            </div>
                                                        @endif

                                                        @foreach($order->items as $item)
                                                            <div class="flex justify-between items-start">
                                                                <div class="pr-4">
                                                                    <span
                                                                        style="color: var(--text-primary); font-size: 0.9rem; font-weight: 700; display: block;">
                                                                        <span
                                                                            style="color: var(--text-muted); margin-right: 4px;">{{ $item->quantity }}x</span>{{ $item->menuItem->name ?? $item->item_name }}
                                                                    </span>
                                                                </div>
                                                                <span
                                                                    style="color: var(--text-primary); font-size: 0.95rem; font-weight: 800; white-space: nowrap;">
                                                                    ₹{{ number_format($item->unit_price * $item->quantity, 0) }}
                                                                </span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        {{-- Receipt Footer (Checkout) --}}
                        <div class="pos-receipt-footer">
                            <div class="flex justify-between items-center mb-2">
                                <span
                                    style="color: var(--text-muted); font-size: 0.85rem; font-weight: 700;">Subtotal</span>
                                <span
                                    style="color: var(--text-primary); font-size: 0.95rem; font-weight: 800;">₹{{ number_format($runningTotal, 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center mb-4">
                                <span style="color: var(--text-muted); font-size: 0.85rem; font-weight: 700;">Taxes</span>
                                <span style="color: var(--text-primary); font-size: 0.95rem; font-weight: 800;">₹0.00</span>
                            </div>

                            <div style="border-top: 2px solid var(--text-primary); margin-bottom: 1.25rem;"></div>

                            <div class="flex justify-between items-end mb-6">
                                <span style="color: var(--text-primary); font-size: 1.25rem; font-weight: 900;">Total</span>
                                <span
                                    style="color: var(--accent-green); font-size: 2rem; font-weight: 900; line-height: 1;">₹{{ number_format($runningTotal, 2) }}</span>
                            </div>

                            <a href="{{ \App\Filament\Resources\TableBillingResource::getUrl('index') }}"
                                style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; background: var(--brand-orange); color: white; padding: 1rem; border-radius: 12px; font-weight: 900; font-size: 1rem; text-transform: uppercase; letter-spacing: 0.05em; text-decoration: none; box-shadow: 0 4px 15px rgba(244, 125, 32, 0.3); transition: all 0.2s;"
                                onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(244, 125, 32, 0.4)';"
                                onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 15px rgba(244, 125, 32, 0.3)';">
                                Settle & Checkout
                            </a>
                        </div>

                    </div>
                @else
                    {{-- Empty State Sidebar --}}
                    @if($this->selectedTableId)
                        @php 
                            $tableInfo = $tables->firstWhere('id', $this->selectedTableId); 
                            $isRes = $tableInfo && ($tableInfo->status === 'reserved');
                        @endphp
                        
                        <div class="pos-receipt justify-center items-center p-8 text-center"
                            style="background: var(--surface-bg); border: 2px dashed var(--border-strong);">
                            
                            <div style="background: var(--surface-card); padding: 1.25rem; border-radius: 50%; border: 1px solid var(--border-light); margin-bottom: 1.5rem; box-shadow: var(--shadow-sm); display: flex; justify-content: center; align-items: center; margin-left: auto; margin-right: auto; width: 80px; height: 80px;">
                                <x-heroicon-o-check-badge style="width: 40px; height: 40px; color: {{ $isRes ? 'var(--accent-pink)' : 'var(--accent-green)' }};" />
                            </div>
                            
                            <h3 style="color: var(--text-primary); font-size: 1.25rem; font-weight: 900; margin-bottom: 0.5rem;">
                                Table {{ $tableInfo->table_number ?? '' }} is {{ $isRes ? 'Reserved' : 'Empty' }}
                            </h3>
                            
                            <p style="color: var(--text-muted); font-size: 0.85rem; font-weight: 500; line-height: 1.5; margin-bottom: 2rem;">
                                {{ $isRes ? 'This table is currently reserved for upcoming guests.' : 'This table is clean and ready for new guests.' }}
                            </p>

                            {{-- THE RESERVATION TOGGLE BUTTON --}}
                            <button wire:click="toggleReservation({{ $this->selectedTableId }})"
                                style="background: {{ $isRes ? 'var(--surface-card)' : 'var(--accent-pink)' }}; color: {{ $isRes ? 'var(--text-primary)' : 'white' }}; border: 1px solid {{ $isRes ? 'var(--border-strong)' : 'var(--accent-pink)' }}; padding: 0.8rem 1.5rem; border-radius: 8px; font-weight: 800; font-size: 0.85rem; width: 100%; transition: all 0.2s;">
                                {{ $isRes ? 'Remove Reservation' : 'Make Reservation' }}
                            </button>
                        </div>
                    @else
                        <div class="pos-receipt justify-center items-center p-8 text-center"
                            style="background: var(--surface-bg); border: 2px dashed var(--border-strong);">
                            <div
                                style="background: var(--surface-card); padding: 1.25rem; border-radius: 50%; border: 1px solid var(--border-light); margin-bottom: 1.5rem; box-shadow: var(--shadow-sm); display: flex; justify-content: center; align-items: center; margin-left: auto; margin-right: auto; width: 80px; height: 80px;">
                                <x-heroicon-o-hand-raised style="width: 40px; height: 40px; color: var(--text-muted);" />
                            </div>
                            <h3
                                style="color: var(--text-primary); font-size: 1.25rem; font-weight: 900; margin-bottom: 0.5rem;">
                                Select a Table</h3>
                            <p style="color: var(--text-muted); font-size: 0.85rem; font-weight: 500; line-height: 1.5;">Click
                                on any occupied table from the layout to view active orders and process checkout.</p>
                        </div>
                    @endif
                @endif
            </div>

        </div>
    </div>
</x-filament-panels::page>