<x-filament-panels::page>
    @php
        $restaurantId = auth()->user()->restaurant_id ?? null;

        $totalToday = $restaurantId
            ? \App\Models\Order::where('restaurant_id', $restaurantId)->whereDate('created_at', today())->count()
            : 0;

        $servedToday = $restaurantId
            ? \App\Models\Order::where('restaurant_id', $restaurantId)->whereDate('created_at', today())->whereIn('status', ['served', 'completed'])->count()
            : 0;

        $prepCount = $this->preparingOrders->count();
        $readyCount = $this->readyOrders->count();
        
        // Helper function to render the location badge
        if (!function_exists('renderKdsLocation')) {
            function renderKdsLocation($order) {
                $type = $order->service_type ?? 'dine_in';
                
                if ($type === 'parcel') {
                    $name = $order->parcelQrSession->parcelQrCode->name ?? 'Counter';
                    return "<div style='font-size: 10px; font-weight: 800; background: #ede9fe; color: #6d28d9; padding: 2px 8px; border-radius: 6px; margin-top: 4px; display: inline-block;'>🛍️ PARCEL ({$name})</div>";
                } elseif ($type === 'room_service') {
                    $room = $order->roomSession->room->room_number ?? '?';
                    return "<div style='font-size: 10px; font-weight: 800; background: #e0f2fe; color: #1d4ed8; padding: 2px 8px; border-radius: 6px; margin-top: 4px; display: inline-block;'>🚪 ROOM {$room}</div>";
                } else {
                    $table = $order->table->table_number ?? $order->table_number ?? '?';
                    return "<div style='font-size: 10px; font-weight: 800; background: #ffedd5; color: #b45309; padding: 2px 8px; border-radius: 6px; margin-top: 4px; display: inline-block;'>🍽️ TABLE {$table}</div>";
                }
            }
        }
    @endphp

    <style>
        html, body, .fi-layout, .fi-main, .fi-page {
            background-color: transparent !important;
            background: transparent !important;
        }

        .custom-page-bg {
            content: "";
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

        .kds-wrapper {
            --text-main: #0f172a;
            --text-sub: #475569;
            --brand-orange-primary: #f16b3f;
            --brand-orange-light: #fe9a54;
            --brand-orange-bg: rgba(241, 107, 63, 0.15);
            --brand-blue-primary: #2a4795; 
            --brand-blue-light: #456aba;
            --brand-blue-bg: rgba(69, 106, 186, 0.15);
            --brand-green: #10b981;
            --brand-green-bg: rgba(16, 185, 129, 0.15);
            --brand-red: #ef4444;
            --brand-red-bg: rgba(239, 68, 68, 0.15);
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(0,0,0,0.1); 
            --glass-shadow: 0 8px 32px rgba(42, 71, 149, 0.08);
            --glass-blur: blur(16px) saturate(140%);
        }

        .dark .kds-wrapper {
            --text-main: #f8fafc;
            --text-sub: #cbd5e1;
            --glass-bg: rgba(15, 15, 20, 0.85);
            --glass-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
            --glass-border: rgba(255,255,255,0.1);
        }

        .kds-wrapper * { box-sizing: border-box; font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif; }
        
        .kds-widgets { display: grid; grid-template-columns: repeat(1, 1fr); gap: 16px; margin-bottom: 24px; position: relative; z-index: 10; }
        @media (min-width: 768px) { .kds-widgets { grid-template-columns: repeat(4, 1fr); } }
        
        .kds-widget { 
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            border: 1.5px solid var(--glass-border);
            border-radius: 1.25rem; 
            padding: 20px; 
            box-shadow: var(--glass-shadow); 
            position: relative; overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .kds-w-title { font-family: 'Inter', sans-serif; font-size: 0.75rem; font-weight: 800; color: var(--text-sub); text-transform: uppercase; letter-spacing: 0.5px; }
        .kds-w-value { font-size: 2.4rem; font-weight: 700; margin-top: 8px; line-height: 1; }
        
        .w-blue .kds-w-value { color: var(--brand-blue-light); }
        .w-orange .kds-w-value { color: var(--brand-orange-primary); }
        .w-green .kds-w-value { color: var(--brand-green); }
        .w-purple .kds-w-value { color: var(--brand-blue-primary); }

        .kds-board { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 24px; min-height: 80vh; padding-bottom: 40px; align-items: start; position: relative; z-index: 10; }
        
        .kds-col { 
            background: rgba(255, 255, 255, 0.4); 
            backdrop-filter: blur(8px);
            border-radius: 1.25rem; padding: 16px; 
            border: 1.5px solid var(--glass-border); 
            display: flex; flex-direction: column; gap: 16px; 
        }
        .dark .kds-col { background: rgba(15, 15, 20, 0.4); }

        .kds-col-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; padding: 0 4px; }
        .kds-col-title { font-size: 16px; font-weight: 800; letter-spacing: 1px; display: flex; align-items: center; gap: 8px; margin: 0; text-transform: uppercase; }
        .kds-dot { width: 12px; height: 12px; border-radius: 50%; }
        .kds-col-count { font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 800; padding: 4px 12px; border-radius: 99px; background: var(--glass-bg); border: 1.5px solid var(--glass-border); color: var(--text-main); }

        .kds-card { 
            background: var(--glass-bg); 
            backdrop-filter: var(--glass-blur);
            border-radius: 12px; padding: 16px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.05); 
            display: flex; flex-direction: column; gap: 16px; 
            border: 1.5px solid var(--glass-border); 
            transition: transform 0.2s, box-shadow 0.2s; 
        }

        .kds-card.placed { border-top: 4px solid var(--brand-blue-light) !important; }
        .kds-card.prep { border-top: 4px solid var(--brand-orange-primary) !important; }
        .kds-card.urgent { border-top: 4px solid var(--brand-red) !important; animation: pulseBorder 2s infinite; }
        .kds-card.ready { border-top: 4px solid var(--brand-green) !important; }

        .kds-card-top { display: flex; justify-content: space-between; align-items: flex-start; }
        .kds-order-no { font-size: 24px; font-weight: 900; padding: 4px 12px; border-radius: 8px; letter-spacing: 1px; border: 1px solid var(--glass-border); line-height: 1;}
        .kds-order-no.placed { background: var(--brand-blue-bg); color: var(--brand-blue-light); }
        .kds-order-no.prep { background: var(--brand-orange-bg); color: var(--brand-orange-primary); }
        .kds-order-no.urgent { background: var(--brand-red-bg); color: var(--brand-red); }
        .kds-order-no.ready { background: var(--brand-green-bg); color: var(--brand-green); }

        .kds-timer-box { text-align: right; }
        .kds-timer-label { font-family: 'Inter', sans-serif; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; color: var(--text-sub); }
        .kds-timer-val { font-size: 18px; font-family: monospace; font-weight: 800; margin: 0; line-height: 1; color: var(--text-main); }

        .kds-items { display: flex; flex-direction: column; gap: 12px; margin-bottom: 4px; }
        .kds-item { display: flex; align-items: flex-start; gap: 10px; padding-bottom: 8px; border-bottom: 1px dashed rgba(0,0,0,0.1); }
        .dark .kds-item { border-bottom: 1px dashed rgba(255,255,255,0.1); }
        .kds-item:last-child { border-bottom: none; padding-bottom: 0; }
        .kds-item-qty { font-size: 16px; font-weight: 900; color: var(--brand-blue-light); min-width: 24px; }
        .kds-item-text { font-family: 'Inter', sans-serif; font-size: 16px; font-weight: 700; color: var(--text-main); line-height: 1.3; flex: 1; }
        .kds-item-text.ready { color: var(--text-sub); text-decoration: line-through; text-decoration-color: var(--brand-green); text-decoration-thickness: 2px; }
        .kds-item-notes { font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 700; color: var(--brand-red); margin-top: 4px; background: var(--brand-red-bg); padding: 4px 8px; border-radius: 6px; display: inline-block; border: 1px solid rgba(239, 68, 68, 0.3); }

        .kds-btn { 
            width: 100%; border: 1.5px solid var(--glass-border); border-radius: 10px; padding: 14px; 
            font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 800; color: white; 
            cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px; 
            transition: all 0.2s; text-transform: uppercase; letter-spacing: 0.5px; 
        }
        .kds-btn.btn-blue { background: linear-gradient(135deg, var(--brand-blue-primary), var(--brand-blue-light)); }
        .kds-btn.btn-orange { background: linear-gradient(135deg, var(--brand-orange-primary), var(--brand-orange-light)); }
        .kds-btn.btn-red { background: linear-gradient(135deg, #b91c1c, var(--brand-red)); }

        @keyframes pulseBorder { 0% { border-top-color: var(--brand-red); } 50% { border-top-color: #fca5a5; } 100% { border-top-color: var(--brand-red); } }
        @media (max-width: 1024px) { .kds-board { grid-template-columns: 1fr; } }
    </style>

    <div class="custom-page-bg"></div>

    <div class="kds-wrapper" wire:poll.5s> 
        <div class="kds-widgets">
            <div class="kds-widget w-blue">
                <div class="kds-w-title">Total Today</div>
                <div class="kds-w-value">{{ $totalToday }}</div>
            </div>
            <div class="kds-widget w-orange">
                <div class="kds-w-title">Preparing Now</div>
                <div class="kds-w-value">{{ $prepCount }}</div>
            </div>
            <div class="kds-widget w-green">
                <div class="kds-w-title">Ready Orders</div>
                <div class="kds-w-value">{{ $readyCount }}</div>
            </div>
            <div class="kds-widget w-purple">
                <div class="kds-w-title">Served Today</div>
                <div class="kds-w-value">{{ $servedToday }}</div>
            </div>
        </div>

        <div class="kds-board">

            {{-- PLACED ORDERS --}}
            <div class="kds-col placed">
                <div class="kds-col-header">
                    <h2 class="kds-col-title" style="color: var(--brand-blue-light);"><div class="kds-dot" style="background: var(--brand-blue-light);"></div> NEW / PLACED</h2>
                    <span class="kds-col-count">{{ $this->placedOrders->count() }} Orders</span>
                </div>

                @foreach($this->placedOrders as $queue)
                    <div class="kds-card placed">
                        <div class="kds-card-top">
                            <div>
                                <div class="kds-order-no placed">#{{ str_pad($queue->order->id, 2, '0', STR_PAD_LEFT) }}</div>
                                {!! renderKdsLocation($queue->order) !!}
                            </div>
                            <div class="kds-timer-box">
                                <div class="kds-timer-label">Received</div>
                                <div class="kds-timer-val">{{ $queue->created_at->diff(now())->format('%I:%S') }}m</div>
                            </div>
                        </div>

                        <div class="kds-items">
                            @foreach($queue->order->items as $item)
                                <div class="kds-item">
                                    <span class="kds-item-qty">{{ $item->quantity }}x</span>
                                    <div>
                                        <div class="kds-item-text">{{ $item->item_name }}</div>
                                        @if($item->notes) <div class="kds-item-notes">⚠️ {{ $item->notes }}</div> @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <button wire:click="updateStatus({{ $queue->id }}, 'preparing')" class="kds-btn btn-orange">
                            START PREP
                        </button>
                    </div>
                @endforeach
            </div>

            {{-- PREPARING ORDERS --}}
            <div class="kds-col prep">
                <div class="kds-col-header">
                    <h2 class="kds-col-title" style="color: var(--brand-orange-primary);"><div class="kds-dot" style="background: var(--brand-orange-primary);"></div> PREPARING</h2>
                    <span class="kds-col-count">{{ $prepCount }} Orders</span>
                </div>

                @foreach($this->preparingOrders as $queue)
                    @php $isUrgent = $queue->created_at->diffInMinutes(now()) >= 15; @endphp
                    <div class="kds-card {{ $isUrgent ? 'urgent' : 'prep' }}">
                        <div class="kds-card-top">
                            <div>
                                <div class="kds-order-no {{ $isUrgent ? 'urgent' : 'prep' }}">#{{ str_pad($queue->order->id, 2, '0', STR_PAD_LEFT) }}</div>
                                {!! renderKdsLocation($queue->order) !!}
                            </div>
                            <div class="kds-timer-box">
                                <div class="kds-timer-label" style="color: {{ $isUrgent ? 'var(--brand-red)' : 'var(--text-sub)' }};">{{ $isUrgent ? 'URGENT' : 'ELAPSED' }}</div>
                                <div class="kds-timer-val" style="color: {{ $isUrgent ? 'var(--brand-red)' : '' }};">{{ $queue->created_at->diff(now())->format('%I:%S') }}m</div>
                            </div>
                        </div>

                        <div class="kds-items">
                            @foreach($queue->order->items as $item)
                                <div class="kds-item">
                                    <span class="kds-item-qty" style="color: var(--brand-orange-primary);">{{ $item->quantity }}x</span>
                                    <div>
                                        <div class="kds-item-text">{{ $item->item_name }}</div>
                                        @if($item->notes) <div class="kds-item-notes">⚠️ {{ $item->notes }}</div> @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <button wire:click="updateStatus({{ $queue->id }}, 'ready')" class="kds-btn {{ $isUrgent ? 'btn-red' : 'btn-blue' }}">
                            <svg style="width:20px; height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            {{ $isUrgent ? 'PRIORITIZE (MARK READY)' : 'MARK READY' }}
                        </button>
                    </div>
                @endforeach
            </div>

            {{-- READY ORDERS --}}
            <div class="kds-col ready">
                <div class="kds-col-header">
                    <h2 class="kds-col-title" style="color: var(--brand-green);"><div class="kds-dot" style="background: var(--brand-green);"></div> READY</h2>
                    <span class="kds-col-count">{{ $readyCount }} Orders</span>
                </div>

                @foreach($this->readyOrders as $queue)
                    <div class="kds-card ready">
                        <div class="kds-card-top">
                            <div>
                                <div class="kds-order-no ready">#{{ str_pad($queue->order->id, 2, '0', STR_PAD_LEFT) }}</div>
                                {!! renderKdsLocation($queue->order) !!}
                            </div>
                            <div class="kds-timer-box">
                                <div class="kds-timer-label" style="color: var(--brand-green);">Wait Time</div>
                                <div class="kds-timer-val" style="color: var(--brand-green);">{{ $queue->created_at->diff(now())->format('%I:%S') }}m</div>
                            </div>
                        </div>

                        <div class="kds-items">
                            @foreach($queue->order->items as $item)
                                <div class="kds-item">
                                    <span class="kds-item-qty" style="color: var(--brand-green);">{{ $item->quantity }}x</span>
                                    <div>
                                        <div class="kds-item-text ready">{{ $item->item_name }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div style="text-align: center; margin-top: 8px; color: var(--brand-green); font-family: 'Inter', sans-serif; font-weight: 800; font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 6px; background: var(--brand-green-bg); padding: 10px; border-radius: 8px; border: 1px solid rgba(16, 185, 129, 0.3);">
                            <svg style="width:18px; height:18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                            WAITING FOR DELIVERY
                        </div>
                    </div>
                @endforeach
            </div>

        </div>
    </div>
</x-filament-panels::page>