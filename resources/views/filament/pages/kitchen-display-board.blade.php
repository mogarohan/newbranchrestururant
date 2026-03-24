<x-filament-panels::page>
    @php
        // Fetching live stats directly for the widgets
        $restaurantId = auth()->user()->restaurant_id ?? null;

        $totalToday = $restaurantId
            ? \App\Models\Order::where('restaurant_id', $restaurantId)->whereDate('created_at', today())->count()
            : 0;

        $servedToday = $restaurantId
            ? \App\Models\Order::where('restaurant_id', $restaurantId)->whereDate('created_at', today())->whereIn('status', ['served', 'completed'])->count()
            : 0;

        $prepCount = $this->preparingOrders->count();
        $readyCount = $this->readyOrders->count();
    @endphp

    <style>
        /* Base Global Layout */
        .kds-wrapper * { box-sizing: border-box; font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif; }
        
        /* ==========================================
           4 WIDGETS GRID LAYOUT (NEW)
           ========================================== */
        .kds-widgets { display: grid; grid-template-columns: repeat(1, 1fr); gap: 16px; margin-bottom: 24px; }
        @media (min-width: 768px) { .kds-widgets { grid-template-columns: repeat(4, 1fr); } }
        
        .kds-widget { 
            background: #ffffff; border-radius: 16px; padding: 20px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.03); border: 1px solid #e2e8f0; 
            position: relative; overflow: hidden;
            transition: transform 0.2s ease;
        }
        .dark .kds-widget { background: #1e293b; border-color: #334155; }
        .kds-widget:hover { transform: translateY(-3px); }
        
        .kds-w-title { font-size: 13px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .kds-w-value { font-size: 36px; font-weight: 900; margin-top: 8px; line-height: 1; }
        
        .w-blue .kds-w-value { color: #3B82F6; }
        .w-orange .kds-w-value { color: #F47D20; }
        .w-green .kds-w-value { color: #10B981; }
        .w-purple .kds-w-value { color: #8B5CF6; }

        /* ==========================================
           KDS BOARD LAYOUT
           ========================================== */
        .kds-board { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 24px; min-height: 80vh; padding-bottom: 40px; align-items: start; }
        
        /* Columns */
        .kds-col { background: #f8fafc; border-radius: 16px; padding: 16px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; gap: 16px; }
        .dark .kds-col { background: #0f172a; border-color: #1e293b; }

        /* Column Headers */
        .kds-col-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; padding: 0 4px; }
        .kds-col-title { font-size: 16px; font-weight: 800; letter-spacing: 1px; display: flex; align-items: center; gap: 8px; margin: 0; }
        .kds-dot { width: 12px; height: 12px; border-radius: 50%; }
        .kds-col-count { font-size: 13px; font-weight: 800; padding: 4px 12px; border-radius: 99px; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .dark .kds-col-count { background: #1e293b; border-color: #334155; color: #e2e8f0; }

        /* Cards */
        .kds-card { background: #ffffff; border-radius: 12px; padding: 16px; box-shadow: 0 4px 10px rgba(0,0,0,0.04); display: flex; flex-direction: column; gap: 16px; border: 1px solid #e2e8f0; transition: transform 0.2s, box-shadow 0.2s; }
        .dark .kds-card { background: #1e293b; border-color: #334155; }
        .kds-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.08); }

        /* Status Tops */
        .kds-card.placed { border-top: 4px solid #3B82F6; }
        .kds-card.prep { border-top: 4px solid #F47D20; }
        .kds-card.urgent { border-top: 4px solid #ef4444; animation: pulseBorder 2s infinite; }
        .kds-card.ready { border-top: 4px solid #10b981; }

        /* Card Header & Order Number */
        .kds-card-top { display: flex; justify-content: space-between; align-items: flex-start; }
        .kds-order-no { font-size: 24px; font-weight: 900; padding: 4px 12px; border-radius: 8px; letter-spacing: 1px; }
        .kds-order-no.placed { background: rgba(59, 130, 246, 0.1); color: #3B82F6; }
        .kds-order-no.prep { background: rgba(244, 125, 32, 0.1); color: #F47D20; }
        .kds-order-no.urgent { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        .kds-order-no.ready { background: rgba(16, 185, 129, 0.1); color: #10b981; }

        /* Timers */
        .kds-timer-box { text-align: right; }
        .kds-timer-label { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; color: #64748b; }
        .kds-timer-val { font-size: 18px; font-family: monospace; font-weight: 800; margin: 0; line-height: 1; color: #0f172a; }
        .dark .kds-timer-val { color: #f8fafc; }

        /* Items */
        .kds-items { display: flex; flex-direction: column; gap: 12px; margin-bottom: 4px; }
        .kds-item { display: flex; align-items: flex-start; gap: 10px; padding-bottom: 8px; border-bottom: 1px dashed #e2e8f0; }
        .dark .kds-item { border-bottom-color: #334155; }
        .kds-item:last-child { border-bottom: none; padding-bottom: 0; }
        .kds-item-qty { font-size: 16px; font-weight: 900; color: #3B82F6; min-width: 24px; }
        .kds-item-text { font-size: 16px; font-weight: 700; color: #334155; line-height: 1.3; flex: 1; }
        .dark .kds-item-text { color: #e2e8f0; }
        .kds-item-text.ready { color: #9ca3af; text-decoration: line-through; text-decoration-color: #10b981; text-decoration-thickness: 2px; }
        .kds-item-notes { font-size: 13px; font-weight: 700; color: #ef4444; margin-top: 4px; background: rgba(239, 68, 68, 0.08); padding: 4px 8px; border-radius: 6px; display: inline-block; }

        /* Buttons */
        .kds-btn { width: 100%; border: none; border-radius: 10px; padding: 14px; font-size: 14px; font-weight: 800; color: white; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px; transition: all 0.2s; text-transform: uppercase; letter-spacing: 0.5px; }
        .kds-btn:hover { opacity: 0.9; transform: scale(0.98); }
        .kds-btn.btn-blue { background: #3B82F6; box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3); }
        .kds-btn.btn-orange { background: #F47D20; box-shadow: 0 4px 10px rgba(244, 125, 32, 0.3); }
        .kds-btn.btn-red { background: #ef4444; box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3); }

        @keyframes pulseBorder { 0% { border-top-color: #ef4444; } 50% { border-top-color: #fca5a5; } 100% { border-top-color: #ef4444; } }
        @media (max-width: 1024px) { .kds-board { grid-template-columns: 1fr; } }
    </style>

    <div class="kds-wrapper" wire:poll.5s> <div class="kds-widgets">
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

            <div class="kds-col placed">
                <div class="kds-col-header">
                    <h2 class="kds-col-title" style="color: #3B82F6;"><div class="kds-dot" style="background: #3B82F6;"></div> NEW / PLACED</h2>
                    <span class="kds-col-count" style="color: #3B82F6;">{{ $this->placedOrders->count() }} Orders</span>
                </div>

                @foreach($this->placedOrders as $queue)
                    <div class="kds-card placed">
                        <div class="kds-card-top">
                            <div class="kds-order-no placed">#{{ str_pad($queue->order->id, 2, '0', STR_PAD_LEFT) }}</div>
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

            <div class="kds-col prep">
                <div class="kds-col-header">
                    <h2 class="kds-col-title" style="color: #F47D20;"><div class="kds-dot" style="background: #F47D20;"></div> PREPARING</h2>
                    <span class="kds-col-count" style="color: #F47D20;">{{ $prepCount }} Orders</span>
                </div>

                @foreach($this->preparingOrders as $queue)
                    @php $isUrgent = $queue->created_at->diffInMinutes(now()) >= 15; @endphp
                    <div class="kds-card {{ $isUrgent ? 'urgent' : 'prep' }}">
                        <div class="kds-card-top">
                            <div class="kds-order-no {{ $isUrgent ? 'urgent' : 'prep' }}">#{{ str_pad($queue->order->id, 2, '0', STR_PAD_LEFT) }}</div>
                            <div class="kds-timer-box">
                                <div class="kds-timer-label" style="color: {{ $isUrgent ? '#ef4444' : '#64748b' }};">{{ $isUrgent ? 'URGENT' : 'ELAPSED' }}</div>
                                <div class="kds-timer-val" style="color: {{ $isUrgent ? '#ef4444' : '' }};">{{ $queue->created_at->diff(now())->format('%I:%S') }}m</div>
                            </div>
                        </div>

                        <div class="kds-items">
                            @foreach($queue->order->items as $item)
                                <div class="kds-item">
                                    <span class="kds-item-qty" style="color: #F47D20;">{{ $item->quantity }}x</span>
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

            <div class="kds-col ready">
                <div class="kds-col-header">
                    <h2 class="kds-col-title" style="color: #10b981;"><div class="kds-dot" style="background: #10b981;"></div> READY</h2>
                    <span class="kds-col-count" style="color: #10b981;">{{ $readyCount }} Orders</span>
                </div>

                @foreach($this->readyOrders as $queue)
                    <div class="kds-card ready">
                        <div class="kds-card-top">
                            <div class="kds-order-no ready">#{{ str_pad($queue->order->id, 2, '0', STR_PAD_LEFT) }}</div>
                            <div class="kds-timer-box">
                                <div class="kds-timer-label" style="color: #10b981;">Wait Time</div>
                                <div class="kds-timer-val" style="color: #10b981;">{{ $queue->created_at->diff(now())->format('%I:%S') }}m</div>
                            </div>
                        </div>

                        <div class="kds-items">
                            @foreach($queue->order->items as $item)
                                <div class="kds-item">
                                    <span class="kds-item-qty" style="color: #10b981;">{{ $item->quantity }}x</span>
                                    <div>
                                        <div class="kds-item-text ready">{{ $item->item_name }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div style="text-align: center; margin-top: 8px; color: #10b981; font-weight: 800; font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 6px; background: rgba(16, 185, 129, 0.1); padding: 10px; border-radius: 8px;">
                            <svg style="width:18px; height:18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                            WAITING FOR WAITER
                        </div>
                    </div>
                @endforeach
            </div>

        </div>
    </div>
</x-filament-panels::page>