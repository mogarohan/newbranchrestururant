<x-filament-panels::page>
    
    {{-- 🎨 CORE STYLES (Ultra-Dense Grid & Dark Mode Support) --}}
    <style>
        .rm-container { width: 100%; max-width: 100%; font-family: ui-sans-serif, system-ui, sans-serif; }
        .rm-layout { display: grid; grid-template-columns: 1fr; gap: 1.5rem; align-items: start; width: 100%; }
        @media (min-width: 1280px) { .rm-layout { grid-template-columns: 1fr 400px; } } 
        
        /* CSS Variables for Dark/Light theme */
        .rm-scope {
            --card-bg: #ffffff;
            --card-border: #e5e7eb;
            --text-main: #111827;
            --text-sub: #6b7280;
            --bg-sub: #f9fafb;
            --shadow: 0 1px 3px 0 rgba(0,0,0,0.05);
            --dense-bg: #f3f4f6;
            --brand-orange: #f97316;
            --brand-red: #ef4444;
            --brand-green: #10b981;
        }
        .dark .rm-scope {
            --card-bg: #18181b;
            --card-border: #27272a;
            --text-main: #f9fafb;
            --text-sub: #9ca3af;
            --bg-sub: #0f0f11;
            --shadow: 0 4px 6px -1px rgba(0,0,0,0.5);
            --dense-bg: #27272a;
        }

        /* Base Utilities */
        .rm-card { background-color: var(--card-bg); border: 1px solid var(--card-border); border-radius: 0.75rem; box-shadow: var(--shadow); }
        .rm-bg-sub { background-color: var(--bg-sub); border: 1px solid var(--card-border); border-radius: 0.75rem; }
        .rm-text-main { color: var(--text-main); }
        .rm-text-sub { color: var(--text-sub); }
        .rm-border { border-color: var(--card-border); }
        
        /* Layout Grids */
        .rm-stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
        @media (min-width: 1024px) { .rm-stats-grid { grid-template-columns: repeat(4, 1fr); } }

        /* 🚀 HIGH DENSITY FLOOR MAP FOR 200 TABLES */
        .rm-floor-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 0.75rem; }

        /* Scrollbars */
        .rm-scroll::-webkit-scrollbar { height: 6px; width: 4px; }
        .rm-scroll::-webkit-scrollbar-track { background: transparent; }
        .rm-scroll::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }
        .dark .rm-scroll::-webkit-scrollbar-thumb { background: #4b5563; }
        
        /* Table Card Hover */
        .rm-clickable { cursor: pointer; transition: transform 0.1s, box-shadow 0.1s, border-color 0.1s; }
        .rm-clickable:hover { transform: translateY(-2px); border-color: var(--brand-green); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .rm-selected { border: 2px solid var(--brand-green) !important; box-shadow: 0 0 0 2px rgba(16,185,129,0.2); }
    </style>

    <div wire:poll.5s class="rm-scope rm-container">
        
        <div style="margin-bottom: 1.5rem;">
            <p class="rm-text-sub" style="font-size: 0.875rem; font-weight: 600;">Real-time floor and kitchen oversight</p>
        </div>

        <div class="rm-layout">
            
            {{-- ========================================== --}}
            {{-- LEFT COLUMN: THE MASTER VIEW               --}}
            {{-- ========================================== --}}
            <div style="display: flex; flex-direction: column; gap: 1.5rem; min-width: 0;">
                
                {{-- 1. STATS CARDS (4 Parallel) --}}
                <div class="rm-stats-grid">
                    <div class="rm-card" style="padding: 1rem; border-top: 4px solid #6b7280;">
                        <span class="rm-text-sub" style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">Total Tables</span>
                        <span class="rm-text-main" style="font-size: 1.75rem; font-weight: 900; display: block; line-height: 1; margin-top: 0.25rem;">{{ $totalTables }}</span>
                    </div>
                    <div class="rm-card" style="padding: 1rem; border-top: 4px solid var(--brand-green);">
                        <span style="color: var(--brand-green); font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">Active Tables</span>
                        <span style="color: var(--brand-green); font-size: 1.75rem; font-weight: 900; display: block; line-height: 1; margin-top: 0.25rem;">{{ $activeTables }}</span>
                    </div>
                    <div class="rm-card" style="padding: 1rem; border-top: 4px solid var(--brand-orange);">
                        <span style="color: var(--brand-orange); font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">Occupancy</span>
                        <span style="color: var(--brand-orange); font-size: 1.75rem; font-weight: 900; display: block; line-height: 1; margin-top: 0.25rem;">{{ $occupancyRate }}%</span>
                    </div>
                    <div class="rm-card" style="padding: 1rem; border-top: 4px solid #3b82f6;">
                        <span style="color: #3b82f6; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">Active Diners</span>
                        <span style="color: #3b82f6; font-size: 1.75rem; font-weight: 900; display: block; line-height: 1; margin-top: 0.25rem;">{{ $activeSessions }}</span>
                    </div>
                </div>

                {{-- 2. HORIZONTAL INCOMING ORDERS STRIP (DETAILED) --}}
                @if($incomingOrders->count() > 0)
                    <div class="rm-card" style="padding: 1rem; border: 1px solid var(--brand-red); background: rgba(239, 68, 68, 0.02);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                            <h2 style="color: var(--brand-red); font-size: 1rem; font-weight: 900; display: flex; align-items: center; gap: 0.5rem;">
                                <span style="width: 10px; height: 10px; background: var(--brand-red); border-radius: 50%;" class="animate-pulse"></span>
                                Action Required ({{ $incomingOrders->count() }})
                            </h2>
                        </div>
                        
                        <div class="rm-scroll" style="display: flex; gap: 1rem; overflow-x: auto; padding-bottom: 0.5rem;">
                            @foreach($incomingOrders as $order)
                                <div class="rm-card" style="min-width: 360px; max-width: 400px; padding: 1rem; border-left: 4px solid var(--brand-red); flex-shrink: 0; display: flex; flex-direction: column;">
                                    
                                    {{-- Order Header --}}
                                    <div class="rm-border" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; border-bottom-width: 1px; padding-bottom: 0.5rem;">
                                        <div>
                                            <span class="rm-text-main" style="font-weight: 900; font-size: 1.1rem; display: block; line-height: 1;">Table {{ $order->restaurantTable->table_number ?? 'TW' }}</span>
                                            <span class="rm-text-sub" style="font-size: 0.75rem; font-weight: 700;">Order #{{ $order->id }} • {{ $order->customer_name ?? 'Guest' }}</span>
                                        </div>
                                        <div style="text-align: right;">
                                            <span style="color: var(--brand-green); font-weight: 900; font-size: 1.1rem; display: block; line-height: 1;">₹{{ number_format($order->total_amount, 0) }}</span>
                                            <span class="rm-text-sub" style="font-size: 0.7rem; font-weight: 700;">{{ $order->created_at->diffForHumans(null, true, true) }}</span>
                                        </div>
                                    </div>

                                    {{-- ORDER LEVEL NOTE --}}
                                    @if($order->notes)
                                        <div style="background: rgba(239, 68, 68, 0.1); border-left: 2px solid var(--brand-red); padding: 0.4rem 0.5rem; border-radius: 0.25rem; margin-bottom: 0.75rem;">
                                            <span style="color: var(--brand-red); font-size: 0.7rem; font-weight: 800; display: block;">Order Note:</span>
                                            <span style="color: var(--brand-red); font-size: 0.75rem; font-weight: 600; font-style: italic;">{{ $order->notes }}</span>
                                        </div>
                                    @endif

                                    {{-- DETAILED ITEMS LIST --}}
                                    <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1rem; flex-grow: 1;">
                                        @foreach($order->items as $item)
                                            <div style="display: flex; flex-direction: column; line-height: 1.2;">
                                                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                                                    <div>
                                                        {{-- Item Category --}}
                                                        <span style="background: var(--dense-bg); color: var(--text-sub); font-size: 0.55rem; font-weight: 900; padding: 0.1rem 0.3rem; border-radius: 0.2rem; margin-right: 0.25rem; vertical-align: middle;">
                                                            {{ strtoupper($item->menuItem?->category?->name ?? 'GEN') }}
                                                        </span>
                                                        {{-- Quantity & Name --}}
                                                        <span class="rm-text-main" style="font-size: 0.85rem; font-weight: 600;">
                                                            <strong>{{ $item->quantity }}x</strong> {{ $item->menuItem->name ?? $item->item_name }}
                                                        </span>
                                                    </div>
                                                </div>
                                                {{-- ITEM LEVEL NOTE --}}
                                                @if($item->notes)
                                                    <span style="color: var(--brand-red); font-size: 0.7rem; font-style: italic; font-weight: 600; padding-left: 2.2rem; margin-top: 0.15rem;">
                                                        ↳ {{ $item->notes }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>

                                    {{-- Actions --}}
                                    <div style="display: flex; gap: 0.5rem;">
                                        <button wire:click="updateStatus({{ $order->id }}, 'accepted')" style="background: var(--brand-orange); color: white; border: none; padding: 0.5rem; border-radius: 0.4rem; font-weight: 800; font-size: 0.8rem; flex: 1; cursor: pointer; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                                            Accept Order
                                        </button>                                        
                                        <button wire:click="updateStatus({{ $order->id }}, 'cancelled')" onclick="confirm('Reject this order?')" class="rm-bg-sub rm-border" style="color: var(--text-main); padding: 0.5rem 1rem; border-radius: 0.4rem; font-weight: 800; font-size: 0.8rem; cursor: pointer; border-width: 1px;">Reject</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- 3. HIGH DENSITY FLOOR MAP (The 200 Table Solution) --}}
                <div>
                    <h2 class="rm-text-main" style="font-size: 1.125rem; font-weight: 900; margin-bottom: 1rem;">Floor Overview</h2>
                    
                    <div class="rm-floor-grid">
                        @foreach($tables as $table)
                            @php
                                $isOccupied = $table->active_sessions_count > 0;
                                $isSelected = $selectedTableId === $table->id;
                            @endphp

                            <div wire:click="openTable({{ $table->id }})" 
                                 class="rm-card rm-clickable {{ $isSelected ? 'rm-selected' : '' }}" 
                                 style="padding: 0.75rem; display: flex; flex-direction: column; justify-content: space-between; height: 100px; border-top: 4px solid {{ $isOccupied ? 'var(--brand-green)' : '#9ca3af' }}; opacity: {{ $isOccupied ? '1' : '0.6' }};">
                                
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <span class="rm-text-main" style="font-size: 1.1rem; font-weight: 900; line-height: 1;">T{{ $table->table_number }}</span>
                                    @if($isOccupied)
                                        <span style="color: var(--brand-green); font-size: 0.7rem; font-weight: 800; display: flex; align-items: center; gap: 2px;">
                                            <x-heroicon-s-users style="width: 10px;"/> {{ $table->active_sessions_count }}
                                        </span>
                                    @endif
                                </div>

                                @if($isOccupied)
                                    <span class="rm-text-main" style="font-size: 1rem; font-weight: 800; margin-top: auto;">₹{{ number_format($table->total_bill, 0) }}</span>
                                    
                                    <div style="display: flex; gap: 0.25rem; margin-top: 0.25rem;">
                                        @if($table->preparing_count > 0)
                                            <span style="background: rgba(249, 115, 22, 0.1); color: var(--brand-orange); padding: 0.1rem 0.25rem; border-radius: 0.25rem; font-size: 0.6rem; font-weight: 800;">{{ $table->preparing_count }} CK</span>
                                        @endif
                                        @if($table->ready_count > 0)
                                            <span style="background: rgba(59, 130, 246, 0.1); color: #3b82f6; padding: 0.1rem 0.25rem; border-radius: 0.25rem; font-size: 0.6rem; font-weight: 800;" class="animate-pulse">{{ $table->ready_count }} RD</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="rm-text-sub" style="font-size: 0.7rem; font-weight: 700; margin-top: auto;">Free</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>

            {{-- ========================================== --}}
            {{-- RIGHT COLUMN: TABLE BREAKDOWN SIDEBAR      --}}
            {{-- ========================================== --}}
            <div class="rm-right-col">
                @if($selectedTableData)
                    @php
                        $groupedOrders = $selectedTableData->orders->groupBy('status');
                        $runningTotal = $selectedTableData->orders->sum('total_amount');
                    @endphp

                    <div class="rm-card" style="padding: 1.5rem; display: flex; flex-direction: column; height: calc(100vh - 6.5rem); position: sticky; top: 5.5rem;">
                        
                        {{-- Sidebar Header --}}
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                            <div>
                                <h3 class="rm-text-main" style="font-size: 1.5rem; font-weight: 900; line-height: 1;">
                                    Table {{ $selectedTableData->table_number }}
                                </h3>
                                <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                                    <span style="background: var(--brand-green); color: white; font-size: 0.65rem; font-weight: 800; padding: 0.15rem 0.4rem; border-radius: 0.25rem;">OCCUPIED</span>
                                    <span class="rm-text-sub" style="font-size: 0.75rem; font-weight: 700;">{{ $selectedTableData->qrSessions->count() }} Guests</span>
                                </div>
                            </div>
                            <button wire:click="$set('selectedTableId', null)" style="background: transparent; border: none; cursor: pointer;">
                                <x-heroicon-s-x-circle style="width: 24px; height: 24px; color: #9ca3af;" />
                            </button>
                        </div>

                        {{-- Order List (Scrollable Area) --}}
                        <div class="rm-scroll" style="flex-grow: 1; overflow-y: auto; padding-right: 0.5rem; display: flex; flex-direction: column; gap: 1.5rem;">
                            
                            @foreach(['preparing' => 'Cooking Now', 'ready' => 'Ready', 'served' => 'Served Items'] as $statusKey => $label)
                                @if(isset($groupedOrders[$statusKey]) && $groupedOrders[$statusKey]->count() > 0)
                                    <div>
                                        <h4 class="rm-border" style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.75rem; padding-bottom: 0.4rem; border-bottom-width: 1px; color: {{ $statusKey === 'served' ? 'var(--text-sub)' : ($statusKey === 'preparing' ? 'var(--brand-orange)' : '#3b82f6') }};">
                                            {{ $label }}
                                        </h4>
                                        
                                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                                            @foreach($groupedOrders[$statusKey] as $order)
                                                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                                    <div style="display: flex; justify-content: space-between; font-size: 0.7rem; color: var(--text-sub);">
                                                        <span style="font-weight: 800;">Order #{{ $order->id }} ({{ $order->customer_name }})</span>
                                                    </div>

                                                    {{-- ORDER LEVEL NOTE --}}
                                                    @if($order->notes)
                                                        <div style="color: var(--brand-red); font-size: 0.7rem; font-style: italic; font-weight: 700;">
                                                            * Note: {{ $order->notes }}
                                                        </div>
                                                    @endif
                                                    
                                                    @foreach($order->items as $item)
                                                        <div style="display: flex; flex-direction: column; margin-bottom: 0.25rem;">
                                                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                                                <div style="padding-right: 1rem;">
                                                                    <span style="font-size: 0.55rem; font-weight: 900; background: var(--dense-bg); color: var(--text-sub); padding: 0.1rem 0.3rem; border-radius: 0.2rem; margin-right: 0.25rem;">
                                                                        {{ strtoupper($item->menuItem?->category?->name ?? 'GEN') }}
                                                                    </span>
                                                                    <span class="rm-text-main" style="font-size: 0.85rem; font-weight: 600;">
                                                                        {{ $item->quantity }}x {{ $item->menuItem->name ?? $item->item_name }}
                                                                    </span>
                                                                </div>
                                                                <span class="rm-text-main" style="font-size: 0.85rem; font-weight: 700;">
                                                                    ₹{{ number_format($item->unit_price * $item->quantity, 0) }}
                                                                </span>
                                                            </div>
                                                            {{-- ITEM LEVEL NOTE --}}
                                                            @if($item->notes)
                                                                <span style="color: var(--brand-red); font-size: 0.7rem; font-style: italic; display: block; margin-top: 0.15rem; padding-left: 2rem;">
                                                                    ↳ {{ $item->notes }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endforeach

                        </div>

                        {{-- Checkout Bottom Action --}}
                        <div class="rm-border" style="margin-top: 1rem; padding-top: 1rem; border-top-width: 1px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1.25rem;">
                                <span class="rm-text-main" style="font-size: 1rem; font-weight: 800;">Current Total</span>
                                <span style="color: var(--brand-green); font-size: 1.75rem; font-weight: 900; line-height: 1;">₹{{ number_format($runningTotal, 2) }}</span>
                            </div>

                            <a href="{{ \App\Filament\Resources\TableBillingResource::getUrl('index') }}" 
                               style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; background: var(--brand-green); color: white; padding: 0.8rem; border-radius: 0.5rem; font-weight: 800; font-size: 1rem; text-decoration: none; transition: opacity 0.2s;" 
                               onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                                <x-heroicon-s-banknotes style="width: 20px; height: 20px;"/> Checkout Table
                            </a>
                        </div>

                    </div>
                @else
                    {{-- Empty State Sidebar --}}
                    <div class="rm-card rm-bg-sub rm-border" style="padding: 3rem 1.5rem; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; border-style: dashed; border-width: 2px; height: calc(100vh - 6.5rem); position: sticky; top: 5.5rem;">
                        <x-heroicon-o-cursor-arrow-rays class="rm-text-sub" style="width: 48px; height: 48px; margin-bottom: 1rem; opacity: 0.5;" />
                        <h3 class="rm-text-main" style="font-size: 1.125rem; font-weight: 800; margin-bottom: 0.5rem;">Select a Table</h3>
                        <p class="rm-text-sub" style="font-size: 0.875rem; font-weight: 600;">Click any table from the floor map to instantly view active orders, guests, and current bill.</p>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-filament-panels::page>