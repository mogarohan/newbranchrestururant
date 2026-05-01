<x-filament-panels::page>
    <style>
        html, body, .fi-layout, .fi-main, .fi-page {
            background-color: transparent !important;
            background: transparent !important;
        }

        .custom-page-bg {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: url("/images/bg.png") !important;
            background-size: cover !important;
            background-position: center !important;
            background-attachment: fixed !important;
            opacity: 0.15 !important;
            z-index: -999 !important;
            pointer-events: none;
        }

        .pos-container {
            width: 100%;
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
            position: relative;
            z-index: 10;
        }

        .pos-layout {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        @media (min-width: 1024px) {
            .pos-layout {
                display: grid;
                grid-template-columns: minmax(0, 1fr) 320px;
                align-items: flex-start;
            }
        }

        @media (min-width: 1280px) {
            .pos-layout {
                grid-template-columns: minmax(0, 1fr) 380px;
            }
        }

        .pos-scope {
            --text-primary: #0f172a;
            --text-secondary: #334155;
            --text-muted: #64748b;
            --brand-blue: #2a4795;
            --brand-blue-light: rgba(42, 71, 149, 0.15);
            --brand-orange: #f16b3f;
            --brand-orange-light: rgba(241, 107, 63, 0.15);
            --accent-green: #10b981;
            --accent-green-light: rgba(16, 185, 129, 0.15);
            --accent-pink: #f395a3;
            --accent-pink-light: rgba(243, 149, 163, 0.15);
            --accent-red: #ef4444;
            --glass-bg: rgba(255, 255, 255, 0.45);
            --glass-border: #000000;
            --glass-shadow: 0 8px 32px rgba(42, 71, 149, 0.08);
            --glass-blur: blur(16px) saturate(140%);
            --card-radius: 1.25rem;
        }

        .dark .pos-scope {
            --text-primary: #f9fafb;
            --text-secondary: #e5e7eb;
            --text-muted: #9ca3af;
            --glass-bg: rgba(15, 15, 20, 0.7);
            --glass-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
            --brand-blue-light: rgba(69, 106, 186, 0.2);
            --brand-orange-light: rgba(241, 107, 63, 0.2);
        }

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
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            border: 1.5px solid var(--glass-border);
            border-radius: var(--card-radius);
            padding: 1rem;
            box-shadow: var(--glass-shadow);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card-h::before {
            content: ''; position: absolute; inset: 0; border-radius: inherit; padding: 1px;
            background: linear-gradient(135deg, rgba(255,255,255,0.8), rgba(255,255,255,0.1));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor; mask-composite: exclude; pointer-events: none;
        }
        .dark .stat-card-h::before { background: linear-gradient(135deg, rgba(255,255,255,0.15), rgba(255,255,255,0.02)); }

        .stat-card-h:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(42, 71, 149, 0.15);
        }
        .dark .stat-card-h:hover { box-shadow: 0 12px 40px rgba(0, 0, 0, 0.8); }

        .stat-icon-wrapper {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            backdrop-filter: blur(4px);
        }

        .stat-icon-wrapper svg {
            width: 26px;
            height: 26px;
        }

        .stat-h-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
            z-index: 2;
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

        .theme-blue .stat-icon-wrapper {
            background-color: var(--brand-blue-light);
            color: var(--brand-blue);
            border: 1px solid rgba(42, 71, 149, 0.2);
        }
        .theme-blue .stat-value { color: var(--brand-blue); }

        .theme-orange .stat-icon-wrapper {
            background-color: var(--brand-orange-light);
            color: var(--brand-orange);
            border: 1px solid rgba(241, 107, 63, 0.2);
        }
        .theme-orange .stat-value { color: var(--brand-orange); }

        .pos-table-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
            gap: 1.25rem;
        }

        .ts-table {
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            border: 1.5px solid var(--glass-border) !important;
            border-radius: var(--card-radius);
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            min-height: 220px;
            position: relative;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: var(--glass-shadow);
            overflow: hidden;
        }

        .ts-table::before {
            content: ''; position: absolute; inset: 0; border-radius: inherit; padding: 1px;
            background: linear-gradient(135deg, rgba(255,255,255,0.8), rgba(255,255,255,0.1));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor; mask-composite: exclude; pointer-events: none;
        }
        .dark .ts-table::before { background: linear-gradient(135deg, rgba(255,255,255,0.15), rgba(255,255,255,0.02)); }

        .ts-table:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(42, 71, 149, 0.15);
        }
        .dark .ts-table:hover { box-shadow: 0 12px 40px rgba(0, 0, 0, 0.8); }

        .ts-table.available { border-top: 4px dashed var(--accent-green) !important; }
        .ts-table.occupied { border-top: 4px solid var(--brand-orange) !important; }
        .ts-table.reserved { border-top: 4px solid var(--accent-pink) !important; }
        .ts-table.selected { border-color: var(--brand-blue) !important; border-width: 2.5px !important; box-shadow: 0 0 0 4px var(--brand-blue-light) !important; }

        .ts-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.25rem;
            position: relative; z-index: 2;
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

        .ts-badge {
            font-size: 0.6rem;
            font-weight: 800;
            padding: 4px 8px;
            border-radius: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            backdrop-filter: blur(4px);
        }

        .badge-available { background-color: var(--accent-green-light); color: var(--accent-green); border: 1px solid rgba(16, 185, 129, 0.3); }
        .badge-occupied { background-color: var(--brand-orange-light); color: var(--brand-orange); border: 1px solid rgba(241, 107, 63, 0.3); }
        .badge-reserved { background-color: var(--accent-pink-light); color: var(--accent-pink); border: 1px solid rgba(243, 149, 163, 0.5); }

        .ts-info-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 0.75rem;
            position: relative; z-index: 2;
        }

        .ts-info-icon { color: var(--text-muted); width: 16px; }
        .ts-info-text { font-size: 0.85rem; font-weight: 700; color: var(--text-secondary); }

        .ts-btn-reserve {
            margin-top: auto;
            width: 100%;
            padding: 0.6rem;
            border-radius: 8px;
            font-size: 0.7rem;
            font-weight: 800;
            text-align: center;
            text-transform: uppercase;
            transition: all 0.2s;
            cursor: pointer;
            position: relative; z-index: 2;
        }

        .ts-btn-reserve.make {
            background-color: rgba(255,255,255,0.2);
            color: var(--text-primary);
            border: 1px solid var(--border-strong);
            backdrop-filter: blur(4px);
        }
        .dark .ts-btn-reserve.make { background-color: rgba(0,0,0,0.2); }
        .ts-btn-reserve.make:hover { background-color: var(--brand-blue); color: white; border-color: var(--brand-blue); }
        .ts-btn-reserve.cancel { background-color: var(--accent-pink-light); color: var(--accent-pink); border: 1px solid var(--accent-pink); }
        .ts-btn-reserve.cancel:hover { background-color: var(--accent-pink); color: white; }
        
        .ts-btn-clean {
            margin-top: 8px;
            width: 100%;
            padding: 0.5rem;
            background-color: var(--brand-orange-light);
            color: var(--brand-orange);
            border: 1px solid var(--brand-orange);
            border-radius: 8px;
            font-size: 0.65rem;
            font-weight: 800;
            text-align: center;
            text-transform: uppercase;
            transition: all 0.2s;
            cursor: pointer;
            position: relative; z-index: 2;
        }

        .ts-btn-clean:hover { background-color: var(--brand-orange); color: white; }

        .pos-receipt {
            height: calc(100vh - 6.5rem);
            position: sticky;
            top: 5.5rem;
            display: flex;
            flex-direction: column;
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            border: 1.5px solid var(--glass-border);
            border-radius: var(--card-radius);
            box-shadow: var(--glass-shadow);
            overflow: hidden;
            width: 100%;
        }

        .pos-receipt-header {
            padding: 1.5rem;
            border-bottom: 1.5px dashed rgba(0,0,0,0.2);
            background-color: rgba(255, 255, 255, 0.2);
        }
        .dark .pos-receipt-header { border-color: rgba(255,255,255,0.2); background-color: rgba(0, 0, 0, 0.2); }

        .pos-receipt-body {
            flex-grow: 1;
            overflow-y: auto;
            padding: 1.5rem;
        }

        .pos-receipt-footer {
            padding: 1.5rem;
            background-color: rgba(255, 255, 255, 0.2);
            border-top: 1.5px dashed rgba(0,0,0,0.2);
        }
        .dark .pos-receipt-footer { border-color: rgba(255,255,255,0.2); background-color: rgba(0, 0, 0, 0.2); }

        .pos-scroll::-webkit-scrollbar { width: 4px; height: 4px; }
        .pos-scroll::-webkit-scrollbar-track { background: transparent; }
        .pos-scroll::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.2); border-radius: 10px; }
        .dark .pos-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); }
        
        .customer-pill {
            display: inline-flex;
            align-items: center;
            background: rgba(255,255,255,0.5);
            border: 1px solid rgba(0,0,0,0.2);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 2px;
            backdrop-filter: blur(4px);
        }
        .dark .customer-pill { background: rgba(0,0,0,0.5); border-color: rgba(255,255,255,0.2); }
        
        .customer-pill.host {
            border-color: var(--brand-orange);
            background: var(--brand-orange-light);
            color: var(--brand-orange);
        }
        .dark .customer-pill.host { color: #ffffff; }

        .urgent-strip {
            border: 1.5px solid var(--glass-border); 
            background: rgba(239, 68, 68, 0.15); 
            backdrop-filter: var(--glass-blur);
            padding: 1.25rem; 
            border-radius: var(--card-radius);
            margin-bottom: 1.5rem;
            box-shadow: var(--glass-shadow);
        }
        .urgent-card {
            border: 1.5px solid var(--glass-border); 
            border-top: 4px solid var(--accent-red);
            background: var(--glass-bg); 
            border-radius: 12px;
        }

        /* 👇 NEW: Buttons for placing/editing orders 👇 */
        .btn-add-order {
            background: var(--brand-blue); 
            color: #fff; 
            border: 1.5px solid #000; 
            padding: 4px 12px; 
            border-radius: 20px; 
            font-size: 0.65rem; 
            font-weight: 900; 
            cursor: pointer; 
            transition: all 0.2s;
        }
        .btn-add-order:hover {
            background: var(--brand-blue-light);
            color: var(--brand-blue);
        }
        .btn-edit-order {
            background: transparent; 
            border: 1px solid var(--text-muted); 
            color: var(--text-muted); 
            padding: 2px 8px; 
            border-radius: 6px; 
            font-size: 0.6rem; 
            font-weight: 800; 
            cursor: pointer; 
            transition: all 0.2s;
        }
        .btn-edit-order:hover {
            color: var(--text-primary); 
            border-color: var(--text-primary);
        }
    </style>

    <div class="custom-page-bg"></div>

    <div class="pos-scope pos-container">
        <div class="pos-layout">

            {{-- LEFT COLUMN: MASTER VIEW --}}
            <div class="flex flex-col w-full min-w-0">

                <div class="pos-stats">
                    <div class="stat-card-h theme-blue">
                        <div class="stat-icon-wrapper"><x-heroicon-s-squares-2x2 /></div>
                        <div class="stat-h-info">
                            <span class="stat-label">Total Tables</span>
                            <span class="stat-value">{{ $totalTables }}</span>
                        </div>
                    </div>
                    <div class="stat-card-h theme-orange">
                        <div class="stat-icon-wrapper"><x-heroicon-s-play-circle /></div>
                        <div class="stat-h-info">
                            <span class="stat-label">Active Tables</span>
                            <span class="stat-value">{{ $activeTables }}</span>
                        </div>
                    </div>
                    <div class="stat-card-h theme-blue">
                        <div class="stat-icon-wrapper"><x-heroicon-s-chart-pie /></div>
                        <div class="stat-h-info">
                            <span class="stat-label">Occupancy</span>
                            <span class="stat-value">{{ $occupancyRate }}%</span>
                        </div>
                    </div>
                    <div class="stat-card-h theme-orange">
                        <div class="stat-icon-wrapper"><x-heroicon-s-users /></div>
                        <div class="stat-h-info">
                            <span class="stat-label">Active Diners</span>
                            <span class="stat-value">{{ $activeSessions }}</span>
                        </div>
                    </div>
                </div>

                @if($incomingOrders->count() > 0)
                    <div class="urgent-strip">
                        <h2 style="color: var(--accent-red); font-size: 0.9rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                            <x-heroicon-s-bell-alert style="width: 18px;" class="animate-bounce" /> Kitchen Action Required ({{ $incomingOrders->count() }})
                        </h2>

                        <div class="pos-scroll flex gap-4 overflow-x-auto pb-2">
                            @foreach($incomingOrders as $order)
                                <div class="urgent-card min-w-[280px] flex-shrink-0 flex flex-col p-4 shadow-sm">
                                    <div class="flex justify-between items-start border-b pb-3 mb-3" style="border-color: rgba(0,0,0,0.1);">
                                        <div>
                                            <span style="color: var(--text-primary); font-weight: 900; font-size: 1.1rem; display: block; line-height: 1;">Table {{ $order->restaurantTable->table_number ?? 'TW' }}</span>
                                            <span style="color: var(--text-muted); font-size: 0.75rem; font-weight: 600; margin-top: 4px; display: block;">Order #{{ $order->id }} • {{ $order->customer_name ?? 'Guest' }}</span>
                                        </div>
                                        <div class="text-right">
                                            <span style="color: var(--accent-green); font-weight: 900; font-size: 1rem; display: block;">₹{{ number_format($order->total_amount, 0) }}</span>
                                            <span style="color: var(--brand-orange); font-size: 0.7rem; font-weight: 800;">{{ $order->created_at->diffForHumans(null, true, true) }}</span>
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-2 flex-grow mb-4">
                                        @foreach($order->items as $item)
                                            <div class="flex items-start gap-2">
                                                <span style="background: rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.1); color: var(--text-muted); font-size: 0.6rem; font-weight: 900; padding: 2px 6px; border-radius: 4px; margin-top: 2px;">
                                                    {{ strtoupper($item->menuItem?->category?->name ?? 'GEN') }}
                                                </span>
                                                <div>
                                                    <span style="color: var(--text-primary); font-size: 0.85rem; font-weight: 700;">
                                                        <strong style="color: var(--brand-blue);">{{ $item->quantity }}x</strong> {{ $item->menuItem->name ?? $item->item_name }}
                                                    </span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="flex gap-2 mt-auto">
                                        <button wire:click="updateStatus({{ $order->id }}, 'accepted')" style="background: linear-gradient(135deg, var(--brand-orange), var(--brand-orange-light)); color: Black ; border: 1px solid #000000; padding: 0.6rem; border-radius: 6px; font-weight: 800; font-size: 0.8rem; flex: 1; transition: opacity 0.2s;">
                                            Accept & Cook
                                        </button>
                                        <button wire:click="updateStatus({{ $order->id }}, 'cancelled')" onclick="confirm('Reject this order?')" style="background: rgba(255,255,255,0.5); color: var(--text-primary); border: 1px solid #000000; padding: 0.6rem 0.8rem; border-radius: 6px; font-weight: 800; font-size: 0.8rem;">
                                            Reject
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                <br />

                <div>
                    <div class="flex flex-col md:flex-row justify-center md:items-center mb-6 pb-2 gap-4">
                        <div class="flex gap-4 px-4 py-2 rounded-full" style="background: var(--glass-bg); border: 1.5px solid #000000; backdrop-filter: var(--glass-blur);">
                            <span style="display: flex; align-items: center; gap: 6px; font-size: 0.7rem; font-weight: 800; color: var(--text-primary);">
                                <div class="w-3 h-3 rounded-full" style="background: var(--accent-green);"></div> Available
                            </span>
                            <span style="display: flex; align-items: center; gap: 6px; font-size: 0.7rem; font-weight: 800; color: var(--text-primary);">
                                <div class="w-3 h-3 rounded-full" style="background: var(--brand-orange);"></div> Occupied
                            </span>
                            <span style="display: flex; align-items: center; gap: 6px; font-size: 0.7rem; font-weight: 800; color: var(--text-primary);">
                                <div class="w-3 h-3 rounded-full" style="background: var(--accent-pink);"></div> Reserved
                            </span>
                        </div>
                    </div>
                    <br />
                    
                    <div class="pos-table-grid">
                        @foreach($tables as $table)
                            @php
                                $isOccupied = $table->active_sessions_count > 0;
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

                                $formattedTableNum = is_numeric($table->table_number) ? sprintf('%02d', $table->table_number) : $table->table_number;
                            @endphp

                            <div wire:click="openTable({{ $table->id }})" class="ts-table {{ $tableStateClass }} {{ $isSelected ? 'selected' : '' }}">

                                <div class="ts-header">
                                    <div>
                                        <div class="ts-title">T-{{ $formattedTableNum }}</div>
                                        <div class="ts-subtitle">OCCUPANCY: {{ $table->active_sessions_count }} / {{ $table->seating_capacity ?? 4 }}</div>
                                    </div>
                                    <div class="ts-badge {{ $badgeClass }}">{{ $statusText }}</div>
                                </div>

                                <div class="flex-grow flex flex-col justify-center">
                                    @if($isOccupied)
                                        <div class="ts-info-row">
                                            <x-heroicon-s-clock class="ts-info-icon" />
                                            <span class="ts-info-text">{{ $table->total_orders_count ?? 0 }} Order(s) Placed</span>
                                        </div>
                                        <div class="ts-info-row">
                                            <x-heroicon-s-currency-rupee class="ts-info-icon" />
                                            <span class="ts-info-text">₹{{ number_format($table->total_bill, 2) }}</span>
                                        </div>

                                        <button wire:click.stop="cleanTable({{ $table->id }})" class="ts-btn-clean" onclick="confirm('Are you sure you want to end all sessions and clean this table?') || event.stopImmediatePropagation()">
                                            Clean Table
                                        </button>

                                    @elseif($isReserved)
                                        <div class="ts-info-row justify-center mt-2 mb-3">
                                            <x-heroicon-s-calendar class="w-10 h-10" style="color: var(--accent-pink);" />
                                        </div>
                                        <button wire:click.stop="toggleReservation({{ $table->id }})" class="ts-btn-reserve cancel">
                                            Cancel Reserve
                                        </button>
                                    @else
                                        <div class="ts-info-row justify-center mt-2 mb-3">
                                            <x-heroicon-s-check-circle class="w-10 h-10" style="color: rgba(0,0,0,0.3);" />
                                        </div>
                                        <button wire:click.stop="toggleReservation({{ $table->id }})" class="ts-btn-reserve make">
                                            Reserve Table
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- RIGHT COLUMN: DIGITAL RECEIPT SIDEBAR --}}
            <div class="w-full lg:w-auto">
                @if($selectedTableData && $activeDinersList->count() > 0)
                    @php
                        $groupedOrders = $tableOrders->groupBy('status');
                        $validOrdersForBill = $tableOrders->whereIn('status', ['placed', 'accepted', 'preparing', 'ready', 'served']);
                        $runningTotal = $validOrdersForBill->sum('total_amount');
                    @endphp

                    <div class="pos-receipt">
                        <div class="pos-receipt-header">
                            <div class="flex justify-between items-start">
                                <div>
                                    <span style="color: var(--text-muted); font-size: 0.7rem; font-weight: 800; letter-spacing: 0.05em;">CURRENTLY VIEWING</span>
                                    <h3 style="color: var(--brand-blue); font-size: 1.75rem; font-weight: 900; line-height: 1; margin-top: 4px; margin-bottom: 0.5rem;">
                                        Table {{ $selectedTableData->table_number }}
                                    </h3>
                                </div>
                                <button wire:click="$set('selectedTableId', null)"
                                    style="background: transparent; border: none; cursor: pointer; color: var(--text-muted); transition: color 0.2s;"
                                    onmouseover="this.style.color='var(--accent-red)'"
                                    onmouseout="this.style.color='var(--text-muted)'">
                                    <x-heroicon-s-x-circle style="width: 28px; height: 28px;" />
                                </button>
                            </div>
                        </div>

                        <div class="pos-receipt-body pos-scroll">
                            <div style="background: rgba(255,255,255,0.3); border-radius: 8px; padding: 12px; margin-bottom: 1.5rem; border: 1px solid rgba(0,0,0,0.1);">
                                <div style="font-size: 0.7rem; font-weight: 800; color: var(--text-muted); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.05em;">
                                    Active Diners ({{ $activeDinersList->count() }}/{{ $selectedTableData->seating_capacity ?? 4 }})
                                </div>
                                <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                    @foreach($activeDinersList as $diner)
                                        <div class="customer-pill {{ $diner->is_primary ? 'host' : '' }}">
                                            @if($diner->is_primary) 👑 @else 👤 @endif 
                                            {{ $diner->customer_name }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- 👇 NEW: Add Order Button Here 👇 --}}
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                                <span style="background: var(--text-primary); color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 0.65rem; font-weight: 900; letter-spacing: 0.1em; text-transform: uppercase;">
                                    Active Orders
                                </span>
                                @if(!$pendingPayment)
                                    <button wire:click="mountAction('placeOrderAction')" class="btn-add-order">
                                        + PLACE ORDER
                                    </button>
                                @endif
                            </div>

                            <div class="flex flex-col gap-6">
                                @foreach([
                                        'placed' => 'New / Pending',
                                        'accepted' => 'Order Accepted',
                                        'preparing' => 'Cooking',
                                        'ready' => 'Ready to Serve',
                                        'served' => 'Served',
                                        'cancelled' => 'Cancelled / Rejected',
                                        'rejected' => 'Cancelled / Rejected'
                                    ] as $statusKey => $label)

                                    @if(isset($groupedOrders[$statusKey]) && $groupedOrders[$statusKey]->count() > 0)
                                        <div>
                                            <div style="font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: {{ in_array($statusKey, ['placed', 'accepted']) ? 'var(--accent-red)' : ($statusKey === 'preparing' ? 'var(--brand-orange)' : ($statusKey === 'ready' ? 'var(--brand-blue)' : (in_array($statusKey, ['cancelled', 'rejected']) ? 'var(--text-muted)' : 'var(--text-primary)'))) }}; margin-bottom: 0.75rem; border-bottom: 1.5px solid rgba(0,0,0,0.1); padding-bottom: 4px;">
                                                {{ $label }}
                                            </div>

                                            <div class="flex flex-col gap-4">
                                                @foreach($groupedOrders[$statusKey] as $order)
                                                    @php
                                                        $isHostOrder = $order->qr_session_id === $hostSessionId;
                                                        $isCancelled = in_array($statusKey, ['cancelled', 'rejected']);
                                                    @endphp

                                                    <div class="flex flex-col gap-2 p-3 rounded-lg" style="background: rgba(255,255,255,0.4); border: 1px solid rgba(0,0,0,0.1); {{ $isCancelled ? 'opacity: 0.5;' : '' }}">

                                                        <div class="flex justify-between items-center mb-1 pb-2 border-b border-dashed" style="border-color: rgba(0,0,0,0.1);">
                                                            <span style="font-size: 0.7rem; font-weight: 800; color: var(--text-muted); {{ $isCancelled ? 'text-decoration: line-through;' : '' }}">ORDER #{{ $order->id }}</span>
                                                            <div class="flex gap-2 items-center">
                                                                <span style="font-size: 0.7rem; font-weight: 800; color: {{ $isHostOrder ? 'var(--brand-orange)' : 'var(--brand-blue)' }};">
                                                                    {{ $isHostOrder ? '👑 HOST' : '👤 GUEST' }}: {{ $order->customer_name }}
                                                                </span>
                                                                {{-- 👇 NEW: Edit Button Here 👇 --}}
                                                                @if(!$isCancelled && !$pendingPayment)
                                                                    <button wire:click="mountAction('editOrderAction', { orderId: {{ $order->id }} })" class="btn-edit-order">
                                                                        EDIT
                                                                    </button>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        @if($order->notes && !$isCancelled)
                                                            <div style="color: var(--accent-red); font-size: 0.75rem; font-style: italic; font-weight: 700; background: var(--brand-red-bg); padding: 4px 8px; border-radius: 4px; border-left: 2px solid var(--accent-red);">
                                                                Note: {{ $order->notes }}
                                                            </div>
                                                        @endif

                                                        @foreach($order->items as $item)
                                                            <div class="flex justify-between items-start mt-1">
                                                                <div class="pr-4">
                                                                    <span style="color: var(--text-primary); font-size: 0.9rem; font-weight: 700; display: block; {{ $isCancelled ? 'text-decoration: line-through;' : '' }}">
                                                                        <span style="color: var(--brand-blue); margin-right: 4px;">{{ $item->quantity }}x</span>{{ $item->menuItem->name ?? $item->item_name }}
                                                                    </span>
                                                                </div>
                                                                <span style="color: var(--text-primary); font-size: 0.95rem; font-weight: 800; white-space: nowrap; {{ $isCancelled ? 'text-decoration: line-through;' : '' }}">
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

                        {{-- IN-DASHBOARD BILLING & PAYMENT CONTROL --}}
                        <div class="pos-receipt-footer">
                            @if($pendingPayment && $pendingPayment->status === 'paid')
                                <div style="background: var(--accent-green-light); border: 1px solid var(--accent-green); padding: 1rem; border-radius: 12px; text-align: center;">
                                    <x-heroicon-s-check-circle style="width: 32px; height: 32px; color: var(--accent-green); margin: 0 auto 0.5rem auto;" />
                                    <div style="color: var(--accent-green); font-weight: 900; font-size: 1.1rem; text-transform: uppercase;">Payment Settled</div>
                                    <div style="color: var(--text-primary); font-weight: bold; margin-top: 4px;">Grand Total: ₹{{ number_format($pendingPayment->amount, 2) }}</div>
                                    <div style="color: var(--text-muted); font-size: 0.75rem; margin-top: 4px;">Customer can now download PDF.</div>
                                </div>
                            @else
                                @php
                                    $taxable = max(0, $runningTotal - (float) $discountAmount);
                                    $liveTax = $taxable * ((float) $taxPercentage / 100);
                                    $liveTotal = $taxable + $liveTax;
                                @endphp

                                <div class="flex justify-between items-end mb-4">
                                    <span style="color: var(--text-muted); font-size: 1rem; font-weight: 800;">Subtotal</span>
                                    <span style="color: var(--text-primary); font-size: 1.25rem; font-weight: 900;">₹{{ number_format($runningTotal, 2) }}</span>
                                </div>

                                @if(!$pendingPayment)
                                    <div class="flex gap-3 mb-4">
                                        <div style="flex: 1;">
                                            <label style="font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Discount (₹)</label>
                                            <input type="number" wire:model.live="discountAmount" style="width: 100%; padding: 0.5rem; border-radius: 8px; border: 1.5px solid #000000; background: rgba(255,255,255,0.5); color: var(--text-primary); font-weight: bold;" placeholder="0">
                                        </div>
                                        <div style="flex: 1;">
                                            <label style="font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Tax (%)</label>
                                            <input type="number" wire:model.live="taxPercentage" style="width: 100%; padding: 0.5rem; border-radius: 8px; border: 1.5px solid #000000; background: rgba(255,255,255,0.5); color: var(--text-primary); font-weight: bold;" placeholder="0">
                                        </div>
                                    </div>

                                    <div class="flex justify-between items-end mb-6 pt-4" style="border-top: 1.5px dashed rgba(0,0,0,0.2);">
                                        <span style="color: var(--text-primary); font-size: 1.25rem; font-weight: 900;">Grand Total</span>
                                        <div style="text-align: right;">
                                            <span style="color: var(--accent-green); font-size: 2rem; font-weight: 900; line-height: 1;">₹{{ number_format($liveTotal, 2) }}</span>
                                            @if($liveTax > 0)
                                                <div style="font-size: 0.7rem; color: var(--text-muted); font-weight: bold;">Includes ₹{{ number_format($liveTax, 2) }} Tax</div>
                                            @endif
                                        </div>
                                    </div>

                                    <button wire:click="sendBillToCustomer"
                                        style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.5rem; background: var(--brand-blue); color: white; padding: 1rem; border-radius: 12px; font-weight: 900; font-size: 1rem; text-transform: uppercase; letter-spacing: 0.05em; border: 1.5px solid #000000; cursor: pointer; box-shadow: 0 4px 15px rgba(42, 71, 149, 0.3); transition: all 0.2s;"
                                        onmouseover="this.style.transform='translateY(-2px)';"
                                        onmouseout="this.style.transform='none';">
                                        <x-heroicon-s-paper-airplane style="width: 20px; height: 20px;" />
                                        Send Bill to Customer
                                    </button>
                                @else
                                    <div class="flex justify-between items-end mb-6 pt-4" style="border-top: 1.5px dashed rgba(0,0,0,0.2);">
                                        <span style="color: var(--text-primary); font-size: 1.25rem; font-weight: 900;">Grand Total</span>
                                        <span style="color: var(--accent-green); font-size: 2rem; font-weight: 900; line-height: 1;">₹{{ number_format($pendingPayment->amount, 2) }}</span>
                                    </div>

                                    <div style="background: rgba(255,255,255,0.4); border: 1px solid rgba(0,0,0,0.1); padding: 1rem; border-radius: 12px; margin-bottom: 1rem; text-align: center;">
                                        <span style="display: block; font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px;">Customer Selected Method</span>
                                        @if($pendingPayment->payment_method === 'pending')
                                            <span class="animate-pulse" style="color: var(--brand-orange); font-weight: 900; font-size: 1.1rem;">Waiting for Customer...</span>
                                        @else
                                            <span style="color: var(--brand-blue); font-weight: 900; font-size: 1.5rem; text-transform: uppercase;">{{ $pendingPayment->payment_method }}</span>
                                        @endif
                                    </div>

                                    <button wire:click="cancelPendingBill"
                                        onclick="confirm('Are you sure you want to cancel this bill? The customer will be able to order again.') || event.stopImmediatePropagation()"
                                        style="width: 100%; margin-bottom: 12px; display: flex; align-items: center; justify-content: center; gap: 0.5rem; background: transparent; color: var(--accent-red); padding: 0.75rem; border-radius: 12px; font-weight: 800; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; border: 1.5px solid var(--accent-red); cursor: pointer; transition: all 0.2s;"
                                        onmouseover="this.style.backgroundColor='var(--accent-red)'; this.style.color='white';"
                                        onmouseout="this.style.backgroundColor='transparent'; this.style.color='var(--accent-red)';">
                                        <x-heroicon-o-arrow-path style="width: 18px; height: 18px;" />
                                        Cancel Bill & Reopen Orders
                                    </button>

                                    <button wire:click="confirmPayment"
                                        style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.5rem; background: var(--accent-green); color: white; padding: 1rem; border-radius: 12px; font-weight: 900; font-size: 1rem; text-transform: uppercase; letter-spacing: 0.05em; border: 1.5px solid #000000; cursor: pointer; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3); transition: all 0.2s;"
                                        onmouseover="this.style.transform='translateY(-2px)';"
                                        onmouseout="this.style.transform='none';">
                                        <x-heroicon-s-check-circle style="width: 24px; height: 24px;" />
                                        Confirm Payment Received
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>
                @else
                    @if($this->selectedTableId)
                        @php 
                            $tableInfo = $tables->firstWhere('id', $this->selectedTableId);
                            $isRes = $tableInfo && ($tableInfo->status === 'reserved');
                        @endphp

                        <div class="pos-receipt justify-center items-center p-8 text-center" style="border: 1.5px dashed #000000;">
                            <div style="background: var(--glass-bg); padding: 1.25rem; border-radius: 50%; border: 1px solid rgba(0,0,0,0.1); margin-bottom: 1.5rem; box-shadow: var(--glass-shadow); display: flex; justify-content: center; align-items: center; margin-left: auto; margin-right: auto; width: 80px; height: 80px;">
                                <x-heroicon-o-check-badge style="width: 40px; height: 40px; color: {{ $isRes ? 'var(--accent-pink)' : 'var(--accent-green)' }};" />
                            </div>
                            <h3 style="color: var(--text-primary); font-size: 1.25rem; font-weight: 900; margin-bottom: 0.5rem;">
                                Table {{ $tableInfo->table_number ?? '' }} is {{ $isRes ? 'Reserved' : 'Empty' }}
                            </h3>
                            <p style="color: var(--text-muted); font-size: 0.85rem; font-weight: 500; line-height: 1.5; margin-bottom: 2rem;">
                                {{ $isRes ? 'This table is currently reserved for upcoming guests.' : 'This table is clean and ready for new guests.' }}
                            </p>
                            {{-- Managers can place a new order even if the table is empty! --}}
                            <button wire:click="mountAction('placeOrderAction')" class="btn-add-order" style="padding: 10px 20px; font-size: 0.85rem;">
                                + PLACE NEW ORDER
                            </button>
                        </div>
                    @else
                        <div class="pos-receipt justify-center items-center p-8 text-center" style="border: 1.5px dashed #000000;">
                            <div style="background: var(--glass-bg); padding: 1.25rem; border-radius: 50%; border: 1px solid rgba(0,0,0,0.1); margin-bottom: 1.5rem; box-shadow: var(--glass-shadow); display: flex; justify-content: center; align-items: center; margin-left: auto; margin-right: auto; width: 80px; height: 80px;">
                                <x-heroicon-o-hand-raised style="width: 40px; height: 40px; color: var(--text-muted);" />
                            </div>
                            <h3 style="color: var(--text-primary); font-size: 1.25rem; font-weight: 900; margin-bottom: 0.5rem;">
                                Select a Table</h3>
                            <p style="color: var(--text-muted); font-size: 0.85rem; font-weight: 500; line-height: 1.5;">Click
                                on any occupied table from the layout to view active orders and process checkout.</p>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
    
    {{-- Required to render the mounted actions (modals) --}}
    <x-filament-actions::modals />
</x-filament-panels::page>