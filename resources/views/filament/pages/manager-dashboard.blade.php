<x-filament-panels::page>
    {{-- 👇 SAB KUCH IS EK SINGLE PARENT DIV KE ANDAR HAI 👇 --}}
    <div>
        <style>
            /* ─── Ann Sathi Brand Variables ─── */
            :root {
                --ann-orange: #fe9a54;
                --ann-red: #f16b3f;
                --ann-blue: #456aba;
                --ann-dark-blue: #2a4795;
                --ann-orange-light: #fff4ec;
                --ann-red-light: #fff0eb;
                --ann-blue-light: #eef2fb;
                --ann-dark-blue-light: #e8ecf7;
                --ann-text-primary: #1e293b;
                --ann-text-secondary: #64748b;
                --ann-border: #e2e8f0;
                --ann-success: #10b981;
                --ann-warning: #f59e0b;
            }

            /* Base Resets */
            .fi-page,
            .fi-main {
                max-width: 100% !important;
                padding-top: 0 !important;
            }

            .fi-page-content {
                width: 100% !important;
            }

            .custom-page-bg {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-image: url("/images/bg.png");
                background-size: cover;
                background-position: center;
                opacity: 0.05;
                z-index: -999;
                pointer-events: none;
            }

            /* Font Styles */
            .txt-p {
                color: var(--ann-text-primary);
            }

            .txt-s {
                color: var(--ann-text-secondary);
            }

            .font-black {
                font-weight: 900;
            }

            .font-bold {
                font-weight: 700;
            }

            /* Dashboard Grid */
            .dashboard-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 1rem;
                padding-bottom: 2rem;
            }

            /* 🌟 UPDATED: FULL BORDERS FOR MAIN CARDS 🌟 */
            .ts-table {
                background: rgba(255, 255, 255, 0.9);
                backdrop-filter: blur(8px);
                border-radius: 1rem;
                padding: 1.25rem;
                display: flex;
                flex-direction: column;
                min-height: 180px;
                cursor: pointer;
                transition: all 0.2s;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            }

            .ts-table:hover {
                transform: translateY(-4px);
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            }

            .ts-table.available {
                border: 2px dashed var(--ann-success) !important;
            }

            .ts-table.occupied {
                border: 2px solid var(--ann-red) !important;
            }

            .ts-table.reserved {
                border: 2px solid var(--ann-dark-blue) !important;
            }

            .ts-table.cleaning {
                border: 2px solid var(--ann-warning) !important;
            }

            .ts-table.parcel {
                border: 2px solid var(--ann-orange) !important;
            }

            .ts-table.selected {
                border-color: var(--ann-dark-blue) !important;
                border-width: 2.5px !important;
            }

            /* Top Strips */
            .urgent-strip {
                border: 1px solid var(--ann-red);
                background: var(--ann-red-light);
                padding: 1.25rem;
                border-radius: 1rem;
                margin-bottom: 1rem;
            }

            /* 🌟 UPDATED: FULL BORDERS FOR ALL CARDS 🌟 */
            .urgent-card {
                background: white;
                border-radius: 0.75rem;
                padding: 1rem;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            }

            .room-strip {
                border: 1px solid var(--ann-blue);
                background: var(--ann-blue-light);
                padding: 1.25rem;
                border-radius: 1rem;
                margin-bottom: 1rem;
            }

            .room-card {
                background: white;
                border-radius: 0.75rem;
                padding: 1rem;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            }

            .parcel-strip {
                border: 1px solid var(--ann-orange);
                background: var(--ann-orange-light);
                padding: 1.25rem;
                border-radius: 1rem;
                margin-bottom: 1rem;
            }

            .parcel-card {
                background: white;
                border-radius: 0.75rem;
                padding: 1rem;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            }

            /* Modal Overlay - Mobile Optimized */
            .modal-overlay {
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, 0.6);
                backdrop-filter: blur(4px);
                z-index: 40;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1rem;
            }

            .modal-content {
                background: white;
                width: 100%;
                max-width: 1200px;
                max-height: 90vh;
                border-radius: 1.5rem;
                overflow: hidden;
                display: flex;
                flex-direction: column;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            }

            /* Modal Split View */
            .modal-body {
                display: flex;
                flex-direction: column;
                height: 100%;
                overflow-y: auto;
                background: #ffffff;
            }

            @media (min-width: 1024px) {
                .modal-body {
                    flex-direction: row;
                    height: 75vh;
                    overflow-y: hidden;
                }
            }

            .col-list {
                flex: 1;
                border-right: 1.5px dashed var(--ann-border);
                padding: 1.5rem;
                overflow-y: auto;
                background: #ffffff;
            }

            .col-order {
                flex: 1.5;
                border-right: 1.5px dashed var(--ann-border);
                padding: 1.5rem;
                overflow-y: auto;
                background: #f8fafc;
            }

            .col-bill {
                flex: 1;
                padding: 1.5rem;
                overflow-y: auto;
                background: var(--ann-blue-light);
            }

            .customer-pill {
                padding: 12px;
                border: 1px solid var(--ann-border);
                border-radius: 12px;
                margin-bottom: 8px;
                cursor: pointer;
                transition: all 0.2s;
                background: #ffffff;
            }

            .customer-pill.active {
                border-color: var(--ann-dark-blue);
                background: var(--ann-dark-blue-light);
                transform: translateX(4px);
            }

            /* Buttons */
            .btn-primary {
                background: var(--ann-dark-blue);
                color: #ffffff;
                border: none;
                font-weight: bold;
                cursor: pointer;
                transition: all 0.2s;
            }

            .btn-primary:hover {
                filter: brightness(0.9);
            }

            .btn-secondary {
                background: #ffffff;
                color: var(--ann-red);
                border: 1px solid var(--ann-red);
                font-weight: bold;
                cursor: pointer;
                transition: all 0.2s;
            }

            .pos-scroll::-webkit-scrollbar {
                width: 4px;
                height: 4px;
            }

            .pos-scroll::-webkit-scrollbar-thumb {
                background: rgba(0, 0, 0, 0.2);
                border-radius: 10px;
            }

            /* 🌟 ANIMATIONS FOR ALL-IN-ONE (UPDATED TO GREEN SUCCESS COLOR) 🌟 */
            @keyframes prepPulseBorder {
                0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
                70% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
                100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
            }
            .animate-pulse-border {
                animation: prepPulseBorder 1.5s infinite;
                border-color: var(--ann-success) !important;
            }
            .animate-spin {
                animation: spin 1s linear infinite;
            }
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
        </style>

        <div class="custom-page-bg"></div>

        <div>
            {{-- TOP TOGGLE MENU --}}
            @if($hasRoomsFacility)
                <div style="display: flex; justify-content: center; margin-bottom: 1.5rem;">
                    <div style="background: #ffffff; border-radius: 50px; padding: 4px; display: flex; gap: 8px; border: 1px solid var(--ann-border); box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                        <button wire:click="switchTab('tables')"
                            style="padding: 8px 24px; border-radius: 50px; font-weight: bold; font-size: 14px; border:none; cursor: pointer; {{ $currentTab == 'tables' ? 'background: var(--ann-dark-blue); color: white;' : 'background: transparent; color: var(--ann-text-secondary);' }}">Tables
                            & Parcels</button>
                        <button wire:click="switchTab('rooms')"
                            style="padding: 8px 24px; border-radius: 50px; font-weight: bold; font-size: 14px; border:none; cursor: pointer; {{ $currentTab == 'rooms' ? 'background: var(--ann-dark-blue); color: white;' : 'background: transparent; color: var(--ann-text-secondary);' }}">Room
                            Service</button>
                    </div>
                </div>
            @endif

            {{-- 🌟 ALL-IN-ONE CAFE ALERTS 🌟 --}}
            @if(($isAllInOne ?? false) && count($activeAlerts) > 0)
                <div class="urgent-strip" style="border-color: var(--ann-red); background: var(--ann-red-light); margin-bottom: 1.5rem;">
                    <h2 style="color: var(--ann-red); font-weight: 900; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px; font-size: 14px; text-transform: uppercase;">
                        <x-heroicon-s-bell-alert style="width: 20px; height: 20px;" class="animate-bounce" /> Customer Assistance Required ({{ count($activeAlerts) }})
                    </h2>
                    <div style="display: flex; gap: 1rem; overflow-x: auto; padding-bottom: 8px;" class="pos-scroll">
                        @foreach($activeAlerts as $alert)
                        <div class="urgent-card" style="min-width: 280px; flex-shrink: 0; display: flex; align-items: center; justify-content: space-between; border: 2px solid var(--ann-red);">
                            <div>
                                <span style="font-size: 10px; font-weight: bold; background: var(--ann-red); color: white; padding: 2px 6px; border-radius: 4px;">WAITER CALL</span>
                                <p style="font-weight: 900; color: var(--ann-text-primary); margin: 6px 0 2px 0; font-size: 16px;">
                                    {{ $alert['table_number'] }}
                                </p>
                                <p style="font-size: 12px; color: var(--ann-text-secondary); margin:0; font-weight: bold;">
                                    👤 {{ $alert['customer_name'] }} • ⏰ {{ $alert['time'] }}
                                </p>
                            </div>
                            <button wire:click="resolveAlert('{{ $alert['id'] }}')" style="background: var(--ann-success); border: none; width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2);">
                                <x-heroicon-s-check style="width: 24px; height: 24px; color: white;" />
                            </button>
                        </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- DYNAMIC ACTIVE TABLE ORDERS STRIP --}}
            @if($incomingTableOrders->count() > 0)
                <div class="urgent-strip" style="background: transparent; border: 1px dashed var(--ann-border);">
                    <h2 style="color: var(--ann-dark-blue); font-weight: 900; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px; font-size: 14px; text-transform: uppercase;">
                        <x-heroicon-s-clipboard-document-list style="width: 20px; height: 20px;" /> Active Table Orders ({{ $incomingTableOrders->count() }})
                    </h2>
                    <div style="display: flex; gap: 1rem; overflow-x: auto; padding-bottom: 8px;" class="pos-scroll">
                        @foreach($incomingTableOrders as $order)
                            @php
                                $oStatus = $order->status;
                                $isAnimating = false;
                                
                                // 🌟 MATCHING COLORS FOR BORDERS AND BUTTONS 🌟
                                if ($oStatus === 'ready') {
                                    $cBorder = 'var(--ann-blue)'; // Matches SERVE CUSTOMER button
                                    $cLabel = '🛎️ READY';
                                } elseif ($oStatus === 'preparing') {
                                    $cBorder = 'var(--ann-success)'; // Matches MARK READY button
                                    $cLabel = 'PREPARING';
                                    $isAnimating = true;
                                } elseif (in_array($oStatus, ['accepted', 'partial_accepted'])) {
                                    $cBorder = 'var(--ann-orange)'; // Matches START PREPARING button
                                    $cLabel = '📋 ACCEPTED';
                                } else {
                                    $cBorder = 'var(--ann-dark-blue)'; // Matches ACCEPT button
                                    $cLabel = '🚨 NEW ORDER';
                                }

                                // 🌟 FORMATTING TABLE NUMBER 🌟
                                $rawOrderNum = $order->restaurantTable->table_number ?? '?';
                                $cleanOrderNum = str_replace(['Table-', 'Table - ', 'Table ', 'T-', 't-'], '', $rawOrderNum);
                                $finalOrderNum = is_numeric(trim($cleanOrderNum)) ? sprintf('%02d', trim($cleanOrderNum)) : trim($cleanOrderNum);
                            @endphp
                            <div class="urgent-card {{ $isAnimating ? 'animate-pulse-border' : '' }}"
                                style="min-width: 280px; flex-shrink: 0; display: flex; flex-direction: column; border: 2px solid {{ $cBorder }}; background: white; transition: all 0.3s;">
                                <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed {{ $cBorder }}; padding-bottom: 8px; margin-bottom: 8px;">
                                    <div>
                                        <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 10px; font-weight: bold; background: {{ $cBorder }}; color: white; padding: 2px 6px; border-radius: 4px;">
                                            @if($isAnimating)
                                                <svg class="animate-spin" style="width:12px; height:12px; color:white;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            @endif
                                            {{ $cLabel }}
                                        </span>
                                        <p style="font-weight: 900; color: var(--ann-text-primary); margin: 6px 0 0 0;">Table-{{ $finalOrderNum }}</p>
                                        <p style="font-size: 12px; color: var(--ann-text-secondary); margin:0; margin-top:2px;">{{ $order->customer_name }} • #{{ $order->id }}</p>
                                    </div>
                                    <p style="font-weight: 900; color: {{ $cBorder }}; margin:0;">₹{{ number_format($order->total_amount, 0) }}</p>
                                </div>
                                <div style="flex-grow: 1; margin-bottom: 12px;">
                                    @foreach($order->items as $item)
                                        <p style="font-size: 14px; color: var(--ann-text-primary); margin:0; margin-bottom:2px; {{ $oStatus === 'ready' ? 'text-decoration: line-through; color: var(--ann-text-secondary);' : '' }}">
                                            <strong style="color: {{ $cBorder }};">{{ $item->quantity }}x</strong> {{ $item->menuItem->name ?? $item->item_name }}
                                        </p>
                                    @endforeach
                                </div>
                                <div style="margin-top: auto;">
                                    @if($oStatus === 'placed')
                                        <div style="display: flex; gap: 8px;">
                                            <button wire:click="updateStatus({{ $order->id }}, 'accepted')" class="btn-primary" style="flex: 1; padding: 8px; border-radius: 8px; background: {{ $cBorder }}; border: 1px solid {{ $cBorder }};">Accept</button>
                                            <button wire:click="updateStatus({{ $order->id }}, 'rejected')" onclick="confirm('Reject this order?') || event.stopImmediatePropagation()" class="btn-secondary" style="padding: 8px 16px; border-radius: 8px; border-color: var(--ann-red); color: var(--ann-red);">Reject</button>
                                        </div>
                                    @elseif(in_array($oStatus, ['accepted', 'partial_accepted']))
                                        <button wire:click="updateStatus({{ $order->id }}, 'preparing')" style="width: 100%; background: {{ $cBorder }}; color: white; border: none; padding: 10px; border-radius: 8px; font-weight: bold; font-size: 14px; display: flex; justify-content: center; align-items: center; gap: 6px; cursor: pointer;">
                                            <x-heroicon-s-fire style="width: 18px; height: 18px;" /> START PREPARING
                                        </button>
                                    @elseif($oStatus === 'preparing')
                                        <button wire:click="updateStatus({{ $order->id }}, 'ready')" style="width: 100%; background: {{ $cBorder }}; color: white; border: none; padding: 10px; border-radius: 8px; font-weight: bold; font-size: 14px; display: flex; justify-content: center; align-items: center; gap: 6px; cursor: pointer;">
                                            <x-heroicon-s-check-circle style="width: 18px; height: 18px;" /> MARK READY
                                        </button>
                                    @elseif($oStatus === 'ready')
                                        <button wire:click="updateStatus({{ $order->id }}, 'served')" style="width: 100%; background: {{ $cBorder }}; color: white; border: none; padding: 10px; border-radius: 8px; font-weight: bold; font-size: 14px; display: flex; justify-content: center; align-items: center; gap: 6px; cursor: pointer;">
                                            <x-heroicon-s-arrow-right-circle style="width: 18px; height: 18px;" /> SERVE CUSTOMER
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- DYNAMIC ACTIVE ROOM ORDERS STRIP --}}
            @if($incomingRoomOrders->count() > 0)
                <div class="room-strip" style="background: transparent; border: 1px dashed var(--ann-blue);">
                    <h2 style="color: var(--ann-dark-blue); font-weight: 900; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px; font-size: 14px; text-transform: uppercase;">
                        <x-heroicon-s-clipboard-document-list style="width: 20px; height: 20px;" /> Active Room Orders ({{ $incomingRoomOrders->count() }})
                    </h2>
                    <div style="display: flex; gap: 1rem; overflow-x: auto; padding-bottom: 8px;" class="pos-scroll">
                        @foreach($incomingRoomOrders as $order)
                            @php
                                $oStatus = $order->status;
                                $isAnimating = false;
                                
                                // 🌟 MATCHING COLORS FOR BORDERS AND BUTTONS 🌟
                                if ($oStatus === 'ready') {
                                    $cBorder = 'var(--ann-blue)';
                                    $cLabel = '🛎️ READY';
                                } elseif ($oStatus === 'preparing') {
                                    $cBorder = 'var(--ann-success)';
                                    $cLabel = 'PREPARING';
                                    $isAnimating = true;
                                } elseif (in_array($oStatus, ['accepted', 'partial_accepted'])) {
                                    $cBorder = 'var(--ann-orange)';
                                    $cLabel = '📋 ACCEPTED';
                                } else {
                                    $cBorder = 'var(--ann-dark-blue)';
                                    $cLabel = '🚨 NEW ORDER';
                                }
                            @endphp
                            <div class="room-card {{ $isAnimating ? 'animate-pulse-border' : '' }}"
                                style="min-width: 280px; flex-shrink: 0; display: flex; flex-direction: column; border: 2px solid {{ $cBorder }}; background: white; transition: all 0.3s;">
                                <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed {{ $cBorder }}; padding-bottom: 8px; margin-bottom: 8px;">
                                    <div>
                                        <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 10px; font-weight: bold; background: {{ $cBorder }}; color: white; padding: 2px 6px; border-radius: 4px;">
                                            @if($isAnimating)
                                                <svg class="animate-spin" style="width:12px; height:12px; color:white;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            @endif
                                            {{ $cLabel }}
                                        </span>
                                        <p style="font-weight: 900; color: var(--ann-text-primary); margin: 6px 0 0 0;">Room {{ $order->roomSession->room->room_number ?? '?' }}</p>
                                        <p style="font-size: 12px; color: var(--ann-text-secondary); margin:0; margin-top:2px;">{{ $order->customer_name }} • #{{ $order->id }}</p>
                                    </div>
                                    <p style="font-weight: 900; color: {{ $cBorder }}; margin:0;">₹{{ number_format($order->total_amount, 0) }}</p>
                                </div>
                                <div style="flex-grow: 1; margin-bottom: 12px;">
                                    @foreach($order->items as $item)
                                        <p style="font-size: 14px; color: var(--ann-text-primary); margin:0; margin-bottom:2px; {{ $oStatus === 'ready' ? 'text-decoration: line-through; color: var(--ann-text-secondary);' : '' }}">
                                            <strong style="color: {{ $cBorder }};">{{ $item->quantity }}x</strong> {{ $item->menuItem->name ?? $item->item_name }}
                                        </p>
                                    @endforeach
                                </div>
                                <div style="margin-top: auto;">
                                    @if($oStatus === 'placed')
                                        <div style="display: flex; gap: 8px;">
                                            <button wire:click="updateStatus({{ $order->id }}, 'accepted')" class="btn-primary" style="flex: 1; padding: 8px; border-radius: 8px; background: {{ $cBorder }}; border: 1px solid {{ $cBorder }};">Accept</button>
                                            <button wire:click="updateStatus({{ $order->id }}, 'rejected')" onclick="confirm('Reject this order?') || event.stopImmediatePropagation()" class="btn-secondary" style="padding: 8px 16px; border-radius: 8px; border-color: var(--ann-red); color: var(--ann-red);">Reject</button>
                                        </div>
                                    @elseif(in_array($oStatus, ['accepted', 'partial_accepted']))
                                        <button wire:click="updateStatus({{ $order->id }}, 'preparing')" style="width: 100%; background: {{ $cBorder }}; color: white; border: none; padding: 10px; border-radius: 8px; font-weight: bold; font-size: 14px; display: flex; justify-content: center; align-items: center; gap: 6px; cursor: pointer;">
                                            <x-heroicon-s-fire style="width: 18px; height: 18px;" /> START PREPARING
                                        </button>
                                    @elseif($oStatus === 'preparing')
                                        <button wire:click="updateStatus({{ $order->id }}, 'ready')" style="width: 100%; background: {{ $cBorder }}; color: white; border: none; padding: 10px; border-radius: 8px; font-weight: bold; font-size: 14px; display: flex; justify-content: center; align-items: center; gap: 6px; cursor: pointer;">
                                            <x-heroicon-s-check-circle style="width: 18px; height: 18px;" /> MARK READY
                                        </button>
                                    @elseif($oStatus === 'ready')
                                        <button wire:click="updateStatus({{ $order->id }}, 'served')" style="width: 100%; background: {{ $cBorder }}; color: white; border: none; padding: 10px; border-radius: 8px; font-weight: bold; font-size: 14px; display: flex; justify-content: center; align-items: center; gap: 6px; cursor: pointer;">
                                            <x-heroicon-s-arrow-right-circle style="width: 18px; height: 18px;" /> SERVE CUSTOMER
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- DYNAMIC ACTIVE PARCEL STRIP --}}
            @if(isset($parcelOrders) && $parcelOrders->count() > 0)
                <div class="parcel-strip" style="background: transparent; border: 1px dashed var(--ann-orange);">
                    <h2 style="color: var(--ann-orange); font-weight: 900; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px; font-size: 14px; text-transform: uppercase;">
                        <x-heroicon-s-shopping-bag style="width: 20px; height: 20px;" /> Active Parcels ({{ $parcelOrders->count() }})
                    </h2>
                    <div style="display: flex; gap: 1rem; overflow-x: auto; padding-bottom: 8px;" class="pos-scroll">
                        @foreach($parcelOrders as $order)
                            @php
                                $oStatus = $order->status;
                                $isAnimating = false;
                                
                                // 🌟 MATCHING COLORS FOR BORDERS AND BUTTONS 🌟
                                if ($oStatus === 'ready') {
                                    $cBorder = 'var(--ann-blue)';
                                    $cLabel = '🛎️ READY';
                                } elseif ($oStatus === 'preparing') {
                                    $cBorder = 'var(--ann-success)';
                                    $cLabel = 'PREPARING';
                                    $isAnimating = true;
                                } elseif (in_array($oStatus, ['accepted', 'partial_accepted'])) {
                                    $cBorder = 'var(--ann-orange)';
                                    $cLabel = '📋 ACCEPTED';
                                } else {
                                    $cBorder = 'var(--ann-dark-blue)';
                                    $cLabel = '🚨 NEW ORDER';
                                }
                            @endphp
                            <div class="parcel-card {{ $isAnimating ? 'animate-pulse-border' : '' }}"
                                style="min-width: 280px; flex-shrink: 0; display: flex; flex-direction: column; border: 2px solid {{ $cBorder }}; background: white; transition: all 0.3s;">
                                <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed {{ $cBorder }}; padding-bottom: 8px; margin-bottom: 8px;">
                                    <div>
                                        <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 10px; font-weight: bold; background: {{ $cBorder }}; color: white; padding: 2px 6px; border-radius: 4px;">
                                            @if($isAnimating)
                                                <svg class="animate-spin" style="width:12px; height:12px; color:white;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            @endif
                                            {{ $cLabel }}
                                        </span>
                                        <p style="font-weight: 900; color: var(--ann-text-primary); margin: 6px 0 0 0;">{{ $order->parcelQrSession?->parcelQrCode?->name ?? 'PARCEL' }}</p>
                                        <p style="font-size: 12px; color: var(--ann-text-secondary); margin:0; margin-top:2px;">{{ $order->customer_name }} • #{{ $order->id }}</p>
                                    </div>
                                    <p style="font-weight: 900; color: {{ $cBorder }}; margin:0;">₹{{ number_format($order->total_amount, 0) }}</p>
                                </div>
                                <div style="flex-grow: 1; margin-bottom: 12px;">
                                    @foreach($order->items as $item)
                                        <p style="font-size: 14px; color: var(--ann-text-primary); margin:0; margin-bottom:2px; {{ $oStatus === 'ready' ? 'text-decoration: line-through; color: var(--ann-text-secondary);' : '' }}">
                                            <strong style="color: {{ $cBorder }};">{{ $item->quantity }}x</strong> {{ $item->item_name }}
                                        </p>
                                    @endforeach
                                </div>
                                <div style="margin-top: auto;">
                                    @if($oStatus === 'placed')
                                        <div style="display: flex; gap: 8px;">
                                            <button wire:click="updateStatus({{ $order->id }}, 'accepted')" class="btn-primary" style="flex: 1; padding: 8px; border-radius: 8px; background: {{ $cBorder }}; border: 1px solid {{ $cBorder }};">Accept</button>
                                            <button wire:click="updateStatus({{ $order->id }}, 'rejected')" onclick="confirm('Reject this order?') || event.stopImmediatePropagation()" class="btn-secondary" style="padding: 8px 16px; border-radius: 8px; border-color: var(--ann-red); color: var(--ann-red);">Reject</button>
                                        </div>
                                    @elseif(in_array($oStatus, ['accepted', 'partial_accepted']))
                                        <button wire:click="updateStatus({{ $order->id }}, 'preparing')" style="width: 100%; background: {{ $cBorder }}; color: white; border: none; padding: 10px; border-radius: 8px; font-weight: bold; font-size: 14px; display: flex; justify-content: center; align-items: center; gap: 6px; cursor: pointer;">
                                            <x-heroicon-s-fire style="width: 18px; height: 18px;" /> START PREPARING
                                        </button>
                                    @elseif($oStatus === 'preparing')
                                        <button wire:click="updateStatus({{ $order->id }}, 'ready')" style="width: 100%; background: {{ $cBorder }}; color: white; border: none; padding: 10px; border-radius: 8px; font-weight: bold; font-size: 14px; display: flex; justify-content: center; align-items: center; gap: 6px; cursor: pointer;">
                                            <x-heroicon-s-check-circle style="width: 18px; height: 18px;" /> MARK READY
                                        </button>
                                    @elseif($oStatus === 'ready')
                                        <button wire:click="updateStatus({{ $order->id }}, 'served')" style="width: 100%; background: {{ $cBorder }}; color: white; border: none; padding: 10px; border-radius: 8px; font-weight: bold; font-size: 14px; display: flex; justify-content: center; align-items: center; gap: 6px; cursor: pointer;">
                                            <x-heroicon-s-arrow-right-circle style="width: 18px; height: 18px;" /> SERVE CUSTOMER
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- MAIN GRID --}}
            <div class="dashboard-grid">
                @if($currentTab === 'tables')
                    {{-- Parcel Counters --}}
                    @foreach($parcelCounters as $counter)
                        <div wire:click="openParcelCounter({{ $counter->id }})"
                            class="ts-table parcel {{ $selectedParcelCounterId === $counter->id ? 'selected' : '' }}">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                                <div>
                                    <h3 style="font-weight: 900; font-size: 18px; color: var(--ann-orange); margin:0;">🛍️
                                        {{ $counter->name }}</h3>
                                    <p style="font-size: 12px; font-weight: bold; color: var(--ann-text-secondary); text-transform: uppercase; margin:0; margin-top: 4px;">
                                        Customers: {{ $counter->active_sessions_count }}</p>
                                </div>
                                <span style="font-size: 10px; font-weight: bold; padding: 4px 8px; border-radius: 8px; background: var(--ann-orange-light); color: var(--ann-orange);">PARCEL QUEUE</span>
                            </div>
                            <div style="margin-top: auto;">
                                <p style="font-size: 14px; font-weight: bold; color: var(--ann-text-primary); margin:0; margin-bottom: 4px;">
                                    🛒 {{ $counter->live_orders_count ?? 0 }} Items</p>
                                <p style="font-size: 14px; font-weight: bold; color: var(--ann-text-primary); margin:0;">💰
                                    Total: ₹{{ number_format($counter->live_subtotal ?? 0, 2) }}</p>

                                {{-- 👇 UPI QR CODE FOR PARCEL DASHBOARD CARD 👇 --}}
                                @if(isset($counter->pending_payment) && $counter->pending_payment && !empty($upiId))
                                    <div style="margin-top: 10px; background: white; padding: 8px; border-radius: 8px; text-align: center; border: 2px dashed var(--ann-success);">
                                        <p style="font-size: 11px; font-weight: 900; color: var(--ann-success); margin: 0 0 6px 0;">
                                            PAY ₹{{ number_format($counter->pending_payment->amount, 0) }}</p>
                                        <div style="display: flex; justify-content: center;">
                                            {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(90)->margin(0)->generate("upi://pay?pa={$upiId}&pn=" . urlencode($restaurantName) . "&am={$counter->pending_payment->amount}&cu=INR&tr={$counter->pending_payment->transaction_reference}") !!}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    {{-- Tables --}}
                    @foreach($tables as $table)
                        @php
                            $isOccupied = $table->active_sessions_count > 0;
                            $isReserved = !$isOccupied && (($table->status ?? '') === 'reserved' || ($table->is_reserved ?? false));
                            
                            // 🌟 FORMATTING TABLE NUMBER FOR DASHBOARD CARDS 🌟
                            $rawNum = $table->table_number;
                            $cleanNum = str_replace(['Table-', 'Table - ', 'Table ', 'T-', 't-'], '', $rawNum);
                            $finalNum = is_numeric(trim($cleanNum)) ? sprintf('%02d', trim($cleanNum)) : trim($cleanNum);
                        @endphp
                        <div wire:click="openTable({{ $table->id }})"
                            class="ts-table {{ $isOccupied ? 'occupied' : ($isReserved ? 'reserved' : 'available') }} {{ $selectedTableId === $table->id ? 'selected' : '' }}">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                                <div>
                                    <h3 style="font-weight: 900; font-size: 18px; color: var(--ann-text-primary); margin:0;">
                                        Table-{{ $finalNum }}
                                    </h3>
                                    <p style="font-size: 12px; font-weight: bold; color: var(--ann-text-secondary); text-transform: uppercase; margin:0; margin-top: 4px;">
                                        Capacity: {{ $table->seating_capacity ?? 4 }}</p>
                                </div>
                                @if($isOccupied) <span style="font-size: 10px; font-weight: bold; padding: 4px 8px; border-radius: 8px; background: var(--ann-red-light); color: var(--ann-red);">OCCUPIED</span>
                                @elseif($isReserved) <span style="font-size: 10px; font-weight: bold; padding: 4px 8px; border-radius: 8px; background: var(--ann-blue-light); color: var(--ann-dark-blue);">RESERVED</span>
                                @else <span style="font-size: 10px; font-weight: bold; padding: 4px 8px; border-radius: 8px; background: #d1fae5; color: var(--ann-success);">AVAILABLE</span>
                                @endif
                            </div>
                            <div style="margin-top: auto;">
                                @if($isOccupied)
                                    <p style="font-size: 14px; font-weight: bold; color: var(--ann-text-primary); margin:0; margin-bottom: 4px;">
                                        👤 Split Bills: {{ $table->active_sessions_count }}</p>
                                    <p style="font-size: 14px; font-weight: bold; color: var(--ann-red); margin:0;">₹ Due:
                                        {{ number_format($table->live_due ?? 0, 2) }}</p>
                                @elseif($isReserved)
                                    <p style="text-align: center; color: var(--ann-dark-blue); font-weight: bold; margin:0;">
                                        <x-heroicon-s-calendar style="width: 32px; height: 32px; margin: 0 auto;" /></p>
                                @else
                                    <p style="text-align: center; color: var(--ann-success); font-weight: bold; margin:0;">
                                        <x-heroicon-s-check-circle style="width: 32px; height: 32px; margin: 0 auto;" /></p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endif

                @if($currentTab === 'rooms')
                    @foreach($rooms as $room)
                        <div wire:click="openRoom({{ $room->id }})"
                            class="ts-table {{ $room->status }} {{ $selectedRoomId === $room->id ? 'selected' : '' }}">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                                <h3 style="font-weight: 900; font-size: 18px; color: var(--ann-text-primary); margin:0;">Room
                                    {{ $room->room_number }}</h3>
                                @if($room->status === 'occupied') <span style="font-size: 10px; font-weight: bold; padding: 4px 8px; border-radius: 8px; background: var(--ann-red-light); color: var(--ann-red);">OCCUPIED</span>
                                @elseif($room->status === 'cleaning') <span style="font-size: 10px; font-weight: bold; padding: 4px 8px; border-radius: 8px; background: #fef3c7; color: var(--ann-warning);">CLEANING</span>
                                @else <span style="font-size: 10px; font-weight: bold; padding: 4px 8px; border-radius: 8px; background: #d1fae5; color: var(--ann-success);">AVAILABLE</span>
                                @endif
                            </div>
                            <div style="margin-top: auto; text-align: center;">
                                @if($room->status === 'occupied')
                                    <p style="font-size: 14px; font-weight: bold; color: var(--ann-text-primary); margin:0; margin-bottom: 4px;">
                                        👤 {{ $room->guest_name }}</p>
                                    <p style="font-size: 14px; font-weight: bold; color: var(--ann-red); margin:0;">₹ Due:
                                        {{ number_format($room->live_due ?? 0, 2) }}</p>

                                    {{-- 👇 UPI QR CODE FOR ROOM DASHBOARD CARD 👇 --}}
                                    @if(isset($room->pending_payment) && $room->pending_payment && !empty($upiId))
                                        <div style="margin-top: 10px; background: white; padding: 8px; border-radius: 8px; text-align: center; border: 2px dashed var(--ann-success);">
                                            <p style="font-size: 11px; font-weight: 900; color: var(--ann-success); margin: 0 0 6px 0;">
                                                PAY ₹{{ number_format($room->pending_payment->amount, 0) }}</p>
                                            <div style="display: flex; justify-content: center;">
                                                {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(90)->margin(0)->generate("upi://pay?pa={$upiId}&pn=" . urlencode($restaurantName) . "&am={$room->pending_payment->amount}&cu=INR&tr={$room->pending_payment->transaction_reference}") !!}
                                            </div>
                                        </div>
                                    @endif

                                @elseif($room->status === 'cleaning')
                                    <p style="color: var(--ann-warning); font-weight: 900; margin:0;">🧹 Housekeeping</p>
                                @else
                                    <p style="color: var(--ann-success); font-weight: 900; margin:0;">✔️ Ready</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        {{-- 🌟 UNIFIED MODAL ENGINE 🌟 --}}
        @if($selectedTableId || $selectedParcelCounterId || $selectedRoomId)
            <div class="modal-overlay" wire:click.self="closeReceiptModal">
                <div class="modal-content">

                    {{-- Header --}}
                    <div style="padding: 1.5rem; border-bottom: 1px solid var(--ann-border); display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <span style="font-size: 10px; font-weight: bold; color: var(--ann-text-secondary); text-transform: uppercase; letter-spacing: 1px;">MANAGING</span>
                            <h3 style="font-size: 24px; font-weight: 900; color: var(--ann-dark-blue); margin: 0; margin-top: 4px; line-height: 1;">
                                @if($selectedParcelCounterId) 🛍️ {{ $selectedEntityData->name ?? 'Parcel Queue' }}
                                @elseif($selectedRoomId) 🚪 Room {{ $selectedEntityData->room_number ?? '?' }}
                                @else 
                                    @php
                                        // 🌟 FORMATTING TABLE NUMBER FOR MODAL 🌟
                                        $rawModalNum = $selectedEntityData->table_number ?? '?';
                                        $cleanModalNum = str_replace(['Table-', 'Table - ', 'Table ', 'T-', 't-'], '', $rawModalNum);
                                        $finalModalNum = is_numeric(trim($cleanModalNum)) ? sprintf('%02d', trim($cleanModalNum)) : trim($cleanModalNum);
                                    @endphp
                                    🍽️ Table-{{ $finalModalNum }}
                                @endif
                            </h3>
                        </div>
                        <button wire:click="closeReceiptModal" style="background: var(--ann-border); border: none; border-radius: 50%; padding: 8px; cursor: pointer;">
                            <x-heroicon-s-x-mark style="width: 24px; height: 24px; color: var(--ann-text-secondary);" />
                        </button>
                    </div>

                    <div class="modal-body">

                        {{-- LEFT COLUMN: CUSTOMERS OR ROOM INFO --}}
                        <div class="modal-col col-list pos-scroll">
                            @if($selectedRoomId)
                                <span style="display: block; font-size: 10px; font-weight: bold; color: var(--ann-text-secondary); text-transform: uppercase; margin-bottom: 16px;">Room Management</span>
                                @if($selectedEntityData->status === 'occupied')
                                    <div style="background: var(--ann-blue-light); padding: 16px; border-radius: 12px; border: 1px solid var(--ann-blue); text-align: center; margin-bottom: 16px;">
                                        <p style="font-weight: bold; color: var(--ann-dark-blue); margin:0; margin-bottom: 8px;">
                                            Guest: {{ $selectedEntityData->guest_name }}</p>
                                        @if($selectedEntityData->qr_path)
                                            <img src="{{ asset('storage/' . $selectedEntityData->qr_path) }}"
                                                style="width: 120px; height: 120px; margin: 0 auto; border-radius: 8px; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.1);" />
                                            <button wire:click="mountAction('printStayQrAction')"
                                                style="margin-top: 12px; font-size: 12px; font-weight: bold; color: var(--ann-blue); text-decoration: underline; background: none; border: none; cursor: pointer;">Customize & Print QR</button>
                                        @endif
                                    </div>
                                    <button wire:click="mountAction('checkoutAction', { room_id: {{ $selectedRoomId }} })" class="btn-primary" style="width: 100%; background: var(--ann-red); padding: 12px; border-radius: 12px; font-size: 14px;">Checkout Guest</button>
                                @elseif($selectedEntityData->status === 'cleaning')
                                    <button wire:click="mountAction('markCleanAction', { room_id: {{ $selectedRoomId }} })" class="btn-primary" style="width: 100%; background: var(--ann-blue); padding: 12px; border-radius: 12px; font-size: 14px;">Ready for Next Guest</button>
                                @else
                                    <button wire:click="mountAction('checkInAction', { room_id: {{ $selectedRoomId }} })" class="btn-primary" style="width: 100%; background: var(--ann-success); padding: 12px; border-radius: 12px; font-size: 14px;">Check In Guest</button>
                                @endif
                            @else
                                {{-- Tables & Parcels show the Split Bill / Queue list --}}
                                <span style="display: block; font-size: 10px; font-weight: bold; color: var(--ann-text-secondary); text-transform: uppercase; margin-bottom: 16px;">{{ $selectedTableId ? 'Active Bills (Split)' : 'Customer Queue' }}</span>
                                @if($activeDinersList->count() === 0)
                                    <p style="text-align: center; color: var(--ann-text-secondary); font-style: italic; margin-top: 16px;">
                                        No active customers.</p>
                                @else
                                    @foreach($activeDinersList as $diner)
                                        <div wire:click="selectCustomerSession({{ $diner->id }})" class="customer-pill {{ $selectedSessionId === $diner->id ? 'active' : '' }}">
                                            <span class="customer-pill-name" style="color: {{ $selectedSessionId === $diner->id ? 'var(--ann-dark-blue)' : 'var(--ann-text-primary)' }}; font-weight:bold; display:block;">{{ $diner->customer_name }}</span>
                                            <span class="customer-pill-sub" style="color: var(--ann-text-secondary); font-size:11px; display:block;">Arrived {{ $diner->created_at->diffForHumans() }}</span>
                                        </div>
                                    @endforeach
                                @endif
                            @endif
                        </div>

                        {{-- MIDDLE COLUMN: ORDER HISTORY --}}
                        <div class="modal-col col-order pos-scroll">
                            @if(!$selectedSessionId)
                                <div style="height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--ann-text-secondary);">
                                    <x-heroicon-o-user style="width: 48px; height: 48px; margin-bottom: 8px; opacity: 0.5;" />
                                    <p style="font-weight: bold; margin:0;">
                                        {{ $selectedRoomId ? 'Guest not checked in' : 'Select a customer from the left' }}</p>
                                </div>
                            @else
                                @php
                                    $groupedOrders = $tableOrders->groupBy('status');
                                @endphp

                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                                    <span style="background: var(--ann-text-primary); color: white; padding: 4px 16px; border-radius: 50px; font-size: 10px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase;">Order History</span>
                                    @if(!$pendingPayment)
                                        <button wire:click="mountAction('placeOrderAction')" class="btn-primary" style="padding: 6px 16px; border-radius: 50px; font-size: 10px;">+ PLACE ORDER</button>
                                    @endif
                                </div>

                                @if($tableOrders->count() === 0)
                                    <p style="text-align: center; color: var(--ann-text-secondary); font-style: italic; margin-top: 32px;">No orders placed yet.</p>
                                @else
                                    <div style="display: flex; flex-direction: column; gap: 16px;">
                                        @foreach(['placed' => 'Pending', 'accepted' => 'Accepted', 'partial_accepted' => 'Accepted', 'preparing' => 'Cooking', 'ready' => 'Ready to Serve', 'served' => 'Served', 'cancelled' => 'Cancelled', 'rejected' => 'Cancelled'] as $statusKey => $label)
                                            @if(isset($groupedOrders[$statusKey]) && $groupedOrders[$statusKey]->count() > 0)
                                                <div>
                                                    <h4 style="font-size: 10px; font-weight: bold; margin:0; text-transform: uppercase; border-bottom: 1px solid var(--ann-border); padding-bottom: 4px; margin-bottom: 12px; color: {{ 
                                                        $statusKey === 'placed' ? 'var(--ann-dark-blue)' : 
                                                        (in_array($statusKey, ['accepted', 'partial_accepted']) ? 'var(--ann-orange)' : 
                                                        ($statusKey === 'preparing' ? 'var(--ann-success)' : 
                                                        ($statusKey === 'ready' ? 'var(--ann-blue)' : 'var(--ann-text-secondary)')))
                                                    }}">
                                                        {{ $label }}</h4>
                                                    <div style="display: flex; flex-direction: column; gap: 8px;">
                                                        @foreach($groupedOrders[$statusKey] as $order)
                                                            @php $isCancelled = in_array($statusKey, ['cancelled', 'rejected']);
                                                            $isPaid = $order->payment_status === 'paid'; @endphp
                                                            <div style="background: white; padding: 12px; border-radius: 12px; border: 1px solid var(--ann-border); box-shadow: 0 1px 2px rgba(0,0,0,0.05); {{ $isCancelled ? 'opacity: 0.5;' : '' }}">
                                                                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px dashed var(--ann-border); padding-bottom: 8px; margin-bottom: 8px;">
                                                                    <div style="display: flex; align-items: center; gap: 8px;">
                                                                        <span style="font-size: 12px; font-weight: bold; color: var(--ann-text-secondary);">Order #{{ $order->id }}</span>
                                                                        @if($isPaid) <span style="font-size: 10px; font-weight: bold; background: #d1fae5; color: var(--ann-success); padding: 2px 6px; border-radius: 4px;">PAID</span>
                                                                        @endif
                                                                    </div>
                                                                    @if(!$isCancelled && !$pendingPayment && !$isPaid)
                                                                        <button wire:click="mountAction('editOrderAction', { orderId: {{ $order->id }} })" style="font-size: 10px; font-weight: bold; border: 1px solid var(--ann-border); padding: 4px 8px; border-radius: 4px; color: var(--ann-text-secondary); background: transparent; cursor: pointer;">EDIT</button>
                                                                    @endif
                                                                </div>
                                                                @foreach($order->items as $item)
                                                                    @php $displayQty = $item->confirmed_qty ?? $item->quantity;
                                                                    $isOos = $displayQty === 0; @endphp
                                                                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 14px; margin-bottom: 4px;">
                                                                        <span style="font-weight: 600; color: var(--ann-text-primary); {{ $isOos ? 'text-decoration: line-through; color: var(--ann-text-secondary);' : '' }}">
                                                                            @if($isOos) <span style="color: var(--ann-red); font-weight: bold; font-size: 12px; margin-right: 4px;">[OOS]</span>
                                                                            @else <span style="color: var(--ann-blue); font-weight: 900; margin-right: 4px;">{{ $displayQty }}x</span>
                                                                            @endif
                                                                            {{ $item->item_name }}
                                                                        </span>
                                                                        <span style="font-weight: bold; color: var(--ann-text-primary); {{ $isOos ? 'text-decoration: line-through; color: var(--ann-text-secondary);' : '' }}">₹{{ number_format($item->unit_price * ($isOos ? $item->quantity : $displayQty), 0) }}</span>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            @endif
                        </div>

                        {{-- RIGHT COLUMN: BILLING & MANAGEMENT --}}
                        <div class="modal-col col-bill pos-scroll">
                            @if($selectedSessionId)

                                {{-- MANAGEMENT ACTIONS --}}
                                @if(!$selectedRoomId)
                                    <div style="margin-bottom: 2rem;">
                                        <span style="display: block; font-size: 10px; font-weight: bold; color: var(--ann-text-secondary); text-transform: uppercase; margin-bottom: 12px;">Management</span>
                                        @if($selectedParcelCounterId)
                                            <button wire:click.stop="cleanParcelSession({{ $selectedSessionId }})"
                                                onclick="confirm('Has this customer paid and received their food? This will close their session.') || event.stopImmediatePropagation()"
                                                style="width: 100%; background: #d1fae5; color: var(--ann-success); font-weight: bold; padding: 12px; border-radius: 12px; display: flex; justify-content: center; align-items: center; gap: 8px; border: 1px solid var(--ann-success); cursor: pointer;">
                                                <x-heroicon-s-check-circle style="width: 20px; height: 20px;" /> Complete Parcel & Clear
                                            </button>
                                        @elseif($selectedTableId)
                                            <button wire:click.stop="cleanTable({{ $selectedTableId }})"
                                                onclick="confirm('Are you sure you want to end all sessions and clean this table?') || event.stopImmediatePropagation()"
                                                style="width: 100%; background: var(--ann-orange-light); color: var(--ann-orange); font-weight: bold; padding: 12px; border-radius: 12px; display: flex; justify-content: center; align-items: center; gap: 8px; border: 1px solid var(--ann-orange); cursor: pointer;">
                                                <x-heroicon-s-sparkles style="width: 20px; height: 20px;" /> Clean Entire Table
                                            </button>
                                        @endif
                                        
                                        {{-- 🌟 ALL-IN-ONE QUICK TABLE STATUS 🌟 --}}
                                        @if(($isAllInOne ?? false) && $selectedTableId)
                                            <span style="display: block; font-size: 10px; font-weight: bold; color: var(--ann-text-secondary); text-transform: uppercase; margin: 16px 0 8px 0;">Table Status (Quick Actions)</span>
                                            <div style="display: flex; gap: 8px; margin-bottom: 16px;">
                                                <button wire:click.stop="changeTableStatus({{ $selectedTableId }}, 'available')" style="flex: 1; padding: 8px; font-size: 12px; font-weight: bold; border-radius: 8px; border: 1px solid #10b981; background: #d1fae5; color: #10b981; cursor: pointer;">Available</button>
                                                <button wire:click.stop="changeTableStatus({{ $selectedTableId }}, 'occupied')" style="flex: 1; padding: 8px; font-size: 12px; font-weight: bold; border-radius: 8px; border: 1px solid var(--ann-red); background: var(--ann-red-light); color: var(--ann-red); cursor: pointer;">Occupied</button>
                                                <button wire:click.stop="changeTableStatus({{ $selectedTableId }}, 'cleaning')" style="flex: 1; padding: 8px; font-size: 12px; font-weight: bold; border-radius: 8px; border: 1px solid var(--ann-orange); background: var(--ann-orange-light); color: var(--ann-orange); cursor: pointer;">Cleaning</button>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                {{-- BILLING --}}
                                <span style="display: block; font-size: 10px; font-weight: bold; color: var(--ann-text-secondary); text-transform: uppercase; margin-bottom: 12px;">Bill Generation</span>

                                @php
                                    $validOrders = $tableOrders->whereIn('status', ['placed', 'accepted', 'partial_accepted', 'preparing', 'ready', 'served']);
                                    $subtotal = $validOrders->sum(fn($o) => $o->confirmed_total ?? $o->total_amount);
                                    $amountAlreadyPaid = $validOrders->where('payment_status', 'paid')->sum(fn($o) => $o->confirmed_total ?? $o->total_amount);
                                    $taxable = max(0, $subtotal - (float) $discountAmount);
                                    $liveTax = $taxable * ((float) $taxPercentage / 100);
                                    $liveTotal = max(0, ($taxable + $liveTax + (float) $extraCharges) - $amountAlreadyPaid);
                                @endphp

                                @if($pendingPayment && $pendingPayment->status === 'paid')
                                    <div style="background: #d1fae5; border: 1px solid var(--ann-success); padding: 16px; border-radius: 16px; text-align: center;">
                                        <x-heroicon-s-check-circle style="width: 40px; height: 40px; color: var(--ann-success); margin: 0 auto 8px auto;" />
                                        <h4 style="color: #047857; font-weight: 900; font-size: 18px; margin:0; text-transform: uppercase; letter-spacing: 1px;">Bill Settled</h4>
                                        <div style="display: flex; justify-content: space-between; margin-top: 12px; padding-top: 12px; border-top: 1px dashed var(--ann-success);">
                                            <span style="color: #065f46; font-weight: bold; font-size: 14px;">Amount Paid:</span>
                                            <span style="color: #064e3b; font-weight: 900; font-size: 14px;">₹{{ number_format($pendingPayment->amount, 2) }}</span>
                                        </div>
                                        <p style="color: #059669; font-size: 12px; margin:0; margin-top: 12px;">Customer can now download PDF.</p>
                                    </div>
                                @else
                                    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 12px;">
                                        <span style="color: var(--ann-text-secondary); font-weight: bold; font-size: 14px;">Orders Total</span>
                                        <span style="color: var(--ann-text-primary); font-weight: 900; font-size: 18px;">₹{{ number_format($subtotal, 2) }}</span>
                                    </div>
                                    @if($amountAlreadyPaid > 0)
                                        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 12px;">
                                            <span style="color: var(--ann-success); font-weight: bold; font-size: 14px;">Already Paid</span>
                                            <span style="color: var(--ann-success); font-weight: 900; font-size: 16px;">- ₹{{ number_format($amountAlreadyPaid, 2) }}</span>
                                        </div>
                                    @endif

                                    {{-- Standard Table/Parcel Billing Modifiers --}}
                                    @if(!$selectedRoomId && !$pendingPayment && $subtotal > 0)
                                        <div style="display: flex; gap: 8px; margin-bottom: 16px;">
                                            <div style="flex: 1;">
                                                <label style="font-size: 10px; font-weight: bold; color: var(--ann-text-secondary); text-transform: uppercase;">Discount (₹)</label>
                                                <input type="number" wire:model.live="discountAmount"
                                                    style="width: 100%; box-sizing:border-box; margin-top: 4px; padding: 8px; border-radius: 8px; border: 1px solid var(--ann-border); font-weight: bold; color: var(--ann-text-primary); font-size: 14px;"
                                                    placeholder="0">
                                            </div>
                                            <div style="flex: 1;">
                                                <label style="font-size: 10px; font-weight: bold; color: var(--ann-text-secondary); text-transform: uppercase;">Tax (%)</label>
                                                <input type="number" wire:model.live="taxPercentage"
                                                    style="width: 100%; box-sizing:border-box; margin-top: 4px; padding: 8px; border-radius: 8px; border: 1px solid var(--ann-border); font-weight: bold; color: var(--ann-text-primary); font-size: 14px;"
                                                    placeholder="0">
                                            </div>
                                            <div style="flex: 1;">
                                                <label style="font-size: 10px; font-weight: bold; color: var(--ann-text-secondary); text-transform: uppercase;">Extra (₹)</label>
                                                <input type="number" wire:model.live="extraCharges"
                                                    style="width: 100%; box-sizing:border-box; margin-top: 4px; padding: 8px; border-radius: 8px; border: 1px solid var(--ann-border); font-weight: bold; color: var(--ann-text-primary); font-size: 14px;"
                                                    placeholder="0">
                                            </div>
                                        </div>
                                    @endif

                                    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; padding-top: 12px; border-top: 2px dashed var(--ann-border);">
                                        <span style="color: var(--ann-text-primary); font-weight: 900; font-size: 18px;">Amount Due</span>
                                        <span style="color: var(--ann-red); font-weight: 900; font-size: 28px;">₹{{ number_format($pendingPayment ? $pendingPayment->amount : $liveTotal, 2) }}</span>
                                    </div>

                                    @if($subtotal > 0)
                                        @if($selectedRoomId)
                                            {{-- Room Settlement Button --}}
                                            <button wire:click="settleRoomBill" class="btn-primary" style="width: 100%; background: var(--ann-success); padding: 16px; border-radius: 12px; font-size: 16px; margin:0;">
                                                Settle Food Bill to Room
                                            </button>
                                        @else
                                            {{-- Standard POS Flow --}}
                                            @if(!$pendingPayment)
                                                <button wire:click="sendBillToCustomer" class="btn-primary" style="width: 100%; padding: 16px; border-radius: 12px; font-size: 16px; display: flex; justify-content: center; align-items: center; gap: 8px; margin:0;">
                                                    <x-heroicon-s-paper-airplane style="width: 20px; height: 20px;" /> Generate Final Bill
                                                </button>
                                            @else
                                                <div style="background: white; border: 1px solid var(--ann-border); padding: 16px; border-radius: 12px; margin-bottom: 16px; text-align: center;">
                                                    <span style="display: block; font-size: 10px; font-weight: bold; color: var(--ann-text-secondary); text-transform: uppercase; margin-bottom: 4px;">Customer Selected</span>
                                                    
                                                    @if($pendingPayment->payment_method === 'cash')
                                                        <span style="color: var(--ann-blue); font-weight: 900; font-size: 20px; margin:0; text-transform: uppercase; display: block;">PAY AT COUNTER (CASH)</span>
                                                    @else
                                                        @if($pendingPayment->payment_method === 'upi')
                                                            <span style="color: var(--ann-success); font-weight: 900; font-size: 18px; margin:0; display: block;">UPI SELECTED</span>
                                                        @else
                                                            <span style="color: var(--ann-orange); font-weight: 900; font-size: 18px; margin:0; display: block;" class="animate-pulse">Waiting for Customer...</span>
                                                        @endif

                                                        {{-- UPI QR CODE FOR MODAL RIGHT COLUMN --}}
                                                        @if(!empty($upiId))
                                                            <div style="margin-top: 12px; background: white; padding: 8px; border-radius: 8px; text-align: center; border: 2px dashed var(--ann-success);">
                                                                <p style="font-size: 11px; font-weight: 900; color: var(--ann-success); margin: 0 0 8px 0;">
                                                                    SCAN TO PAY ₹{{ number_format($pendingPayment->amount, 0) }}</p>
                                                                <div style="display: flex; justify-content: center;">
                                                                    {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(120)->margin(0)->generate("upi://pay?pa={$upiId}&pn=" . urlencode($restaurantName) . "&am={$pendingPayment->amount}&cu=INR&tr={$pendingPayment->transaction_reference}") !!}
                                                                </div>
                                                            </div>
                                                        @endif
                                                    @endif
                                                </div>

                                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                                    <button wire:click="printPendingBill" class="btn-primary" style="width: 100%; background: var(--ann-blue); margin:0; padding: 12px; border-radius: 8px; display: flex; justify-content: center; align-items: center; gap: 8px;">
                                                        <x-heroicon-s-printer style="width: 20px; height: 20px;" /> Print Physical Bill
                                                    </button>
                                                    <button wire:click="confirmPayment" class="btn-primary" style="width: 100%; background: var(--ann-success); margin:0; padding: 12px; border-radius: 8px; display: flex; justify-content: center; align-items: center; gap: 8px;">
                                                        <x-heroicon-s-check-circle style="width: 20px; height: 20px;" /> Confirm Payment
                                                    </button>
                                                    <button wire:click="cancelPendingBill" onclick="confirm('Cancel generated bill?') || event.stopImmediatePropagation()" class="btn-secondary" style="width: 100%; margin:0; padding: 10px; border-radius: 8px; font-size: 12px;">
                                                        Cancel Generated Bill
                                                    </button>
                                                </div>
                                            @endif
                                        @endif
                                    @endif
                                @endif
                            @else
                                <div style="height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--ann-text-secondary);">
                                    <x-heroicon-o-currency-rupee style="width: 64px; height: 64px; margin-bottom: 8px; opacity: 0.5;" />
                                    <p style="font-weight: bold; font-size: 14px; margin:0;">
                                        {{ $selectedRoomId ? 'Guest not checked in' : 'Select a customer to view billing' }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <x-filament-actions::modals />

        {{-- 🌟 NAYA: BROWSER NOTIFICATION AND GUJARATI TTS SCRIPT 🌟 --}}
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if ("Notification" in window && Notification.permission !== "granted" && Notification.permission !== "denied") {
                    Notification.requestPermission();
                }
                
                // Existing Visual Notification
                window.addEventListener('trigger-browser-notification', function (e) {
                    const data = e.detail;
                    if ("Notification" in window && Notification.permission === "granted") {
                        const notification = new Notification(data.title, { body: data.body, requireInteraction: true });
                        notification.onclick = function (event) { event.preventDefault(); window.focus(); notification.close(); };
                    }
                });

                // NEW: Dynamic Text To Speech (Gujarati / Hindi)
                window.addEventListener('speak-notification', function (e) {
                    if ('speechSynthesis' in window) {
                        // Get the text from Livewire 3 event detail
                        const text = e.detail.text || (e.detail[0] ? e.detail[0].text : '');
                        if(!text) return;

                        // Cancel any currently speaking audio
                        window.speechSynthesis.cancel();
                        
                        let msg = new SpeechSynthesisUtterance(text);
                        
                        // Try to find a Gujarati or Indian voice for natural pronunciation
                        let voices = window.speechSynthesis.getVoices();
                        let indianVoice = voices.find(voice => 
                            voice.lang.includes('gu-IN') || 
                            voice.lang.includes('hi-IN') || 
                            voice.lang.includes('en-IN')
                        );
                        
                        if (indianVoice) {
                            msg.voice = indianVoice;
                        }
                        
                        msg.rate = 0.9; // Clear speed
                        msg.pitch = 1;
                        msg.volume = 1;

                        window.speechSynthesis.speak(msg);
                    }
                });
            });
        </script>
    </div>
</x-filament-panels::page>