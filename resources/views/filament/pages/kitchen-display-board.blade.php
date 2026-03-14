<x-filament-panels::page>
    <style>
        /* Base Board Layout */
        .kds-board * { box-sizing: border-box; font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
        .kds-board { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 24px; min-height: 80vh; padding-bottom: 40px; }
        
        /* Columns */
        .kds-col { border-radius: 16px; padding: 20px; display: flex; flex-direction: column; gap: 16px; }
        .kds-col.placed { background: #13171c; border: 1px solid #2d3748; }
        .kds-col.prep { background: #0d1624; border: 1px solid #1e3a8a; }
        .kds-col.ready { background: #0a2316; border: 1px solid #064e3b; }

        /* Column Headers */
        .kds-col-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .kds-col-title { font-size: 14px; font-weight: 700; letter-spacing: 1px; display: flex; align-items: center; gap: 8px; margin: 0; }
        .kds-dot { width: 10px; height: 10px; border-radius: 50%; }
        .kds-col-count { font-size: 12px; font-weight: 700; padding: 4px 12px; border-radius: 99px; }

        /* Cards */
        .kds-card { background: #1e2329; border-radius: 16px; padding: 20px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.5); display: flex; flex-direction: column; gap: 20px; }
        .kds-card.placed { border-left: 8px solid #4b5563; }
        .kds-card.prep { border-left: 8px solid #3b82f6; }
        .kds-card.urgent { border-left: 8px solid #f97316; }
        .kds-card.ready { background: #112a1f; border-left: 8px solid #10b981; }

        /* Card Header & Order Number */
        .kds-card-top { display: flex; justify-content: space-between; align-items: flex-start; }
        .kds-order-no { font-size: 36px; font-weight: 900; padding: 4px 20px; border-radius: 12px; letter-spacing: 2px; }
        .kds-order-no.placed { background: rgba(55, 65, 81, 0.4); color: #60a5fa; }
        .kds-order-no.prep { background: rgba(59, 130, 246, 0.2); color: #93c5fd; }
        .kds-order-no.urgent { background: rgba(249, 115, 22, 0.2); color: #fdba74; }
        .kds-order-no.ready { background: #10b981; color: #fff; }

        /* Timers */
        .kds-timer-box { text-align: right; }
        .kds-timer-label { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
        .kds-timer-val { font-size: 20px; font-family: monospace; font-weight: 700; margin: 0; line-height: 1; }

        /* Items */
        .kds-items { display: flex; flex-direction: column; gap: 14px; margin-bottom: 4px; }
        .kds-item { display: flex; align-items: flex-start; gap: 12px; }
        .kds-item-circle { width: 22px; height: 22px; border-radius: 50%; border: 2px solid #4b5563; flex-shrink: 0; margin-top: 2px; }
        .kds-item-circle.ready { background: rgba(16, 185, 129, 0.2); border-color: #10b981; display: flex; align-items: center; justify-content: center; }
        .kds-item-text { font-size: 18px; font-weight: 600; color: #e5e7eb; line-height: 1.3; }
        .kds-item-text.ready { color: #9ca3af; font-style: italic; text-decoration: line-through; text-decoration-color: #10b981; text-decoration-thickness: 2px; }
        .kds-item-notes { font-size: 14px; font-weight: 500; color: #f97316; margin-top: 4px; }

        /* Buttons */
        .kds-btn { width: 100%; border: none; border-radius: 10px; padding: 16px; font-size: 16px; font-weight: 800; color: white; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px; transition: opacity 0.2s; }
        .kds-btn:hover { opacity: 0.8; }
        .kds-btn.btn-blue { background: #1e88e5; }
        .kds-btn.btn-orange { background: #f97316; }
        .kds-btn.btn-green { background: #10b981; }

        /* Responsive fallback */
        @media (max-width: 1024px) { .kds-board { grid-template-columns: 1fr; } }
    </style>

    <div class="kds-board" wire:poll.60s>

        <div class="kds-col placed">
            <div class="kds-col-header">
                <h2 class="kds-col-title" style="color: #9ca3af;"><div class="kds-dot" style="background: #6b7280;"></div> PLACED</h2>
                <span class="kds-col-count" style="background: #1f2937; color: #d1d5db;">{{ $this->placedOrders->count() }} Orders</span>
            </div>

            @foreach($this->placedOrders as $queue)
                <div class="kds-card placed">
                    <div class="kds-card-top">
                        <div class="kds-order-no placed">#{{ str_pad($queue->order->id, 2, '0', STR_PAD_LEFT) }}</div>
                        <div class="kds-timer-box">
                            <div class="kds-timer-label" style="color: #6b7280;">Received</div>
                            <div class="kds-timer-val" style="color: #9ca3af;">{{ $queue->created_at->diff(now())->format('%I:%S') }}m</div>
                        </div>
                    </div>

                    <div class="kds-items">
                        @foreach($queue->order->items as $item)
                            <div class="kds-item">
                                <div class="kds-item-circle"></div>
                                <div>
                                    <div class="kds-item-text">{{ $item->quantity }}x {{ $item->item_name }}</div>
                                    @if($item->notes) <div class="kds-item-notes">{{ $item->notes }}</div> @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <button wire:click="updateStatus({{ $queue->id }}, 'preparing')" class="kds-btn btn-blue">
                        START PREP
                    </button>
                </div>
            @endforeach
        </div>

        <div class="kds-col prep">
            <div class="kds-col-header">
                <h2 class="kds-col-title" style="color: #60a5fa;"><div class="kds-dot" style="background: #3b82f6;"></div> PREPARING</h2>
                <span class="kds-col-count" style="background: rgba(30,58,138,0.5); color: #93c5fd; border: 1px solid #1e3a8a;">{{ $this->preparingOrders->count() }} Orders</span>
            </div>

            @foreach($this->preparingOrders as $queue)
                @php $isUrgent = $queue->created_at->diffInMinutes(now()) >= 15; @endphp
                <div class="kds-card {{ $isUrgent ? 'urgent' : 'prep' }}">
                    <div class="kds-card-top">
                        <div class="kds-order-no {{ $isUrgent ? 'urgent' : 'prep' }}">#{{ str_pad($queue->order->id, 2, '0', STR_PAD_LEFT) }}</div>
                        <div class="kds-timer-box">
                            <div class="kds-timer-label" style="color: {{ $isUrgent ? '#f97316' : '#6b7280' }};">{{ $isUrgent ? 'URGENT' : 'ELAPSED' }}</div>
                            <div class="kds-timer-val" style="color: {{ $isUrgent ? '#fdba74' : '#60a5fa' }};">{{ $queue->created_at->diff(now())->format('%I:%S') }}m</div>
                        </div>
                    </div>

                    <div class="kds-items">
                        @foreach($queue->order->items as $item)
                            <div class="kds-item">
                                <div class="kds-item-circle"></div>
                                <div>
                                    <div class="kds-item-text">{{ $item->quantity }}x {{ $item->item_name }}</div>
                                    @if($item->notes) <div class="kds-item-notes">{{ $item->notes }}</div> @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <button wire:click="updateStatus({{ $queue->id }}, 'ready')" class="kds-btn {{ $isUrgent ? 'btn-orange' : 'btn-blue' }}">
                        {{ $isUrgent ? 'PRIORITIZE (MARK READY)' : 'MARK READY' }}
                    </button>
                </div>
            @endforeach
        </div>

        <div class="kds-col ready">
            <div class="kds-col-header">
                <h2 class="kds-col-title" style="color: #34d399;"><div class="kds-dot" style="background: #10b981;"></div> READY</h2>
                <span class="kds-col-count" style="background: rgba(6,78,59,0.5); color: #6ee7b7; border: 1px solid #064e3b;">{{ $this->readyOrders->count() }} Orders</span>
            </div>

            @foreach($this->readyOrders as $queue)
                <div class="kds-card ready">
                    <div class="kds-card-top">
                        <div class="kds-order-no ready">#{{ str_pad($queue->order->id, 2, '0', STR_PAD_LEFT) }}</div>
                        <div class="kds-timer-box">
                            <div class="kds-timer-label" style="color: #10b981;">Wait Time</div>
                            <div class="kds-timer-val" style="color: #34d399;">{{ $queue->created_at->diff(now())->format('%I:%S') }}m</div>
                        </div>
                    </div>

                    <div class="kds-items">
                        @foreach($queue->order->items as $item)
                            <div class="kds-item">
                                <div class="kds-item-circle ready">
                                    <svg style="width:14px; height:14px; color:#34d399;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                                <div>
                                    <div class="kds-item-text ready">{{ $item->quantity }}x {{ $item->item_name }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- <button wire:click="updateStatus({{ $queue->id }}, 'completed')" class="kds-btn btn-green">
                        <svg style="width:20px; height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        COMPLETE ORDER
                    </button> -->
                    <div style="text-align: center; margin-top: 12px; color: #10b981; font-weight: 600; font-size: 14px;">
                        <svg style="width:16px; height:16px; display:inline; margin-bottom:-3px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                        Waiting for Waiter
                    </div>
                </div>
            @endforeach
        </div>

    </div>
</x-filament-panels::page>