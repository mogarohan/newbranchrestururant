<x-filament-panels::page>

    {{-- 🎨 ULTRA-PREMIUM POS UI (Fixed Layout, 1-Line Widgets, Better Tables) --}}
    <style>
        .pos-container {
            width: 100%;
            font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
        }

        /* Main Layout Grid */
        .pos-layout {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            align-items: start;
        }

        @media (min-width: 1024px) {
            .pos-layout {
                display: grid;
                grid-template-columns: 1fr 320px;
            }
        }

        @media (min-width: 1280px) {
            .pos-layout {
                grid-template-columns: 1fr 380px;
            }
        }

        /* 🌙 THEME ADAPTIVE VARIABLES */
        /* 🎨 ULTRA-PREMIUM TRANSPARENT POS UI VARIABLES */
        .pos-scope {
            /* Light Theme - Glass Look */
            --surface-card: rgba(255, 255, 255, 0.4); /* Transparent White */
            --surface-bg: transparent; /* No solid background */
            --border-light: rgba(156, 163, 175, 0.2); 
            --border-strong: rgba(156, 163, 175, 0.4);
            --text-primary: #111827;
            --text-muted: #6b7280;
            --accent-green: #10b981;
            --accent-green-light: rgba(16, 185, 129, 0.1);
            --accent-orange: #ea580c;
            --accent-orange-light: rgba(234, 88, 12, 0.1);
            --accent-red: #ef4444;
            --accent-blue: #3b82f6;
            --accent-blue-light: rgba(59, 130, 246, 0.1);
            --card-radius: 12px;
            --shadow-sm: none; /* Shadow hatai taaki glass clean lage */
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.03);
        }

        .dark .pos-scope {
            /* Dark Theme - Premium Glass Look */
            --surface-card: rgba(31, 41, 55, 0.4); /* Transparent Dark Gray */
            --surface-bg: transparent;
            --border-light: rgba(75, 85, 99, 0.3);
            --border-strong: rgba(156, 163, 175, 0.2);
            --text-primary: #f9fafb;
            --text-muted: #9ca3af;
            --accent-green-light: rgba(16, 185, 129, 0.15);
            --accent-orange-light: rgba(234, 88, 12, 0.15);
            --accent-blue-light: rgba(59, 130, 246, 0.15);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        /* Essential Fix: Backdrop Blur lagane se "Glass" effect asali lagega */
        .pos-card, .stat-card, .pos-receipt, .pos-table {
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            background-color: var(--surface-card) !important;
        }

        /* 📦 COMPONENT CLASSES */
        .pos-card {
            background-color: var(--surface-card);
            border: 1px solid var(--border-light);
            border-radius: var(--card-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
        }

        /* 📊 STATS GRID (FIXED: Strictly 4 Columns in 1 Row on Desktop) */
        .pos-stats {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        @media (min-width: 640px) {
            .pos-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Yeh line ensure karegi ki PC par hamesha 4 cards ek line me dikhe! */
        @media (min-width: 1024px) {
            .pos-stats {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        .stat-card {
            background-color: var(--surface-card);
            border: 1px solid var(--border-light);
            border-radius: var(--card-radius);
            padding: 1.25rem;
            /* Thoda padding kam kiya taaki 4 cards ek sath fit ho sakein */
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
        }

        .stat-label {
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
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
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .stat-desc {
            font-size: 0.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* 🪑 TABLE GRID */
        .pos-table-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 1rem;
        }

        .pos-table {
            height: 130px;
            cursor: pointer;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background-color: var(--surface-card);
            border: 2px solid var(--border-light);
            border-radius: 12px;
            padding: 1rem;
            transition: all 0.2s ease;
        }

        .pos-table.available {
            border-style: dashed;
        }

        .pos-table.available:hover {
            border-color: var(--text-muted);
            border-style: solid;
            background-color: var(--surface-bg);
        }

        .pos-table.occupied {
            border-color: var(--accent-green);
            background-color: var(--accent-green-light);
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.1);
        }

        .pos-table.occupied:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(16, 185, 129, 0.2);
        }

        .pos-table.selected {
            border-color: var(--accent-orange) !important;
            background-color: var(--accent-orange-light) !important;
            transform: scale(1.02);
            box-shadow: 0 0 0 4px rgba(234, 88, 12, 0.1);
        }

        /* 🧾 RECEIPT SIDEBAR */
        .pos-receipt {
            height: calc(100vh - 6.5rem);
            position: sticky;
            top: 5.5rem;
            display: flex;
            flex-direction: column;
            background-color: var(--surface-card);
            border: 1px solid var(--border-strong);
            border-radius: var(--card-radius);
            box-shadow: var(--shadow-md);
            overflow: hidden;
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
            width: 6px;
            height: 6px;
        }

        .pos-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .pos-scroll::-webkit-scrollbar-thumb {
            background: var(--border-strong);
            border-radius: 10px;
        }
    </style>

    <div wire:poll.5s class="pos-scope pos-container">


        <div class="pos-layout">

            {{-- ========================================== --}}
            {{-- LEFT COLUMN: MASTER VIEW --}}
            {{-- ========================================== --}}
            <div class="flex flex-col min-w-0">

                {{-- 1. STATS WIDGETS (FIXED TO 1 LINE) --}}
                <div class="pos-stats">
                    {{-- Card 1: Total Tables --}}
                    <div class="stat-card" style="border-bottom: 4px solid var(--accent-orange);">
                        <div>
                            <div class="stat-header">
                                <span class="stat-label" style="color: var(--accent-orange);">Total Tables</span>
                                <div class="stat-icon"
                                    style="background-color: var(--accent-orange-light); color: var(--accent-orange);">
                                    <x-heroicon-s-squares-2x2 style="width: 20px; height: 20px;" />
                                </div>
                            </div>
                            <span class="stat-value">{{ $totalTables }}</span>
                        </div>
                        <span class="stat-desc" style="color: var(--text-muted);">Capacity</span>
                    </div>

                    {{-- Card 2: Active Tables --}}
                    <div class="stat-card" style="border-bottom: 4px solid var(--accent-green);">
                        <div>
                            <div class="stat-header">
                                <span class="stat-label" style="color: var(--accent-green);">Active Tables</span>
                                <div class="stat-icon"
                                    style="background-color: var(--accent-green-light); color: var(--accent-green);">
                                    <x-heroicon-s-play-circle style="width: 20px; height: 20px;" />
                                </div>
                            </div>
                            <span class="stat-value">{{ $activeTables }}</span>
                        </div>
                        <span class="stat-desc" style="color: var(--accent-green);">Currently serving</span>
                    </div>

                    {{-- Card 3: Occupancy --}}
                    <div class="stat-card" style="border-bottom: 4px solid var(--accent-blue);">
                        <div>
                            <div class="stat-header">
                                <span class="stat-label" style="color: var(--accent-blue);">Occupancy</span>
                                <div class="stat-icon"
                                    style="background-color: var(--accent-blue-light); color: var(--accent-blue);">
                                    <x-heroicon-s-chart-pie style="width: 20px; height: 20px;" />
                                </div>
                            </div>
                            <span class="stat-value">{{ $occupancyRate }}%</span>
                        </div>
                        <span class="stat-desc" style="color: var(--text-muted);">Floor utilized</span>
                    </div>

                    {{-- Card 4: Total Diners --}}
                    <div class="stat-card" style="border-bottom: 4px solid var(--accent-red);">
                        <div>
                            <div class="stat-header">
                                <span class="stat-label" style="color: var(--accent-red);">Active Diners</span>
                                <div class="stat-icon"
                                    style="background-color: rgba(239, 68, 68, 0.1); color: var(--accent-red);">
                                    <x-heroicon-s-users style="width: 20px; height: 20px;" />
                                </div>
                            </div>
                            <span class="stat-value">{{ $activeSessions }}</span>
                        </div>
                        <span class="stat-desc" style="color: var(--text-muted);">Guests seated</span>
                    </div>
                </div>

                {{-- 2. URGENT ACTION STRIP (KITCHEN TICKETS) --}}
                @if($incomingOrders->count() > 0)
                    <div class="pos-card mb-8"
                        style="border: 2px solid var(--accent-red); background: rgba(239, 68, 68, 0.05); padding: 1.25rem;">
                        <h2
                            style="color: var(--accent-red); font-size: 1rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
                            <x-heroicon-s-bell-alert style="width: 20px;" class="animate-bounce" /> Kitchen Action Required
                            ({{ $incomingOrders->count() }})
                        </h2>

                        <div class="pos-scroll flex gap-4 overflow-x-auto pb-2">
                            @foreach($incomingOrders as $order)
                                <div class="pos-card min-w-[320px] flex-shrink-0 flex flex-col p-4 shadow-sm"
                                    style="border-left: 6px solid var(--accent-red); background: var(--surface-card);">

                                    <div class="flex justify-between items-start border-b pb-3 mb-3"
                                        style="border-color: var(--border-light);">
                                        <div>
                                            <span
                                                style="color: var(--text-primary); font-weight: 900; font-size: 1.25rem; display: block; line-height: 1;">Table
                                                {{ $order->restaurantTable->table_number ?? 'TW' }}</span>
                                            <span
                                                style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600; margin-top: 4px; display: block;">Order
                                                #{{ $order->id }} • {{ $order->customer_name ?? 'Guest' }}</span>
                                        </div>
                                        <div class="text-right">
                                            <span
                                                style="color: var(--accent-green); font-weight: 900; font-size: 1.1rem; display: block;">₹{{ number_format($order->total_amount, 0) }}</span>
                                            <span
                                                style="color: var(--accent-orange); font-size: 0.75rem; font-weight: 800;">{{ $order->created_at->diffForHumans(null, true, true) }}</span>
                                        </div>
                                    </div>

                                    @if($order->notes)
                                        <div
                                            class="mb-3 p-2 rounded bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-900/30">
                                            <span style="color: var(--accent-red); font-size: 0.8rem; font-weight: 700;">Note:
                                                {{ $order->notes }}</span>
                                        </div>
                                    @endif

                                    <div class="flex flex-col gap-2 flex-grow mb-4">
                                        @foreach($order->items as $item)
                                            <div class="flex items-start gap-2">
                                                <span
                                                    style="background: var(--surface-bg); border: 1px solid var(--border-strong); color: var(--text-muted); font-size: 0.65rem; font-weight: 900; padding: 2px 6px; border-radius: 4px; margin-top: 2px;">
                                                    {{ strtoupper($item->menuItem?->category?->name ?? 'GEN') }}
                                                </span>
                                                <div>
                                                    <span style="color: var(--text-primary); font-size: 0.9rem; font-weight: 700;">
                                                        <strong>{{ $item->quantity }}x</strong>
                                                        {{ $item->menuItem->name ?? $item->item_name }}
                                                    </span>
                                                    @if($item->notes)
                                                        <span class="block"
                                                            style="color: var(--accent-red); font-size: 0.75rem; font-style: italic; font-weight: 600;">↳
                                                            {{ $item->notes }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="flex gap-2 mt-auto">
                                        <button wire:click="updateStatus({{ $order->id }}, 'accepted')"
                                            style="background: var(--accent-orange); color: white; border: none; padding: 0.75rem; border-radius: 8px; font-weight: 900; font-size: 0.9rem; flex: 1; transition: opacity 0.2s;"
                                            onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                                            Accept & Cook
                                        </button>
                                        <button wire:click="updateStatus({{ $order->id }}, 'cancelled')"
                                            onclick="confirm('Reject this order?')"
                                            style="background: var(--surface-bg); color: var(--text-primary); border: 1px solid var(--border-strong); padding: 0.75rem 1rem; border-radius: 8px; font-weight: 800; font-size: 0.9rem; transition: background 0.2s;"
                                            onmouseover="this.style.background='rgba(239,68,68,0.1)'; this.style.color='var(--accent-red)';"
                                            onmouseout="this.style.background='var(--surface-bg)'; this.style.color='var(--text-primary)';">
                                            Reject
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- 3. FLOOR PLAN GRID --}}
                <div class="pos-card flex-grow">
                    {{-- <div class="flex justify-between items-center mb-6 border-b pb-4"
                        style="border-color: var(--border-light);">
                        <h2 style="color: var(--text-primary); font-size: 1.25rem; font-weight: 900;">Tables Layout</h2>
                        <div
                            class="flex gap-4 bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-full border border-gray-200 dark:border-gray-700">
                            <span
                                style="display: flex; align-items: center; gap: 6px; font-size: 0.75rem; font-weight: 700; color: var(--text-primary);">
                                <div class="w-3 h-3 rounded-full" style="background: var(--accent-green);"></div>
                                Occupied
                            </span>
                            <span
                                style="display: flex; align-items: center; gap: 6px; font-size: 0.75rem; font-weight: 700; color: var(--text-primary);">
                                <div class="w-3 h-3 border-2 border-dashed rounded-full"
                                    style="border-color: var(--border-strong);"></div> Available
                            </span>
                        </div>
                    </div> --}}

                    <div class="pos-table-grid">
                        @foreach($tables as $table)
                            @php
                                $isOccupied = $table->active_sessions_count > 0;
                                $isSelected = $selectedTableId === $table->id;
                            @endphp

                            <div wire:click="openTable({{ $table->id }})"
                                class="pos-table {{ $isOccupied ? 'occupied' : 'available' }} {{ $isSelected ? 'selected' : '' }}">

                                <div class="flex justify-between items-start">
                                    <span
                                        style="color: var(--text-primary); font-size: 1.5rem; font-weight: 900; line-height: 1;">T{{ $table->table_number }}</span>
                                    @if($isOccupied)
                                        <div class="flex gap-1 bg-white dark:bg-gray-900 px-2 py-1 rounded-full shadow-sm">
                                            @if($table->preparing_count > 0)
                                                <div class="w-2.5 h-2.5 rounded-full" style="background: var(--accent-orange);"
                                            title="Cooking"></div>@endif
                                            @if($table->ready_count > 0)
                                                <div class="w-2.5 h-2.5 rounded-full animate-pulse"
                                            style="background: var(--accent-blue);" title="Ready"></div>@endif
                                        </div>
                                    @else
                                        <x-heroicon-o-plus-circle style="width: 24px; color: var(--border-strong);" />
                                    @endif
                                </div>

                                <div
                                    class="mt-auto pt-2 border-t {{ $isOccupied ? 'border-emerald-200 dark:border-emerald-900/50' : 'border-gray-200 dark:border-gray-700 border-dashed' }}">
                                    @if($isOccupied)
                                        <div style="display: flex; align-items: center; justify-content: space-between;">
                                            <div
                                                style="display: flex; align-items: center; gap: 4px; color: var(--accent-green); font-size: 0.85rem; font-weight: 800;">
                                                <x-heroicon-s-users style="width: 14px;" /> {{ $table->active_sessions_count }}
                                            </div>
                                            <span
                                                style="color: var(--text-primary); font-size: 1.1rem; font-weight: 900;">₹{{ number_format($table->total_bill, 0) }}</span>
                                        </div>
                                    @else
                                        <span
                                            style="color: var(--text-muted); font-size: 0.85rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; display: block; text-align: center;">Available</span>
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
            <div>
                @if($selectedTableData)
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
                                        style="color: var(--text-muted); font-size: 0.75rem; font-weight: 800; letter-spacing: 0.05em;">CURRENTLY
                                        VIEWING</span>
                                    <h3
                                        style="color: var(--text-primary); font-size: 2rem; font-weight: 900; line-height: 1; margin-top: 4px; margin-bottom: 0.5rem;">
                                        Table {{ $selectedTableData->table_number }}
                                    </h3>
                                    <p
                                        style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 4px;">
                                        <x-heroicon-s-clock style="width: 14px;" /> Seated at
                                        {{ $selectedTableData->qrSessions->first()?->created_at->format('h:i A') ?? 'N/A' }}
                                    </p>
                                </div>
                                <button wire:click="$set('selectedTableId', null)"
                                    style="background: transparent; border: none; cursor: pointer; color: var(--text-muted); transition: color 0.2s;"
                                    onmouseover="this.style.color='var(--accent-red)'"
                                    onmouseout="this.style.color='var(--text-muted)'">
                                    <x-heroicon-s-x-circle style="width: 32px; height: 32px;" />
                                </button>
                            </div>
                        </div>

                        {{-- Receipt Body (Orders) --}}
                        <div class="pos-receipt-body pos-scroll">
                            <div style="text-align: center; margin-bottom: 1.5rem;">
                                <span
                                    style="background: var(--text-primary); color: var(--surface-card); padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 900; letter-spacing: 0.1em; text-transform: uppercase;">Active
                                    Orders</span>
                            </div>

                            <div class="flex flex-col gap-6">
                                @foreach(['preparing' => 'Cooking', 'ready' => 'Ready to Serve', 'served' => 'Served'] as $statusKey => $label)
                                    @if(isset($groupedOrders[$statusKey]) && $groupedOrders[$statusKey]->count() > 0)
                                        <div>
                                            <div
                                                style="font-size: 0.8rem; font-weight: 900; text-transform: uppercase; color: {{ $statusKey === 'preparing' ? 'var(--accent-orange)' : ($statusKey === 'ready' ? 'var(--accent-blue)' : 'var(--text-muted)') }}; margin-bottom: 0.75rem; border-bottom: 2px solid var(--border-light); padding-bottom: 4px;">
                                                {{ $label }}
                                            </div>

                                            <div class="flex flex-col gap-4">
                                                @foreach($groupedOrders[$statusKey] as $order)
                                                    <div class="flex flex-col gap-2">

                                                        @if($order->notes)
                                                            <div
                                                                style="color: var(--accent-red); font-size: 0.8rem; font-style: italic; font-weight: 700; background: rgba(239, 68, 68, 0.05); padding: 4px 8px; border-radius: 4px; border-left: 2px solid var(--accent-red);">
                                                                Note: {{ $order->notes }}
                                                            </div>
                                                        @endif

                                                        @foreach($order->items as $item)
                                                            <div class="flex justify-between items-start">
                                                                <div class="pr-4">
                                                                    <span
                                                                        style="color: var(--text-primary); font-size: 0.95rem; font-weight: 700; display: block;">
                                                                        <span
                                                                            style="color: var(--text-muted); margin-right: 4px;">{{ $item->quantity }}x</span>{{ $item->menuItem->name ?? $item->item_name }}
                                                                    </span>
                                                                    @if($item->notes)
                                                                        <span
                                                                            style="color: var(--accent-red); font-size: 0.8rem; font-style: italic; font-weight: 600; display: block;">↳
                                                                            {{ $item->notes }}</span>
                                                                    @endif
                                                                </div>
                                                                <span
                                                                    style="color: var(--text-primary); font-size: 1rem; font-weight: 800; white-space: nowrap;">
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
                                <span style="color: var(--text-muted); font-size: 0.9rem; font-weight: 700;">Subtotal</span>
                                <span
                                    style="color: var(--text-primary); font-size: 1rem; font-weight: 800;">₹{{ number_format($runningTotal, 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center mb-4">
                                <span style="color: var(--text-muted); font-size: 0.9rem; font-weight: 700;">Taxes</span>
                                <span style="color: var(--text-primary); font-size: 1rem; font-weight: 800;">₹0.00</span>
                            </div>

                            <div style="border-top: 2px solid var(--text-primary); margin-bottom: 1.25rem;"></div>

                            <div class="flex justify-between items-end mb-6">
                                <span style="color: var(--text-primary); font-size: 1.5rem; font-weight: 900;">Total</span>
                                <span
                                    style="color: var(--accent-green); font-size: 2.25rem; font-weight: 900; line-height: 1;">₹{{ number_format($runningTotal, 2) }}</span>
                            </div>

                            <a href="{{ \App\Filament\Resources\TableBillingResource::getUrl('index') }}"
                                style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; background: var(--accent-orange); color: white; padding: 1.25rem; border-radius: 12px; font-weight: 900; font-size: 1.2rem; text-transform: uppercase; letter-spacing: 0.05em; text-decoration: none; box-shadow: 0 4px 15px rgba(234, 88, 12, 0.3); transition: all 0.2s;"
                                onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(234, 88, 12, 0.4)';"
                                onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 15px rgba(234, 88, 12, 0.3)';">
                                Settle & Checkout
                            </a>
                        </div>

                    </div>
                @else
                    {{-- Empty State Sidebar --}}
                    <div class="pos-receipt justify-center items-center p-8 text-center"
                        style="background: var(--surface-bg); border: 2px dashed var(--border-strong);">
                        <div
                            style="background: var(--surface-card); padding: 1.5rem; border-radius: 50%; border: 1px solid var(--border-light); margin-bottom: 1.5rem; box-shadow: var(--shadow-sm);">
                            <x-heroicon-o-hand-raised style="width: 48px; height: 48px; color: var(--text-muted);" />
                        </div>
                        <h3 style="color: var(--text-primary); font-size: 1.5rem; font-weight: 900; margin-bottom: 0.5rem;">
                            Select a Table</h3>
                        <p style="color: var(--text-muted); font-size: 0.95rem; font-weight: 500; line-height: 1.5;">Click
                            on any occupied table from the layout to view active orders and process checkout.</p>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-filament-panels::page>