<x-filament-panels::page>
    <style>
        html, body, .fi-layout, .fi-main, .fi-page { background-color: transparent !important; }
        .custom-page-bg { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-image: url("/images/bg.png") !important; opacity: 0.15 !important; z-index: -999 !important; pointer-events: none; }
        .pos-container { width: 100%; position: relative; z-index: 10; }
        .pos-layout { display: grid; grid-template-columns: minmax(0, 1fr) 380px; gap: 1.5rem; align-items: flex-start; }
        .ts-table { background: rgba(255, 255, 255, 0.45); border: 1.5px solid #000; border-radius: 1.25rem; padding: 1.25rem; display: flex; flex-direction: column; min-height: 220px; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 8px 32px rgba(0,0,0,0.08); overflow: hidden; }
        .ts-table.available { border-top: 4px dashed #10b981 !important; }
        .ts-table.occupied { border-top: 4px solid #ef4444 !important; }
        .ts-table.cleaning { border-top: 4px solid #f59e0b !important; }
        .ts-table.selected { border-color: #2a4795 !important; border-width: 2.5px !important; }
        .pos-receipt { height: calc(100vh - 6.5rem); position: sticky; top: 5.5rem; display: flex; overflow-y: auto; flex-direction: column; background: rgba(255, 255, 255, 0.45); border: 1.5px solid #000; border-radius: 1.25rem; box-shadow: 0 8px 32px rgba(0,0,0,0.08); }
        .urgent-strip { border: 1.5px solid #000; background: rgba(239, 68, 68, 0.15); backdrop-filter: blur(16px); padding: 1.25rem; border-radius: 1.25rem; margin-bottom: 1.5rem; }
        .urgent-card { border: 1.5px solid #000; border-top: 4px solid #ef4444; background: rgba(255,255,255,0.8); border-radius: 12px; }
        .pos-scroll::-webkit-scrollbar { width: 4px; height: 4px; }
        .pos-scroll::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.2); border-radius: 10px; }
    </style>

    <div class="custom-page-bg"></div>

    <div class="pos-container">
        <div class="pos-layout">
            <div class="flex flex-col w-full min-w-0">

                {{-- INCOMING ORDERS STRIP --}}
                @if($incomingOrders->count() > 0)
                    <div class="urgent-strip">
                        <h2 style="color: #ef4444; font-size: 0.9rem; font-weight: 900; text-transform: uppercase; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                            <x-heroicon-s-bell-alert style="width: 18px;" class="animate-bounce" /> Room Service Action Required ({{ $incomingOrders->count() }})
                        </h2>
                        <div class="pos-scroll flex gap-4 overflow-x-auto pb-2">
    @foreach($incomingOrders as $order)
        <div class="urgent-card min-w-[280px] flex-shrink-0 p-4 shadow-sm flex flex-col">
            <div class="flex justify-between items-start border-b pb-2 mb-3">
                <div>
                    <span style="font-weight: 900; font-size: 1.1rem; color:#0f172a;">Room {{ $order->roomSession->room->room_number ?? '?' }}</span>
                    <span style="font-size: 0.75rem; color:#64748b; display:block;">{{ $order->customer_name }}</span>
                </div>
                <span style="color: #10b981; font-weight: 900;">₹{{ number_format($order->total_amount, 0) }}</span>
            </div>
            
            {{-- 👇 ADDED ITEM DETAILS HERE 👇 --}}
            <div class="flex-grow mb-4 flex flex-col gap-1">
                @foreach($order->items as $item)
                    <div style="font-size: 0.85rem; color: #334155; display: flex; gap: 6px;">
                        <span style="color: #2a4795; font-weight: 800;">{{ $item->quantity }}x</span>
                        <span style="font-weight: 600;">{{ $item->menuItem->name ?? $item->item_name ?? 'Custom Item' }}</span>
                    </div>
                @endforeach
                
                @if($order->notes)
                    <div style="color: #ef4444; font-size: 0.75rem; font-style: italic; font-weight: 700; margin-top: 6px;">
                        📝 {{ $order->notes }}
                    </div>
                @endif
            </div>
            {{-- 👆 END ITEM DETAILS 👆 --}}

            <div class="flex gap-2 mt-auto">
                <button wire:click="acceptOrder({{ $order->id }})" style="background: #f16b3f; color: white; padding: 0.5rem; border-radius: 6px; font-weight: 800; flex: 1;">Accept</button>
                <button wire:click="rejectOrder({{ $order->id }})" style="background: #eee; padding: 0.5rem; border-radius: 6px; font-weight: 800; color: #333;">Reject</button>
            </div>
        </div>
    @endforeach
</div>
                    </div>
                @endif

                {{-- ROOM GRID --}}
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 1.25rem;">
                    @foreach($rooms as $room)
                        @php
                            $isSelected = $selectedRoomId === $room->id;
                            $badgeColor = $room->status === 'occupied' ? '#ef4444' : ($room->status === 'available' ? '#10b981' : '#f59e0b');
                        @endphp
                        <div wire:click="openRoom({{ $room->id }})" class="ts-table {{ $room->status }} {{ $isSelected ? 'selected' : '' }}">
                            <div class="flex justify-between">
                                <span style="font-size: 1.15rem; font-weight: 900; color: #0f172a;">Room {{ $room->room_number }}</span>
                                <span style="font-size: 0.6rem; font-weight: 800; padding: 4px 8px; border-radius: 12px; background: {{ $badgeColor }}15; color: {{ $badgeColor }};">{{ strtoupper($room->status) }}</span>
                            </div>
                            <div class="flex-grow flex flex-col justify-center mt-4">
                                @if($room->status === 'occupied')
                                    <span style="font-size: 0.85rem; font-weight: 700; color: #334155;">👤 {{ $room->guest_name }}</span>
                                    <span style="font-size: 0.85rem; font-weight: 700; color: #ef4444; margin-top: 5px;">₹ Due: {{ number_format($room->live_due ?? 0, 2) }}</span>
                                @elseif($room->status === 'cleaning')
                                    <span style="text-align: center; color: #f59e0b; font-weight: 900;">🧹 Housekeeping</span>
                                @else
                                    <span style="text-align: center; color: #10b981; font-weight: 900;">✔️ Available</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- RIGHT PANEL: ACTIONS --}}
            <div>
                @if($selectedRoomData)
                    <div class="pos-receipt ">
                        {{-- Header Section (Stays Fixed) --}}
                        <div style="padding: 1.5rem; border-bottom: 1.5px dashed rgba(0,0,0,0.1); flex-shrink: 0;">
                            <h3 style="color: #2a4795; font-size: 1.5rem; font-weight: 900;">Room {{ $selectedRoomData->room_number }}</h3>
                            
                            @if($selectedRoomData->status === 'occupied')
                                <div style="margin: 15px 0;">
                                    <p style="font-weight: bold; margin-bottom: 10px;">Guest: {{ $selectedRoomData->guest_name }}</p>
                                    
                                    <div style="background: white; border: 1.5px solid #000; padding: 10px; border-radius: 12px; text-align: center; margin-bottom: 15px;">
                                        <img src="{{ asset('storage/' . $selectedRoomData->qr_path) }}" style="width: 120px; margin: 0 auto;" />
                                        <button wire:click="mountAction('printStayQrAction')" style="margin-top: 10px; font-size: 0.75rem; font-weight: bold; color: #2a4795; text-decoration: underline; background: transparent; border: none; cursor: pointer;">
                                            Customize & Print Stay QR
                                        </button>
                                    </div>

                                    <button wire:click="mountAction('checkoutAction', { room_id: {{ $selectedRoomData->id }} })" style="width: 100%; background: #ef4444; color: white; padding: 0.75rem; border-radius: 8px; font-weight: bold;">
                                        Checkout Guest
                                    </button>
                                </div>
                            @elseif($selectedRoomData->status === 'cleaning')
                                <button wire:click="mountAction('markCleanAction', { room_id: {{ $selectedRoomData->id }} })" style="width: 100%; background: #3b82f6; color: white; padding: 0.75rem; border-radius: 8px; font-weight: bold; margin-top: 15px;">
                                    Ready for Next Guest
                                </button>
                            @else
                                <button wire:click="mountAction('checkInAction', { room_id: {{ $selectedRoomData->id }} })" style="width: 100%; background: #10b981; color: white; padding: 0.75rem; border-radius: 8px; font-weight: bold; margin-top: 15px;">
                                    Check In Guest
                                </button>
                            @endif
                        </div>

                        {{-- 👇 FIXED: Orders List (This section will now scroll smoothly) 👇 --}}
                        <div class="pos-receipt-body pos-scroll" style="flex-grow: 1;  padding: 1.5rem;">
                            @foreach($roomOrders as $order)
                                <div style="background: rgba(255,255,255,0.4); border: 1px solid rgba(0,0,0,0.1); padding: 12px; border-radius: 8px; margin-bottom: 12px; transition: transform 0.2s ease;">
                                    <div class="flex justify-between border-b pb-2 mb-2">
                                        <strong style="color: #0f172a;">#{{ $order->id }}</strong>
                                        <span style="color: #f16b3f; font-weight: bold;">{{ strtoupper($order->status) }}</span>
                                    </div>
                                    @foreach($order->items as $item)
                                        <div class="flex justify-between text-sm" style="margin-bottom: 4px;">
                                            <span>{{ $item->quantity }}x {{ $item->menuItem->name ?? $item->item_name ?? 'Custom Item' }}</span>
                                            <strong>₹{{ number_format($item->total_price, 0) }}</strong>
                                        </div>
                                    @endforeach
                                    @if($order->status === 'placed')
                                        <div class="flex gap-2 mt-3">
                                            <button wire:click="updateStatus({{ $order->id }}, 'accepted')" style="flex:1; background:#f16b3f; color:white; font-size:0.75rem; border-radius:4px; padding:4px;">Accept</button>
                                            <button wire:click="updateStatus({{ $order->id }}, 'rejected')" style="flex:1; background:#ef4444; color:white; font-size:0.75rem; border-radius:4px; padding:4px;">Reject</button>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        {{-- Footer Section (Stays Fixed at bottom) --}}
                        @if($selectedRoomData->status === 'occupied' && $selectedRoomData->live_due > 0)
                            <div class="pos-receipt-footer" style="flex-shrink: 0;">
                                <div class="flex justify-between font-bold text-lg mb-4">
                                    <span>Food Due</span>
                                    <span style="color: #ef4444;">₹{{ number_format($selectedRoomData->live_due, 2) }}</span>
                                </div>
                                <button wire:click="settleRoomBill" style="width: 100%; background: #10b981; color: white; padding: 1rem; border-radius: 12px; font-weight: 900;">
                                    Settle Food Bill
                                </button>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="pos-receipt" style="justify-content: center; align-items: center; text-align: center; padding: 2rem;">
                        <x-heroicon-o-home-modern style="width: 40px; color: #ccc;" />
                        <h3 style="font-weight: 900; margin-top: 10px;">Select Room</h3>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <x-filament-actions::modals />
</x-filament-panels::page>