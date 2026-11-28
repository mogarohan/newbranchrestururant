<x-filament-panels::page>
    @php
        $stats = $this->shiftStats;
        $readyOrders = $this->readyOrders;
        $tables = $this->tables;
        $alerts = $this->activeAlerts;
    @endphp

    <style>
        /* Base Styling */
        .waiter-station-wrapper {
            font-family: inherit;
            color: #0f172a;
            max-width: 100%;
        }

        /* Topbar */
        .waiter-topbar {
            background: linear-gradient(135deg, #ffffff 0%, #fff7ed 100%);
            border: 1px solid #fed7aa;
            border-radius: 16px;
            padding: 12px 16px;
            box-shadow: 0 4px 20px -2px rgba(241, 107, 63, 0.08);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 14px;
        }

        .waiter-badge-pulse {
            animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse-ring {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.85; transform: scale(1.05); }
        }

        .sound-status-btn {
            background: #ffffff;
            border: 1.5px solid #fdba74;
            color: #c2410c;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(241, 107, 63, 0.1);
        }
        .sound-status-btn:hover {
            background: #fff7ed;
            border-color: #f97316;
        }

        /* Stats Summary Grid */
        .stats-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 14px;
        }

        .stat-capsule {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 8px 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            min-width: 0;
        }
        .stat-capsule-num {
            font-size: 18px;
            font-weight: 800;
            line-height: 1;
        }
        .stat-capsule-lbl {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Active Alert Banner */
        .alert-banner-box {
            background: #fff1f2;
            border: 1.5px solid #fda4af;
            border-radius: 14px;
            padding: 12px;
            margin-bottom: 14px;
            box-shadow: 0 6px 18px -3px rgba(225, 29, 72, 0.12);
        }

        .alert-item-card {
            background: #ffffff;
            border: 1px solid #fecdd3;
            border-radius: 10px;
            padding: 8px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-top: 6px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }

        /* Navigation Switcher */
        .waiter-nav-pills {
            display: flex;
            gap: 6px;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 12px;
            margin-bottom: 14px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .waiter-nav-pill {
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            border: none;
            background: transparent;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .waiter-nav-pill.active {
            background: #ffffff;
            color: #f16b3f;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
        }

        /* Ready Orders Grid & Cards */
        .ready-orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 12px;
        }

        .ready-card {
            background: #ffffff;
            border: 1.5px solid #86efac;
            border-radius: 14px;
            padding: 14px;
            box-shadow: 0 3px 10px rgba(34, 197, 94, 0.1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Tables Grid */
        .tables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
        }

        .table-card {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            padding: 12px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .table-card.occupied {
            border-color: #93c5fd;
            background: linear-gradient(145deg, #ffffff 0%, #eff6ff 100%);
        }
        .table-card.available {
            border-color: #86efac;
            background: linear-gradient(145deg, #ffffff 0%, #f0fdf4 100%);
        }
        .table-card.cleaning {
            border-color: #fde047;
            background: linear-gradient(145deg, #ffffff 0%, #fefce8 100%);
        }

        /* Prevent Filament notifications from overflowing and blocking mobile screen */
        .fi-no {
            max-height: 200px !important;
            overflow-y: hidden !important;
            pointer-events: none !important;
            position: fixed !important;
            top: 10px !important;
            right: 10px !important;
            left: 10px !important;
            max-width: 440px !important;
            margin: 0 auto !important;
            z-index: 10000000 !important;
        }
        .fi-no > * {
            pointer-events: auto !important;
            max-width: 100% !important;
            margin-bottom: 6px !important;
        }

        /* ─── KOT VISUAL MENU MODAL (MOBILE-OPTIMIZED POS) ─── */
        .stitch-order-overlay {
            position: fixed;
            inset: 0;
            background: rgba(11, 28, 48, 0.88);
            backdrop-filter: blur(6px);
            z-index: 9999999 !important;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            box-sizing: border-box;
        }
        .stitch-order-modal {
            background: #ffffff;
            width: 100%;
            max-width: 1150px;
            height: 92vh;
            max-height: 850px;
            border-radius: 16px;
            box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.4);
            border: 1px solid rgba(226, 232, 240, 0.9);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: relative;
        }
        .stitch-order-header {
            padding: 10px 16px;
            border-bottom: 1px solid #e2e8f0;
            background: #f8f9ff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
            min-height: 48px;
            gap: 8px;
        }

        .stitch-order-workspace {
            flex: 1;
            display: flex;
            flex-direction: row;
            overflow: hidden;
            min-height: 0;
            background: #ffffff;
        }
        .stitch-order-cat-rail {
            width: 84px;
            background: #f8fafc;
            border-right: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 8px 4px;
            overflow-y: auto;
            flex-shrink: 0;
            gap: 6px;
            user-select: none;
        }
        .stitch-cat-btn {
            width: 76px;
            padding: 6px 2px;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 1.5px solid transparent;
            background: transparent;
            transition: all 0.15s ease;
            user-select: none;
        }
        .stitch-cat-btn.active {
            border-color: #fdba74;
            background: #ffedd5;
        }
        .stitch-cat-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2px;
            background: #e2e8f0;
            color: #475569;
            font-weight: 800;
            font-size: 11px;
            text-transform: uppercase;
        }
        .stitch-cat-avatar.active {
            background: #fed7aa;
            color: #9a3412;
        }
        .stitch-cat-label {
            font-size: 10px;
            font-weight: 700;
            color: #475569;
            text-align: center;
            max-width: 70px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            line-height: 1.1;
        }
        .stitch-cat-label.active {
            color: #9a3412;
            font-weight: 800;
        }
        .stitch-cat-badge {
            font-size: 9px;
            font-weight: 800;
            padding: 1px 5px;
            border-radius: 9999px;
            background: #cbd5e1;
            color: #475569;
            margin-top: 2px;
            min-width: 16px;
            text-align: center;
        }
        .stitch-cat-badge.active {
            background: #ea580c;
            color: #ffffff;
        }
        .stitch-order-catalog {
            flex: 1;
            overflow-y: auto;
            padding: 12px 14px;
            background: rgba(248, 249, 255, 0.4);
            min-width: 0;
        }
        .stitch-order-tray {
            width: 340px;
            background: #f8f9ff;
            border-left: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            flex-shrink: 0;
            overflow-y: auto;
        }
        .stitch-order-footer {
            padding: 10px 16px;
            border-top: 1px solid #e2e8f0;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
            position: sticky;
            bottom: 0;
            z-index: 100;
        }

        /* ── Compact Dish Row (NO IMAGE, Maximum usable space) ── */
        .stitch-dish-row {
            background: #ffffff;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            padding: 9px 12px;
            box-shadow: 0 1px 2px rgba(15, 28, 48, 0.03);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            transition: all 0.15s;
        }
        .stitch-dish-row:hover {
            border-color: #f8cbb4;
            background: #fffcf9;
        }

        /* FSSAI Standard Veg / Non-Veg Symbols */
        .stitch-veg-icon {
            width: 14px;
            height: 14px;
            border: 1.5px solid #059669;
            border-radius: 3px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            padding: 1px;
            flex-shrink: 0;
        }
        .stitch-veg-icon > span {
            width: 6px;
            height: 6px;
            border-radius: 9999px;
            background: #059669;
        }
        .stitch-nonveg-icon {
            width: 14px;
            height: 14px;
            border: 1.5px solid #dc2626;
            border-radius: 3px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            padding: 1px;
            flex-shrink: 0;
        }
        .stitch-nonveg-icon > span {
            width: 0;
            height: 0;
            border-left: 3.5px solid transparent;
            border-right: 3.5px solid transparent;
            border-bottom: 6px solid #dc2626;
        }

        .stitch-dot {
            width: 7px;
            height: 7px;
            border-radius: 9999px;
            display: inline-block;
        }
        .animate-pulse-dot {
            animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        /* ── MOBILE SPECIFIC STYLING (Phone Screen ≤ 1024px) ── */
        @media (max-width: 1024px) {
            .stitch-order-overlay {
                padding: 0 !important;
                height: 100vh !important;
                height: 100dvh !important;
                width: 100vw !important;
            }
            .stitch-order-modal {
                max-width: 100vw !important;
                width: 100vw !important;
                height: 100vh !important;
                height: 100dvh !important;
                max-height: 100dvh !important;
                border-radius: 0 !important;
                border: none !important;
            }
            .stitch-order-header {
                padding: 8px 12px !important;
                min-height: 44px !important;
            }
            .stitch-order-workspace {
                flex-direction: row !important;
                height: calc(100dvh - 96px) !important;
                overflow: hidden !important;
            }
            .stitch-order-cat-rail {
                width: 76px !important;
                padding: 6px 2px !important;
                background: #f8fafc !important;
                border-right: 1px solid #e2e8f0 !important;
            }
            .stitch-cat-btn {
                width: 70px !important;
                padding: 5px 2px !important;
            }
            .stitch-cat-avatar {
                width: 26px !important;
                height: 26px !important;
                font-size: 10px !important;
            }
            .stitch-cat-label {
                font-size: 9px !important;
                max-width: 66px !important;
            }
            .stitch-order-catalog {
                padding: 8px 10px !important;
                height: 100% !important;
                flex: 1 !important;
            }
            .stitch-order-tray {
                width: 100% !important;
                border-left: none !important;
                height: 100% !important;
                padding-bottom: 70px !important;
            }
            .stitch-order-footer {
                padding: 8px 12px !important;
                background: #ffffff !important;
                border-top: 1.5px solid #fed7aa !important;
                box-shadow: 0 -4px 16px rgba(0,0,0,0.12) !important;
                padding-bottom: max(8px, env(safe-area-inset-bottom)) !important;
            }

            .stats-summary-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 6px !important;
            }
            .stat-capsule {
                padding: 6px 10px !important;
            }
            .stat-capsule-num {
                font-size: 16px !important;
            }
            .waiter-topbar {
                padding: 10px 12px !important;
                border-radius: 12px !important;
            }
            .sound-status-btn {
                padding: 5px 10px !important;
                font-size: 11px !important;
                width: 100% !important;
                justify-content: center !important;
            }
            .tables-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 6px !important;
            }
            .table-card {
                padding: 8px 10px !important;
                border-radius: 10px !important;
            }
            .ready-orders-grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>

    {{-- Hide the admin panel fixed footer completely when Order Modal or Table Details is open --}}
    @if($isPlaceOrderOpen || $viewingTableId)
        <style>
            .fi-footer-custom {
                display: none !important;
                visibility: hidden !important;
                opacity: 0 !important;
                pointer-events: none !important;
                height: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
            }
        </style>
    @endif

    <div class="waiter-station-wrapper">
        {{-- ── 1. HEADER & SHIFT CONTROL BAR ── --}}
        <div class="waiter-topbar">
            <div style="flex: 1; min-width: 200px;">
                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                    <div style="width: 8px; height: 8px; border-radius: 9999px; background: #22c55e;" class="waiter-badge-pulse"></div>
                    <h1 style="font-size: 17px; font-weight: 800; color: #0f172a; margin: 0;">
                        Waiter Station
                    </h1>
                    <span style="font-size: 11px; font-weight: 700; background: #ffedd5; color: #c2410c; padding: 2px 7px; border-radius: 9999px;">
                        {{ auth()->user()->name }}
                    </span>
                    @if(auth()->user()->branch)
                        <span style="font-size: 10px; font-weight: 700; background: #f1f5f9; color: #475569; padding: 2px 5px; border-radius: 6px;">
                            📍 {{ auth()->user()->branch->name }}
                        </span>
                    @endif
                </div>
                <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                    Instant pickup alerts, table buzzers & fast KOT entry.
                </div>
            </div>

            {{-- Audio & Desktop Notification Unlock Pill --}}
            <div>
                <button type="button" onclick="window.testAudioAndNotifications()" class="sound-status-btn" id="audio-status-btn">
                    <span id="audio-btn-icon">🔊</span>
                    <span id="audio-btn-text">Audio Alerts Active (Click to Test)</span>
                </button>
            </div>
        </div>

        {{-- ── 2. SHIFT STATISTICS COUNTER CAPSULES ── --}}
        <div class="stats-summary-grid">
            <div class="stat-capsule" style="border-left: 3.5px solid #22c55e;">
                <div style="font-size: 20px;">🛎️</div>
                <div style="min-width: 0;">
                    <div class="stat-capsule-num" style="color: #15803d;">{{ $stats['ready_count'] }}</div>
                    <div class="stat-capsule-lbl">Ready Pickup</div>
                </div>
            </div>

            <div class="stat-capsule" style="border-left: 3.5px solid #f43f5e;">
                <div style="font-size: 20px;">🚨</div>
                <div style="min-width: 0;">
                    <div class="stat-capsule-num" style="color: #be123c;">{{ $stats['calls_count'] }}</div>
                    <div class="stat-capsule-lbl">Table Calls</div>
                </div>
            </div>

            <div class="stat-capsule" style="border-left: 3.5px solid #3b82f6;">
                <div style="font-size: 20px;">🪑</div>
                <div style="min-width: 0;">
                    <div class="stat-capsule-num" style="color: #1d4ed8;">{{ $stats['occupied_tables'] }}</div>
                    <div class="stat-capsule-lbl">Occupied</div>
                </div>
            </div>

            <div class="stat-capsule" style="border-left: 3.5px solid #f59e0b;">
                <div style="font-size: 20px;">✔️</div>
                <div style="min-width: 0;">
                    <div class="stat-capsule-num" style="color: #b45309;">{{ $stats['served_today'] }}</div>
                    <div class="stat-capsule-lbl">Served Today</div>
                </div>
            </div>
        </div>

        {{-- ── 3. ACTIVE ASSISTANCE / BUZZER ALERTS (IF ANY) ── --}}
        @if(!empty($alerts))
            <div class="alert-banner-box">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px; flex-wrap: wrap; gap: 4px;">
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <span style="font-size: 16px; animation: bounce 1s infinite;">🔔</span>
                        <span style="font-size: 13px; font-weight: 800; color: #be123c; text-transform: uppercase;">
                            Guest Calls ({{ count($alerts) }})
                        </span>
                    </div>
                    <span style="font-size: 10px; color: #9f1239; font-weight: 600;">
                        Tap attend to clear
                    </span>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 6px;">
                    @foreach($alerts as $alert)
                        <div class="alert-item-card">
                            <div style="min-width: 0;">
                                <div style="display: flex; align-items: center; gap: 5px; flex-wrap: wrap;">
                                    <span style="font-weight: 800; font-size: 14px; color: #0f172a;">{{ $alert['table_number'] }}</span>
                                    @if(($alert['type'] ?? '') === 'bill_requested')
                                        <span style="background: #fef08a; color: #854d0e; font-size: 9px; font-weight: 800; padding: 1px 4px; border-radius: 4px;">💰 BILL</span>
                                    @else
                                        <span style="background: #ffe4e6; color: #be123c; font-size: 9px; font-weight: 800; padding: 1px 4px; border-radius: 4px;">🙋 CALL</span>
                                    @endif
                                </div>
                                <div style="font-size: 11px; color: #475569; margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    {{ $alert['customer_name'] }} • <span style="color: #94a3b8;">{{ $alert['time'] }}</span>
                                </div>
                            </div>
                            <button type="button" wire:click="resolveAlert('{{ $alert['id'] }}')" style="background: #be123c; color: #ffffff; border: none; padding: 6px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer; flex-shrink: 0;">
                                Attend ✓
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ── 4. NAVIGATION TABS ── --}}
        <div class="waiter-nav-pills">
            <button type="button" wire:click="switchTab('ready_orders')" class="waiter-nav-pill {{ $currentTab === 'ready_orders' ? 'active' : '' }}">
                <span style="font-size: 14px;">🛎️</span>
                <span>Ready to Serve</span>
                @if($readyOrders->count() > 0)
                    <span style="background: #22c55e; color: #ffffff; padding: 1px 6px; border-radius: 9999px; font-size: 10px;">
                        {{ $readyOrders->count() }}
                    </span>
                @endif
            </button>

            <button type="button" wire:click="switchTab('tables')" class="waiter-nav-pill {{ $currentTab === 'tables' ? 'active' : '' }}">
                <span style="font-size: 14px;">🪑</span>
                <span>Floor Tables</span>
                <span style="background: #e2e8f0; color: #475569; padding: 1px 6px; border-radius: 9999px; font-size: 10px;">
                    {{ $tables->count() }}
                </span>
            </button>
        </div>

        {{-- ── 5. TAB VIEW: READY TO SERVE ORDERS (PICKUP PASS) ── --}}
        @if($currentTab === 'ready_orders')
            @if($readyOrders->isEmpty())
                <div style="background: #ffffff; border: 2px dashed #cbd5e1; border-radius: 16px; padding: 36px 14px; text-align: center; margin-top: 4px;">
                    <div style="font-size: 38px; margin-bottom: 8px;">👨‍🍳</div>
                    <div style="font-size: 16px; font-weight: 800; color: #1e293b;">Kitchen Pass is Clear!</div>
                    <div style="font-size: 12px; color: #64748b; max-width: 320px; margin: 4px auto 0;">
                        No dishes are waiting right now. When food is marked <b>Ready</b> by the kitchen, it alerts you immediately.
                    </div>
                </div>
            @else
                <div class="ready-orders-grid">
                    @foreach($readyOrders as $order)
                        @php
                            $serviceType = $order->service_type ?? 'dine_in';
                            $locationLabel = 'Table ' . ($order->table?->table_number ?? '?');
                            $locationBadgeBg = '#ffedd5';
                            $locationBadgeColor = '#c2410c';

                            if ($serviceType === 'parcel') {
                                $locationLabel = '🛍️ Parcel: ' . ($order->parcelQrSession?->parcelQrCode?->name ?? 'Counter');
                                $locationBadgeBg = '#ede9fe';
                                $locationBadgeColor = '#6d28d9';
                            } elseif ($serviceType === 'room_service') {
                                $locationLabel = '🚪 Room ' . ($order->roomSession?->room?->room_number ?? '?');
                                $locationBadgeBg = '#e0f2fe';
                                $locationBadgeColor = '#1d4ed8';
                            }

                            $readyItems = $order->items->filter(fn($i) => ($i->status ?? 'placed') === 'ready');
                            $cookingItems = $order->items->filter(fn($i) => in_array($i->status ?? 'placed', ['placed', 'preparing']));
                            $servedItems = $order->items->filter(fn($i) => ($i->status ?? 'placed') === 'served');
                        @endphp

                        <div class="ready-card">
                            <div>
                                <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 8px;">
                                    <div>
                                        <div style="font-size: 10px; font-weight: 800; background: {{ $locationBadgeBg }}; color: {{ $locationBadgeColor }}; padding: 2px 7px; border-radius: 6px; display: inline-block;">
                                            {{ $locationLabel }}
                                        </div>
                                        <div style="font-size: 16px; font-weight: 800; color: #0f172a; margin-top: 2px;">
                                            Order #{{ $order->id }}
                                        </div>
                                        <div style="font-size: 11px; color: #64748b;">
                                            Guest: <b>{{ $order->customer_name ?: 'Diner' }}</b>
                                        </div>
                                    </div>

                                    <div style="text-align: right;">
                                        @if($cookingItems->isNotEmpty())
                                            <span style="background: #fef3c7; color: #b45309; font-size: 10px; font-weight: 800; padding: 2px 7px; border-radius: 9999px; display: inline-flex; align-items: center; gap: 3px;">
                                                <span style="width: 5px; height: 5px; border-radius: 9999px; background: #f59e0b;"></span>
                                                PARTIAL READY
                                            </span>
                                        @else
                                            <span style="background: #dcfce7; color: #15803d; font-size: 10px; font-weight: 800; padding: 2px 7px; border-radius: 9999px; display: inline-flex; align-items: center; gap: 3px;">
                                                <span style="width: 5px; height: 5px; border-radius: 9999px; background: #22c55e;"></span>
                                                ALL READY
                                            </span>
                                        @endif
                                        <div style="font-size: 10px; color: #94a3b8; margin-top: 2px;">
                                            {{ $order->updated_at->diffForHumans(null, true) }} ago
                                        </div>
                                    </div>
                                </div>

                                <div style="background: #f8fafc; border-radius: 10px; padding: 8px 10px; margin-bottom: 12px; max-height: 220px; overflow-y: auto;">
                                    {{-- 1. Ready to deliver section --}}
                                    <div style="font-size: 10px; font-weight: 800; text-transform: uppercase; color: #16a34a; margin-bottom: 6px; display: flex; align-items: center; justify-content: space-between;">
                                        <span>🛎️ Ready for Pickup ({{ $readyItems->sum('quantity') }})</span>
                                        @if($readyItems->count() > 1)
                                            <button type="button" wire:click="markAsServed({{ $order->id }})" wire:loading.attr="disabled"
                                                style="background: #16a34a; color: #ffffff; border: none; padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: 800; cursor: pointer;">
                                                Deliver All ({{ $readyItems->count() }})
                                            </button>
                                        @endif
                                    </div>

                                    @foreach($readyItems as $item)
                                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 4px 0; border-bottom: 1px dashed #cbd5e1; font-size: 12px; gap: 6px;">
                                            <div style="font-weight: 700; color: #0f172a; display: flex; align-items: baseline; gap: 5px; flex: 1;">
                                                <span style="background: #dcfce7; color: #166534; padding: 1px 5px; border-radius: 4px; font-size: 10px; font-weight: 800;">{{ $item->quantity }}x</span>
                                                <span>{{ $item->item_name }}</span>
                                                @if($item->notes)
                                                    <span style="font-size: 10px; color: #e11d48; font-weight: 600; font-style: italic;">({{ $item->notes }})</span>
                                                @endif
                                            </div>
                                            <button type="button" wire:click="markItemAsServed({{ $item->id }})" wire:loading.attr="disabled"
                                                style="padding: 3px 8px; border-radius: 6px; background: #16a34a; color: #ffffff; border: none; font-size: 10px; font-weight: 800; cursor: pointer; display: flex; align-items: center; gap: 3px; flex-shrink: 0;">
                                                <span>Deliver</span>
                                                <span>✔️</span>
                                            </button>
                                        </div>
                                    @endforeach

                                    {{-- 2. Still cooking section --}}
                                    @if($cookingItems->isNotEmpty())
                                        <div style="margin-top: 8px; padding-top: 6px; border-top: 1px solid #e2e8f0;">
                                            <div style="font-size: 9px; font-weight: 800; text-transform: uppercase; color: #d97706; margin-bottom: 4px;">
                                                🍳 Still Cooking ({{ $cookingItems->sum('quantity') }})
                                            </div>
                                            @foreach($cookingItems as $cItem)
                                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 2px 0; font-size: 11px; color: #64748b;">
                                                    <div style="display: flex; align-items: center; gap: 4px;">
                                                        <span style="font-size: 9px; background: #fef3c7; color: #b45309; padding: 1px 4px; border-radius: 3px; font-weight: 700;">{{ $cItem->quantity }}x</span>
                                                        <span>{{ $cItem->item_name }}</span>
                                                    </div>
                                                    <span style="font-size: 9px; color: #d97706; font-weight: 700; text-transform: uppercase;">{{ $cItem->status === 'preparing' ? 'Cooking' : 'Waiting' }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    {{-- 3. Already served section --}}
                                    @if($servedItems->isNotEmpty())
                                        <div style="margin-top: 6px; padding-top: 4px; border-top: 1px dashed #e2e8f0;">
                                            <div style="font-size: 9px; font-weight: 700; color: #94a3b8;">
                                                ✔️ Already Delivered: {{ $servedItems->map(fn($i) => $i->quantity . 'x ' . $i->item_name)->implode(', ') }}
                                            </div>
                                        </div>
                                    @endif

                                    @if($order->notes)
                                        <div style="margin-top: 5px; font-size: 10px; background: #fff1f2; color: #be123c; padding: 3px 6px; border-radius: 5px; font-weight: 600;">
                                            ⚠️ {{ $order->notes }}
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <button type="button" wire:click="markAsServed({{ $order->id }})" wire:loading.attr="disabled" style="width: 100%; min-height: 40px; background: #16a34a; color: #ffffff; border: none; padding: 9px 14px; border-radius: 10px; font-size: 13px; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25);">
                                <span>{{ $cookingItems->isNotEmpty() ? 'Deliver All Ready Items' : 'Mark Delivered to Table' }}</span>
                                <span style="font-size: 15px;">✔️</span>
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif

        {{-- ── 6. TAB VIEW: FLOOR TABLES ── --}}
        @if($currentTab === 'tables')
            <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 6px; margin-bottom: 12px; background: #ffffff; padding: 8px 10px; border-radius: 12px; border: 1px solid #e2e8f0;">
                <div style="display: flex; gap: 4px; overflow-x: auto; max-width: 100%;">
                    <button type="button" wire:click="$set('tableFilter', 'all')" style="padding: 4px 9px; border-radius: 6px; font-size: 11px; font-weight: 700; border: none; cursor: pointer; white-space: nowrap; background: {{ $tableFilter === 'all' ? '#0f172a' : '#f1f5f9' }}; color: {{ $tableFilter === 'all' ? '#ffffff' : '#475569' }};">
                        All ({{ $tables->count() }})
                    </button>
                    <button type="button" wire:click="$set('tableFilter', 'occupied')" style="padding: 4px 9px; border-radius: 6px; font-size: 11px; font-weight: 700; border: none; cursor: pointer; white-space: nowrap; background: {{ $tableFilter === 'occupied' ? '#2563eb' : '#f1f5f9' }}; color: {{ $tableFilter === 'occupied' ? '#ffffff' : '#475569' }};">
                        Occupied ({{ $tables->where('status', 'occupied')->count() }})
                    </button>
                    <button type="button" wire:click="$set('tableFilter', 'available')" style="padding: 4px 9px; border-radius: 6px; font-size: 11px; font-weight: 700; border: none; cursor: pointer; white-space: nowrap; background: {{ $tableFilter === 'available' ? '#16a34a' : '#f1f5f9' }}; color: {{ $tableFilter === 'available' ? '#ffffff' : '#475569' }};">
                        Available ({{ $tables->where('status', 'available')->count() }})
                    </button>
                    <button type="button" wire:click="$set('tableFilter', 'cleaning')" style="padding: 4px 9px; border-radius: 6px; font-size: 11px; font-weight: 700; border: none; cursor: pointer; white-space: nowrap; background: {{ $tableFilter === 'cleaning' ? '#ca8a04' : '#f1f5f9' }}; color: {{ $tableFilter === 'cleaning' ? '#ffffff' : '#475569' }};">
                        Cleaning ({{ $tables->where('status', 'cleaning')->count() }})
                    </button>
                </div>

                <div style="flex: 1; min-width: 120px;">
                    <input type="text" wire:model.live.debounce.300ms="searchQuery" placeholder="Search table..." style="width: 100%; padding: 5px 8px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 11px; outline: none;">
                </div>
            </div>

            <div class="tables-grid">
                @foreach($tables as $table)
                    @php
                        $status = $table->status ?? 'available';
                        $statusBadgeBg = '#dcfce7';
                        $statusBadgeColor = '#15803d';
                        $statusClass = 'available';

                        if ($status === 'occupied') {
                            $statusBadgeBg = '#dbeafe';
                            $statusBadgeColor = '#1d4ed8';
                            $statusClass = 'occupied';
                        } elseif ($status === 'cleaning') {
                            $statusBadgeBg = '#fef9c3';
                            $statusBadgeColor = '#854d0e';
                            $statusClass = 'cleaning';
                        }

                        $activeSession = $table->qrSessions->first();
                    @endphp

                    <div class="table-card {{ $statusClass }}" wire:click="openTableDetails({{ $table->id }})" style="cursor: pointer; transition: transform 0.15s ease, box-shadow 0.15s ease;" title="Click to view customer order history">
                        <div>
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                                <div style="font-size: 16px; font-weight: 800; color: #0f172a;">
                                    {{ $this->formatTableNumber($table->table_number) }}
                                </div>
                                <span style="background: {{ $statusBadgeBg }}; color: {{ $statusBadgeColor }}; font-size: 9px; font-weight: 800; padding: 1px 5px; border-radius: 4px; text-transform: uppercase;">
                                    {{ $status }}
                                </span>
                            </div>

                            <div style="font-size: 11px; color: #64748b; margin-bottom: 6px;">
                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <span>👥 {{ $table->seating_capacity ?? 4 }} seats</span>
                                    <span style="font-size: 10px; color: #2563eb; font-weight: 700; background: #eff6ff; padding: 1px 5px; border-radius: 4px; border: 1px solid #bfdbfe;">
                                        ℹ️ Info & Orders
                                    </span>
                                </div>
                                @if($activeSession)
                                    <div style="font-weight: 700; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px;">
                                        👤 {{ $activeSession->customer_name }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 4px; margin-top: 4px;">
                            @if($status === 'available')
                                <button type="button" wire:click.stop="openPlaceOrder({{ $table->id }})" style="min-height: 36px; background: #f16b3f; color: #ffffff; border: none; padding: 7px 10px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px;">
                                    <span>➕ Take Order</span>
                                </button>
                            @elseif($status === 'occupied')
                                <button type="button" wire:click.stop="openPlaceOrder({{ $table->id }})" style="min-height: 38px; background: #2563eb; color: #ffffff; border: none; padding: 8px 12px; border-radius: 8px; font-size: 12px; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 5px; box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);">
                                    <span>➕ Add Dishes</span>
                                </button>
                            @elseif($status === 'cleaning')
                                <button type="button" wire:click.stop="changeTableStatus({{ $table->id }}, 'available')" style="min-height: 38px; background: #16a34a; color: #ffffff; border: none; padding: 8px 12px; border-radius: 8px; font-size: 12px; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px; box-shadow: 0 2px 4px rgba(22, 163, 74, 0.2);">
                                    ✓ Table Cleaned & Ready
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ── 7. TABLE-SIDE FAST KOT ORDERING MODAL (MOBILE-PERFECT STITCH DESIGN) ── --}}
        @if($isPlaceOrderOpen)
            @php
                $placeOrderCategories = $this->placeOrderCategories;
                $placeOrderMenuItems = $this->placeOrderMenuItems;
                $totalMenuItemsCount = $this->totalMenuItemsCount;

                $kotSubtotal = collect($placeOrderCart)->sum(fn($i) => $i['unit_price'] * $i['quantity']);
                $kotTotal = $kotSubtotal;
                $kotTotalItems = count($placeOrderCart);
                $kotTotalQty = collect($placeOrderCart)->sum('quantity');

                $destLabel = 'Dine In';
                if ($placeOrderTableId) {
                    $tRecord = \App\Models\RestaurantTable::find($placeOrderTableId);
                    $destLabel = $tRecord ? $this->formatTableNumber($tRecord->table_number) : 'Table';
                } elseif ($placeOrderCounterId) {
                    $cRecord = \App\Models\ParcelQrCode::find($placeOrderCounterId);
                    $destLabel = $cRecord ? '🛍️ ' . $cRecord->name : 'Parcel';
                } elseif ($placeOrderRoomId) {
                    $rRecord = \App\Models\Room::find($placeOrderRoomId);
                    $destLabel = $rRecord ? 'Room ' . $rRecord->room_number : 'Room Service';
                }
            @endphp

            <div class="stitch-order-overlay" wire:click.self="closePlaceOrder" @keydown.escape.window="$wire.closePlaceOrder()" x-data="{
                mobileKotTab: 'menu',
                collapsedCategories: {},
                toggleCategory(cat) {
                    this.collapsedCategories[cat] = !this.collapsedCategories[cat];
                },
                isCategoryOpen(cat) {
                    return !this.collapsedCategories[cat];
                },
                expandAll() {
                    this.collapsedCategories = {};
                },
                collapseAll(cats) {
                    cats.forEach(c => this.collapsedCategories[c] = true);
                }
            }">
                <main class="stitch-order-modal" role="dialog" aria-modal="true" aria-labelledby="order-modal-title">

                    {{-- ── MODAL HEADER (Sleek, Unbroken Toggle & Clean Layout) ── --}}
                    <header class="stitch-order-header">
                        <div style="display: flex; align-items: center; gap: 8px; min-width: 0; flex-shrink: 0;">
                            <span style="background: #ffedd5; color: #9a3412; font-weight: 800; font-size: 13px; padding: 4px 10px; border-radius: 8px; border: 1.5px solid #fdba74; display: inline-flex; align-items: center; gap: 5px; white-space: nowrap;">
                                <span style="width: 7px; height: 7px; border-radius: 9999px; background: #16a34a;"></span>
                                <span>{{ $destLabel }}</span>
                            </span>
                            <span style="font-size: 10px; font-weight: 700; color: #64748b; white-space: nowrap;" class="hidden sm:inline">
                                KOT #{{ now()->format('Hi') }}
                            </span>
                        </div>

                        {{-- Mobile View Mode Switcher (Sleek Segmented Control - NEVER WRAPS) --}}
                        <div class="lg:hidden" style="display: inline-flex; background: #e2e8f0; padding: 3px; border-radius: 10px; border: 1px solid #cbd5e1; flex-shrink: 0;">
                            <button type="button" @click="mobileKotTab = 'menu'" :style="mobileKotTab === 'menu' ? 'background: #ffffff; color: #9a3412; font-weight: 800; box-shadow: 0 1px 3px rgba(0,0,0,0.12);' : 'background: transparent; color: #64748b; font-weight: 700;'" style="padding: 5px 12px; border-radius: 7px; border: none; font-size: 12px; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; white-space: nowrap; line-height: 1; transition: all 0.15s ease;">
                                <span>🍽️ Dishes</span>
                            </button>
                            <button type="button" @click="mobileKotTab = 'cart'" :style="mobileKotTab === 'cart' ? 'background: #ffffff; color: #9a3412; font-weight: 800; box-shadow: 0 1px 3px rgba(0,0,0,0.12);' : 'background: transparent; color: #64748b; font-weight: 700;'" style="padding: 5px 12px; border-radius: 7px; border: none; font-size: 12px; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; white-space: nowrap; line-height: 1; transition: all 0.15s ease;">
                                <span>🛒 Cart</span>
                                @if($kotTotalItems > 0)
                                    <span style="background: #ea580c; color: #ffffff; font-size: 10px; padding: 1px 6px; border-radius: 9999px; font-weight: 800; line-height: 1.2;">
                                        {{ $kotTotalItems }}
                                    </span>
                                @endif
                            </button>
                        </div>

                        {{-- Close X Button --}}
                        <button wire:click="closePlaceOrder" style="width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; border: 1px solid #cbd5e1; background: #ffffff; color: #475569; cursor: pointer; flex-shrink: 0;" title="Close">
                            <x-heroicon-m-x-mark style="width: 18px; height: 18px;" />
                        </button>
                    </header>

                    {{-- ── MAIN WORKSPACE ── --}}
                    <div class="stitch-order-workspace">

                        {{-- LEFT VERTICAL CATEGORY RAIL (ALWAYS ACCESSIBLE IN DISHES VIEW) --}}
                        <aside class="stitch-order-cat-rail" x-show="window.innerWidth > 1024 || mobileKotTab === 'menu'">
                            <button wire:click="setOrderCategory('all')" class="stitch-cat-btn {{ $placeOrderCategory === 'all' ? 'active' : '' }}" type="button">
                                <div class="stitch-cat-avatar {{ $placeOrderCategory === 'all' ? 'active' : '' }}">
                                    <x-heroicon-s-squares-2x2 style="width: 15px; height: 15px;" />
                                </div>
                                <span class="stitch-cat-label {{ $placeOrderCategory === 'all' ? 'active' : '' }}">All</span>
                                <span class="stitch-cat-badge {{ $placeOrderCategory === 'all' ? 'active' : '' }}">
                                    {{ $totalMenuItemsCount ?? 0 }}
                                </span>
                            </button>

                            @foreach($placeOrderCategories ?? [] as $cat)
                                @php
                                    $isActive = (string) $placeOrderCategory === (string) $cat->id;
                                @endphp
                                <button wire:click="setOrderCategory({{ $cat->id }})" class="stitch-cat-btn {{ $isActive ? 'active' : '' }}" type="button">
                                    <div class="stitch-cat-avatar {{ $isActive ? 'active' : '' }}">
                                        {{ substr($cat->name, 0, 2) }}
                                    </div>
                                    <span class="stitch-cat-label {{ $isActive ? 'active' : '' }}">
                                        {{ $cat->name }}
                                    </span>
                                    <span class="stitch-cat-badge {{ $isActive ? 'active' : '' }}">
                                        {{ $cat->menu_items_count }}
                                    </span>
                                </button>
                            @endforeach
                        </aside>

                        {{-- CENTER DISH CATALOG (DISHES TAB) --}}
                        <section class="stitch-order-catalog" x-show="window.innerWidth > 1024 || mobileKotTab === 'menu'" style="display: flex; flex-direction: column;">
                            
                            {{-- 1. Search + Veg Filter Bar (Compact & Sleek) --}}
                            <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 8px; flex-shrink: 0;">
                                <div style="position: relative; flex: 1;">
                                    <x-heroicon-m-magnifying-glass style="width: 14px; height: 14px; color: #94a3b8; position: absolute; left: 8px; top: 9px;" />
                                    <input wire:model.live.debounce.250ms="placeOrderSearch" type="text" placeholder="Search dish..." style="width: 100%; box-sizing: border-box; padding: 6px 8px 6px 28px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; font-size: 12px; color: #0f172a; outline: none;">
                                </div>

                                <div style="display: flex; align-items: center; gap: 2px; background: #ffffff; padding: 2px; border-radius: 8px; border: 1px solid #cbd5e1; flex-shrink: 0;">
                                    <button wire:click="setOrderVegFilter('all')" style="padding: 4px 7px; border-radius: 6px; font-size: 10px; font-weight: 700; border: none; cursor: pointer; background: {{ $placeOrderVegFilter === 'all' ? '#0f172a' : 'transparent' }}; color: {{ $placeOrderVegFilter === 'all' ? '#ffffff' : '#475569' }};">
                                        All
                                    </button>
                                    <button wire:click="setOrderVegFilter('veg')" style="padding: 4px 7px; border-radius: 6px; font-size: 10px; font-weight: 700; border: none; cursor: pointer; display: flex; align-items: center; gap: 3px; background: {{ $placeOrderVegFilter === 'veg' ? '#059669' : 'transparent' }}; color: {{ $placeOrderVegFilter === 'veg' ? '#ffffff' : '#059669' }};">
                                        <span class="stitch-veg-icon" style="width: 9px; height: 9px;"><span style="width: 3.5px; height: 3.5px;"></span></span>
                                        Veg
                                    </button>
                                    <button wire:click="setOrderVegFilter('non-veg')" style="padding: 4px 7px; border-radius: 6px; font-size: 10px; font-weight: 700; border: none; cursor: pointer; display: flex; align-items: center; gap: 3px; background: {{ $placeOrderVegFilter === 'non-veg' ? '#dc2626' : 'transparent' }}; color: {{ $placeOrderVegFilter === 'non-veg' ? '#ffffff' : '#dc2626' }};">
                                        <span class="stitch-nonveg-icon" style="width: 9px; height: 9px;"><span style="border-bottom-width: 4px;"></span></span>
                                        Non-Veg
                                    </button>
                                </div>
                            </div>

                            {{-- 2. Dishes List (GROUPED CATEGORY-WISE & EXTENDABLE/COLLAPSIBLE) --}}
                            @php
                                $groupedItems = $placeOrderMenuItems->groupBy(fn($i) => $i->category?->name ?? 'Dishes');
                            @endphp

                            {{-- Subheader with Expand/Collapse All buttons --}}
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; flex-shrink: 0; padding: 0 2px;">
                                <span style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">
                                    Categories (Tap to open/close)
                                </span>
                                <div style="display: flex; gap: 4px;">
                                    <button type="button" @click="expandAll()" style="font-size: 10px; font-weight: 700; color: #2563eb; background: #eff6ff; border: 1px solid #bfdbfe; padding: 2px 7px; border-radius: 5px; cursor: pointer;">
                                        ▾ Expand All
                                    </button>
                                    <button type="button" @click="collapseAll({{ json_encode($groupedItems->keys()->map(fn($k) => md5($k))->toArray()) }})" style="font-size: 10px; font-weight: 700; color: #64748b; background: #f1f5f9; border: 1px solid #cbd5e1; padding: 2px 7px; border-radius: 5px; cursor: pointer;">
                                        ▴ Collapse All
                                    </button>
                                </div>
                            </div>

                            <div style="flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 6px; padding-bottom: 55px;">
                                @forelse($groupedItems as $categoryName => $itemsInGroup)
                                    @php $catKey = md5($categoryName); @endphp
                                    <div style="border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; background: #ffffff;">
                                        {{-- Clickable Accordion Header Bar --}}
                                        <div 
                                            @click="toggleCategory('{{ $catKey }}')" 
                                            style="background: #f8fafc; padding: 7px 10px; font-size: 11px; font-weight: 800; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; justify-content: space-between; border-left: 3.5px solid #f97316; cursor: pointer; user-select: none;"
                                            title="Click to open or close this category"
                                        >
                                            <div style="display: flex; align-items: center; gap: 6px;">
                                                <span style="font-size: 10px; color: #f97316; display: inline-block; transition: transform 0.2s ease;" :style="isCategoryOpen('{{ $catKey }}') ? 'transform: rotate(0deg);' : 'transform: rotate(-90deg);'">
                                                    ▼
                                                </span>
                                                <span>📁 {{ $categoryName }}</span>
                                            </div>

                                            <div style="display: flex; align-items: center; gap: 6px;">
                                                <span style="font-size: 10px; font-weight: 800; background: #ffffff; color: #475569; padding: 1px 7px; border-radius: 9999px; border: 1px solid #cbd5e1;">
                                                    {{ $itemsInGroup->count() }} items
                                                </span>
                                                <span style="font-size: 10px; font-weight: 600; color: #94a3b8;" x-text="isCategoryOpen('{{ $catKey }}') ? 'Hide ▴' : 'Show ▾'"></span>
                                            </div>
                                        </div>

                                        {{-- Collapsible Dishes Content --}}
                                        <div x-show="isCategoryOpen('{{ $catKey }}')" x-transition style="padding: 6px; display: flex; flex-direction: column; gap: 6px; background: rgba(248, 249, 255, 0.4);">
                                            @foreach($itemsInGroup as $mItem)
                                                @php
                                                    $cQty = $placeOrderCart[$mItem->id]['quantity'] ?? 0;
                                                    $isVeg = ($mItem->type ?? 'veg') === 'veg';
                                                    $isOos = $mItem->track_stock && $mItem->stock_quantity !== null && $mItem->stock_quantity <= 0;
                                                @endphp
                                                <div class="stitch-dish-row">
                                                    {{-- Left: Veg Icon + Name + Price --}}
                                                    <div style="flex: 1; min-width: 0; padding-right: 6px;">
                                                        <div style="display: flex; align-items: center; gap: 5px; margin-bottom: 2px;">
                                                            @if($isVeg)
                                                                <span class="stitch-veg-icon" title="Vegetarian"><span></span></span>
                                                            @else
                                                                <span class="stitch-nonveg-icon" title="Non-Vegetarian"><span></span></span>
                                                            @endif
                                                            <span style="font-size: 13px; font-weight: 800; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                                {{ $mItem->name }}
                                                            </span>
                                                        </div>

                                                        <div style="display: flex; align-items: baseline; gap: 6px; font-size: 11px;">
                                                            <span style="font-weight: 900; color: #a33900; font-family: monospace; font-size: 13px;">
                                                                ₹{{ number_format($mItem->price, 0) }}
                                                            </span>
                                                            @if($mItem->track_stock && $mItem->stock_quantity !== null)
                                                                <span style="font-size: 10px; font-weight: 700; color: {{ $isOos ? '#e11d48' : '#059669' }};">
                                                                    {{ $isOos ? 'Sold Out' : "Stock: {$mItem->stock_quantity}" }}
                                                                </span>
                                                            @endif
                                                        </div>

                                                        @if($mItem->description)
                                                            <div style="font-size: 10px; color: #64748b; margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                                {{ $mItem->description }}
                                                            </div>
                                                        @endif
                                                    </div>

                                                    {{-- Right: + ADD / Stepper Button --}}
                                                    <div style="flex-shrink: 0;">
                                                        @if($isOos)
                                                            <span style="background: #fff1f2; color: #e11d48; border: 1px solid #fecdd3; border-radius: 6px; padding: 3px 6px; font-size: 10px; font-weight: 800;">
                                                                OUT
                                                            </span>
                                                        @elseif($cQty > 0)
                                                            <div style="display: flex; align-items: center; background: #fff7ed; border: 1.5px solid #fdba74; border-radius: 8px; padding: 2px;">
                                                                <button type="button" wire:click="decreaseCart({{ $mItem->id }})" style="width: 28px; height: 28px; border-radius: 6px; border: none; background: #ffffff; color: #a33900; font-weight: 800; font-size: 15px; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 2px rgba(0,0,0,0.06);">−</button>
                                                                <span style="padding: 0 8px; font-size: 13px; font-weight: 900; color: #a33900; min-width: 16px; text-align: center;">{{ $cQty }}</span>
                                                                <button type="button" wire:click="addToCart({{ $mItem->id }})" style="width: 28px; height: 28px; border-radius: 6px; border: none; background: #a33900; color: #ffffff; font-weight: 800; font-size: 15px; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 2px rgba(0,0,0,0.06);">+</button>
                                                            </div>
                                                        @else
                                                            <button type="button" wire:click="addToCart({{ $mItem->id }})" style="min-width: 64px; height: 32px; background: #ffffff; border: 1.5px solid #fdba74; color: #a33900; border-radius: 8px; font-size: 12px; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 3px; box-shadow: 0 1px 2px rgba(0,0,0,0.04);">
                                                                <span>+ ADD</span>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @empty
                                    <div style="text-align: center; padding: 24px 10px; background: #ffffff; border: 1px dashed #cbd5e1; border-radius: 10px;">
                                        <p style="font-size: 12px; font-weight: 700; color: #64748b; margin: 0;">No dishes found matching filter.</p>
                                    </div>
                                @endforelse
                            </div>

                            {{-- Mobile Sticky Floating Cart Bar (Visible on Menu tab when dishes selected) --}}
                            @if($kotTotalItems > 0)
                                <div class="lg:hidden" style="position: sticky; bottom: 0; left: 0; right: 0; background: #0f172a; color: #ffffff; padding: 10px 14px; border-radius: 10px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 -2px 14px rgba(0,0,0,0.25); z-index: 50; margin-top: 4px;">
                                    <div>
                                        <div style="font-size: 11px; color: #94a3b8;">{{ $kotTotalItems }} item(s) • {{ $kotTotalQty }} qty</div>
                                        <div style="font-size: 16px; font-weight: 900; color: #4ade80; font-family: monospace;">₹{{ number_format($kotTotal, 2) }}</div>
                                    </div>
                                    <button type="button" @click="mobileKotTab = 'cart'" style="background: #a33900; color: #ffffff; border: none; padding: 8px 14px; border-radius: 8px; font-size: 12px; font-weight: 800; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                                        <span>View Cart & Send KOT</span>
                                        <span>→</span>
                                    </button>
                                </div>
                            @endif
                        </section>

                        {{-- RIGHT ORDER TICKET TRAY & SUMMARY (CART TAB ON MOBILE) --}}
                        <aside class="stitch-order-tray" x-show="window.innerWidth > 1024 || mobileKotTab === 'cart'">
                            {{-- Tray Header --}}
                            <div style="padding: 10px 14px; border-bottom: 1px solid #e2e8f0; background: #ffffff; flex-shrink: 0; display: flex; align-items: center; justify-content: space-between;">
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <h3 style="font-size: 13px; font-weight: 800; text-transform: uppercase; color: #0f172a; margin: 0;">
                                        Order Ticket (KOT)
                                    </h3>
                                    <span style="font-size: 10px; font-weight: 800; padding: 2px 6px; border-radius: 9999px; background: #ffedd5; color: #a33900;">
                                        {{ $kotTotalItems }} Items
                                    </span>
                                </div>

                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <button type="button" class="lg:hidden" @click="mobileKotTab = 'menu'" style="font-size: 12px; font-weight: 700; color: #2563eb; border: none; background: transparent; cursor: pointer;">
                                        ← Add Dishes
                                    </button>
                                    @if($kotTotalItems > 0)
                                        <button wire:click="clearCart" style="font-size: 11px; font-weight: 700; color: #e11d48; border: none; background: transparent; cursor: pointer; text-decoration: underline;">
                                            Clear
                                        </button>
                                    @endif
                                </div>
                            </div>

                            {{-- Context Settings inside Cart View: Customer, Payment & Chef Instructions --}}
                            <div style="padding: 10px 14px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; flex-direction: column; gap: 8px; flex-shrink: 0;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                    <div>
                                        <label style="display: block; font-size: 9px; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 2px;">
                                            Guest Name
                                        </label>
                                        <input wire:model.live.debounce.300ms="placeOrderCustomerName" type="text" placeholder="Guest name..." style="width: 100%; box-sizing: border-box; padding: 5px 8px; border-radius: 6px; border: 1px solid #cbd5e1; background: #ffffff; font-size: 11px; font-weight: 700; color: #0f172a; outline: none;">
                                    </div>

                                    <div>
                                        <label style="display: block; font-size: 9px; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 2px;">
                                            Payment Status
                                        </label>
                                        <select wire:model.live="placeOrderPaymentStatus" style="width: 100%; box-sizing: border-box; padding: 5px 6px; border-radius: 6px; border: 1px solid #cbd5e1; background: #ffffff; font-size: 11px; font-weight: 700; color: #0f172a; outline: none;">
                                            <option value="pending">⏳ Pay Later</option>
                                            <option value="paid_upi">⚡ Paid UPI</option>
                                            <option value="paid_card">💳 Paid Card</option>
                                            <option value="paid_cash">💵 Paid Cash</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label style="display: block; font-size: 9px; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 2px;">
                                        Special Cooking Notes
                                    </label>
                                    <input wire:model.live.debounce.300ms="placeOrderNotes" type="text" placeholder="e.g. less spicy, urgent..." style="width: 100%; box-sizing: border-box; padding: 5px 8px; border-radius: 6px; border: 1px solid #cbd5e1; background: #ffffff; font-size: 11px; color: #0f172a; outline: none;">
                                </div>

                                {{-- KOT Priority Pills --}}
                                <div>
                                    <span style="display: block; font-size: 9px; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 3px;">
                                        KOT Priority
                                    </span>
                                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px;">
                                        <label style="cursor: pointer; text-align: center;">
                                            <input wire:model.live="placeOrderPriority" type="radio" value="normal" style="display: none;">
                                            <span style="display: block; padding: 4px 2px; font-size: 10px; font-weight: 700; border-radius: 6px; border: 1px solid {{ $placeOrderPriority === 'normal' ? '#0f172a' : '#cbd5e1' }}; background: {{ $placeOrderPriority === 'normal' ? '#0f172a' : '#ffffff' }}; color: {{ $placeOrderPriority === 'normal' ? '#ffffff' : '#475569' }};">
                                                Standard
                                            </span>
                                        </label>
                                        <label style="cursor: pointer; text-align: center;">
                                            <input wire:model.live="placeOrderPriority" type="radio" value="urgent" style="display: none;">
                                            <span style="display: block; padding: 4px 2px; font-size: 10px; font-weight: 700; border-radius: 6px; border: 1px solid {{ $placeOrderPriority === 'urgent' ? '#a33900' : '#cbd5e1' }}; background: {{ $placeOrderPriority === 'urgent' ? '#a33900' : '#ffffff' }}; color: {{ $placeOrderPriority === 'urgent' ? '#ffffff' : '#a33900' }};">
                                                ⚡ Rush
                                            </span>
                                        </label>
                                        <label style="cursor: pointer; text-align: center;">
                                            <input wire:model.live="placeOrderPriority" type="radio" value="hold" style="display: none;">
                                            <span style="display: block; padding: 4px 2px; font-size: 10px; font-weight: 700; border-radius: 6px; border: 1px solid {{ $placeOrderPriority === 'hold' ? '#4f46e5' : '#cbd5e1' }}; background: {{ $placeOrderPriority === 'hold' ? '#4f46e5' : '#ffffff' }}; color: {{ $placeOrderPriority === 'hold' ? '#ffffff' : '#4f46e5' }};">
                                                ⏸️ Hold
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- Selected Items Scroll Area --}}
                            <div style="padding: 10px 14px; display: flex; flex-direction: column; gap: 6px; flex: 1; overflow-y: auto;">
                                @forelse($placeOrderCart as $cId => $cItem)
                                    <div style="padding: 8px 10px; border-radius: 8px; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
                                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                                            <div style="display: flex; align-items: center; gap: 5px;">
                                                @if(($cItem['type'] ?? 'veg') === 'veg')
                                                    <span class="stitch-veg-icon" style="width: 11px; height: 11px;"><span style="width: 4px; height: 4px;"></span></span>
                                                @else
                                                    <span class="stitch-nonveg-icon" style="width: 11px; height: 11px;"><span style="border-bottom-width: 4px;"></span></span>
                                                @endif
                                                <span style="font-size: 12px; font-weight: 800; color: #0f172a;">{{ $cItem['name'] }}</span>
                                            </div>
                                            <span style="font-size: 12px; font-weight: 900; font-family: monospace; color: #0f172a;">₹{{ number_format($cItem['unit_price'] * $cItem['quantity'], 2) }}</span>
                                        </div>

                                        <div style="display: flex; align-items: center; justify-content: space-between; font-size: 11px; color: #64748b;">
                                            <div style="display: flex; align-items: center; gap: 5px;">
                                                <button type="button" wire:click="decreaseCart({{ $cId }})" style="width: 24px; height: 24px; border-radius: 4px; border: 1px solid #cbd5e1; background: #f8fafc; font-weight: 800; cursor: pointer;">−</button>
                                                <span style="font-weight: 800; color: #0f172a; min-width: 16px; text-align: center;">{{ $cItem['quantity'] }}</span>
                                                <button type="button" wire:click="addToCart({{ $cId }})" style="width: 24px; height: 24px; border-radius: 4px; border: 1px solid #cbd5e1; background: #f8fafc; font-weight: 800; cursor: pointer;">+</button>
                                                <span style="margin-left: 2px;">× ₹{{ number_format($cItem['unit_price'], 0) }}</span>
                                            </div>

                                            <button type="button" wire:click="removeFromCart({{ $cId }})" style="border: none; background: transparent; color: #ef4444; font-size: 11px; font-weight: 700; cursor: pointer; padding: 2px;">
                                                Remove
                                            </button>
                                        </div>

                                        {{-- Line Note --}}
                                        <div style="margin-top: 4px;">
                                            <input wire:model.live.debounce.300ms="placeOrderCart.{{ $cId }}.notes" type="text" placeholder="Line note (e.g. less oil)..." style="width: 100%; box-sizing: border-box; font-size: 10px; font-style: italic; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 3px 6px; color: #334155; outline: none;">
                                        </div>
                                    </div>
                                @empty
                                    <div style="text-align: center; padding: 24px 10px; background: #ffffff; border-radius: 8px; border: 1px dashed #cbd5e1;">
                                        <p style="font-size: 12px; font-weight: 700; color: #475569; margin: 0;">Your cart is empty.</p>
                                        <button type="button" @click="mobileKotTab = 'menu'" class="lg:hidden" style="margin-top: 6px; background: #a33900; color: #ffffff; border: none; padding: 5px 10px; border-radius: 6px; font-size: 11px; font-weight: 700;">
                                            Browse Menu
                                        </button>
                                    </div>
                                @endforelse
                            </div>

                            {{-- Financial Computation Box (NO GST - GST ADDED AT BILLING) --}}
                            <div style="padding: 10px 14px; background: #ffffff; border-top: 1px solid #e2e8f0; flex-shrink: 0; font-size: 11px; display: flex; flex-direction: column; gap: 4px;">
                                <div style="display: flex; justify-content: space-between; color: #64748b;">
                                    <span>Item Subtotal</span>
                                    <span style="font-weight: 700; color: #0f172a; font-family: monospace;">₹{{ number_format($kotSubtotal, 2) }}</span>
                                </div>
                                <div style="font-size: 10px; color: #94a3b8; font-style: italic;">
                                    * Taxes (GST) will be calculated at billing checkout.
                                </div>
                                <div style="display: flex; align-items: baseline; justify-content: space-between; padding-top: 4px; margin-top: 2px; border-top: 1px dashed #cbd5e1;">
                                    <span style="font-size: 12px; font-weight: 800; color: #0f172a;">KOT Total</span>
                                    <span style="font-size: 17px; font-weight: 900; color: #a33900; font-family: monospace;">₹{{ number_format($kotTotal, 2) }}</span>
                                </div>
                            </div>
                        </aside>
                    </div>

                    {{-- ── MODAL FOOTER (ALWAYS VISIBLE & UNBLOCKED BY ANY ADMIN FOOTER) ── --}}
                    <footer class="stitch-order-footer">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <label style="display: flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 600; color: #334155; cursor: pointer; user-select: none;">
                                <input wire:model="placeOrderAutoPrint" type="checkbox" style="border-radius: 4px; color: #a33900;">
                                <span>Print Slip</span>
                            </label>
                            @if($kotTotalItems > 0)
                                <span style="color: #cbd5e1;">|</span>
                                <button type="button" wire:click="clearCart" style="border: none; background: transparent; font-size: 11px; color: #64748b; font-weight: 600; cursor: pointer; text-decoration: underline;">
                                    Reset
                                </button>
                            @endif
                        </div>

                        <div style="display: flex; align-items: center; gap: 6px;">
                            <button type="button" wire:click="closePlaceOrder" style="padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #475569; font-weight: 700; font-size: 12px; cursor: pointer;">
                                Cancel
                            </button>
                            <button type="button" wire:click="submitPlaceOrder" wire:loading.attr="disabled" style="min-height: 40px; padding: 8px 16px; border-radius: 8px; border: none; background: #a33900; color: #ffffff; font-weight: 800; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 5px; box-shadow: 0 4px 12px rgba(163, 57, 0, 0.35);">
                                <svg style="width: 15px; height: 15px;" class="animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <span>Send to Kitchen (KOT)</span>
                            </button>
                        </div>
                    </footer>
                </main>
            </div>
        @endif

        {{-- ── 8. TABLE DETAILS & ORDER HISTORY POP-UP MODAL ── --}}
        @if($viewingTableId)
            @php
                $vTable = $this->viewingTable;
                $vOrders = $this->viewingTableOrders;
                $vSession = $vTable?->qrSessions->where('is_active', true)->first();
                $vStatus = $vTable?->status ?? 'available';
                $vTotalAmount = $vOrders->sum(fn($o) => $o->confirmed_total ?? $o->total_amount);
                $vTotalItems = $vOrders->sum(fn($o) => $o->items->sum('quantity'));
            @endphp

            <div class="stitch-order-overlay" wire:click.self="closeTableDetails" @keydown.escape.window="$wire.closeTableDetails()">
                <div style="background: #ffffff; width: 100%; max-width: 580px; max-height: 88vh; border-radius: 16px; box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.4); border: 1px solid #e2e8f0; display: flex; flex-direction: column; overflow: hidden; position: relative;">
                    
                    {{-- Modal Header --}}
                    <div style="padding: 12px 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="background: #ffedd5; color: #9a3412; font-size: 15px; font-weight: 800; padding: 4px 10px; border-radius: 8px; border: 1.5px solid #fdba74;">
                                🍽️ {{ $vTable ? $this->formatTableNumber($vTable->table_number) : 'Table Details' }}
                            </span>
                            <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; padding: 3px 8px; border-radius: 6px; 
                                @if($vStatus === 'occupied') background: #dbeafe; color: #1d4ed8;
                                @elseif($vStatus === 'cleaning') background: #fef9c3; color: #854d0e;
                                @else background: #dcfce7; color: #15803d; @endif">
                                {{ $vStatus }}
                            </span>
                        </div>

                        <button wire:click="closeTableDetails" style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #64748b; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                            <x-heroicon-m-x-mark style="width: 18px; height: 18px;" />
                        </button>
                    </div>

                    {{-- Body Content (Scrollable) --}}
                    <div style="padding: 14px 16px; overflow-y: auto; flex: 1; display: flex; flex-direction: column; gap: 12px;">
                        
                        {{-- Current Session & Guest Info Capsule --}}
                        <div style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px;">
                                <div>
                                    <span style="font-size: 10px; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px;">Current Guest</span>
                                    <div style="font-size: 16px; font-weight: 800; color: #0f172a;">
                                        {{ $vSession?->customer_name ?: ($vStatus === 'occupied' ? 'Dining Guest' : 'No Active Guest') }}
                                    </div>
                                </div>

                                <div style="text-align: right;">
                                    <span style="font-size: 10px; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px;">Running Total</span>
                                    <div style="font-size: 18px; font-weight: 900; color: #a33900; font-family: monospace;">
                                        ₹{{ number_format($vTotalAmount, 2) }}
                                    </div>
                                </div>
                            </div>

                            <div style="display: flex; align-items: center; justify-content: space-between; font-size: 11px; color: #64748b; border-top: 1px dashed #e2e8f0; padding-top: 6px; margin-top: 4px;">
                                <div>
                                    <span>👥 {{ $vTable?->seating_capacity ?? 4 }} Seater</span>
                                    @if($vSession)
                                        <span style="margin-left: 6px;">• Seated: <b>{{ $vSession->created_at->format('h:i A') }}</b> ({{ $vSession->created_at->diffForHumans(null, true) }} ago)</span>
                                    @endif
                                </div>
                                <div>
                                    <b>{{ $vOrders->count() }}</b> Orders • <b>{{ $vTotalItems }}</b> Items
                                </div>
                            </div>
                        </div>

                        {{-- Order History Header --}}
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 4px;">
                            <div style="font-size: 13px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 5px;">
                                <span>📜</span>
                                <span>Customer Order History</span>
                                <span style="background: #e2e8f0; color: #334155; font-size: 10px; padding: 1px 6px; border-radius: 9999px;">
                                    {{ $vOrders->count() }}
                                </span>
                            </div>
                            <span style="font-size: 11px; color: #64748b;">Sorted newest first</span>
                        </div>

                        {{-- Orders List --}}
                        @if($vOrders->isEmpty())
                            <div style="text-align: center; padding: 28px 12px; background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 12px;">
                                <div style="font-size: 30px; margin-bottom: 6px;">🍽️</div>
                                <div style="font-size: 14px; font-weight: 800; color: #1e293b;">No Orders Placed Yet</div>
                                <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                                    No KOT orders have been generated for this session.
                                </div>
                            </div>
                        @else
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                @foreach($vOrders as $ord)
                                    @php
                                        $whoPlaced = $this->getOrderPlacedBy($ord);
                                        $ordStatus = $ord->status ?? 'placed';
                                        $ordStatusBg = match($ordStatus) {
                                            'ready' => '#dcfce7',
                                            'served' => '#f1f5f9',
                                            'preparing' => '#fef3c7',
                                            'accepted' => '#e0e7ff',
                                            default => '#ffedd5',
                                        };
                                        $ordStatusColor = match($ordStatus) {
                                            'ready' => '#15803d',
                                            'served' => '#475569',
                                            'preparing' => '#b45309',
                                            'accepted' => '#4338ca',
                                            default => '#c2410c',
                                        };
                                    @endphp

                                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                                        {{-- Order Header Line --}}
                                        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-bottom: 6px;">
                                            <div>
                                                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                                    <span style="font-size: 14px; font-weight: 800; color: #0f172a;">Order #{{ $ord->id }}</span>
                                                    <span style="font-size: 10px; font-weight: 800; background: {{ $ordStatusBg }}; color: {{ $ordStatusColor }}; padding: 2px 7px; border-radius: 9999px; text-transform: uppercase;">
                                                        {{ $ordStatus }}
                                                    </span>
                                                </div>
                                                <div style="font-size: 10px; color: #94a3b8; margin-top: 2px;">
                                                    {{ $ord->created_at->format('h:i A') }} • {{ $ord->created_at->diffForHumans(null, true) }} ago
                                                </div>
                                            </div>

                                            <div style="text-align: right;">
                                                <div style="font-size: 14px; font-weight: 900; color: #a33900; font-family: monospace;">
                                                    ₹{{ number_format($ord->confirmed_total ?? $ord->total_amount, 2) }}
                                                </div>
                                                <div style="font-size: 10px; color: #64748b;">
                                                    {{ $ord->items->sum('quantity') }} items
                                                </div>
                                            </div>
                                        </div>

                                        {{-- WHO PLACED THAT ORDER (Prominent Badge) --}}
                                        <div style="margin: 6px 0;">
                                            @if($whoPlaced['is_staff'])
                                                <span style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 11px; font-weight: 800; padding: 3px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;">
                                                    <span>👔</span>
                                                    <span>{{ $whoPlaced['label'] }}</span>
                                                </span>
                                            @else
                                                <span style="background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; font-size: 11px; font-weight: 800; padding: 3px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;">
                                                    <span>📱</span>
                                                    <span>{{ $whoPlaced['label'] }}</span>
                                                </span>
                                            @endif
                                        </div>

                                        {{-- Items breakdown --}}
                                        <div style="background: #f8fafc; border-radius: 8px; padding: 8px; margin-top: 6px; display: flex; flex-direction: column; gap: 4px;">
                                            @foreach($ord->items as $item)
                                                <div style="display: flex; align-items: baseline; justify-content: space-between; font-size: 11px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 3px;">
                                                    <div style="font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 4px; flex-wrap: wrap;">
                                                        <span style="color: #64748b; font-weight: 600;">{{ $item->quantity }}x</span>
                                                        <span>{{ $item->item_name }}</span>
                                                        @php
                                                            $iStatus = $item->status ?? 'placed';
                                                            $iColor = match($iStatus) {
                                                                'ready' => '#10b981',
                                                                'preparing' => '#f97316',
                                                                default => '#64748b'
                                                            };
                                                            $iBg = match($iStatus) {
                                                                'ready' => '#dcfce7',
                                                                'preparing' => '#ffedd5',
                                                                default => '#f1f5f9'
                                                            };
                                                        @endphp
                                                        <span style="font-size: 8px; font-weight: 800; padding: 1px 4px; border-radius: 4px; background: {{ $iBg }}; color: {{ $iColor }}; text-transform: uppercase;">
                                                            {{ $iStatus === 'preparing' ? 'Cooking' : ucfirst($iStatus) }}
                                                        </span>
                                                        @if($item->notes)
                                                            <span style="font-size: 10px; color: #e11d48; font-style: italic; margin-left: 2px;">({{ $item->notes }})</span>
                                                        @endif
                                                    </div>
                                                    <span style="font-family: monospace; font-weight: 700; color: #475569;">
                                                        ₹{{ number_format($item->total_price, 0) }}
                                                    </span>
                                                </div>
                                            @endforeach

                                            @if($ord->notes)
                                                <div style="font-size: 10px; color: #be123c; background: #fff1f2; padding: 3px 6px; border-radius: 4px; font-weight: 600; margin-top: 3px;">
                                                    ⚠️ Instructions: {{ $ord->notes }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Modal Footer Action Buttons --}}
                    <div style="padding: 10px 16px; background: #ffffff; border-top: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-shrink: 0;">
                        <button type="button" wire:click="closeTableDetails" style="padding: 8px 14px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #475569; font-weight: 700; font-size: 12px; cursor: pointer;">
                            Close
                        </button>

                        @if($vStatus === 'occupied')
                            <button type="button" wire:click="openPlaceOrderFromDetails({{ $vTable?->id }})" style="min-height: 38px; padding: 8px 16px; border-radius: 8px; border: none; background: #2563eb; color: #ffffff; font-weight: 800; font-size: 12px; cursor: pointer; display: flex; align-items: center; gap: 5px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);">
                                <span>➕ Add Dishes to Table</span>
                            </button>
                        @elseif($vStatus === 'available')
                            <button type="button" wire:click="openPlaceOrderFromDetails({{ $vTable?->id }})" style="min-height: 38px; padding: 8px 16px; border-radius: 8px; border: none; background: #f16b3f; color: #ffffff; font-weight: 800; font-size: 12px; cursor: pointer; display: flex; align-items: center; gap: 5px; box-shadow: 0 4px 12px rgba(241, 107, 63, 0.3);">
                                <span>➕ Take Order</span>
                            </button>
                        @elseif($vStatus === 'cleaning')
                            <button type="button" wire:click="changeTableStatus({{ $vTable?->id }}, 'available'); closeTableDetails();" style="min-height: 38px; padding: 8px 16px; border-radius: 8px; border: none; background: #16a34a; color: #ffffff; font-weight: 800; font-size: 12px; cursor: pointer; display: flex; align-items: center; gap: 5px; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);">
                                <span>✓ Mark Cleaned & Ready</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- ── 8. MULTIMODAL NOTIFICATION & SYNTHESIZED AUDIO ENGINE ── --}}
    <script>
        (function () {
            let audioCtx = null;

            function getAudioContext() {
                try {
                    if (!audioCtx) {
                        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
                        if (AudioContextClass) {
                            audioCtx = new AudioContextClass();
                        }
                    }
                    if (audioCtx && audioCtx.state === 'suspended') {
                        audioCtx.resume();
                    }
                } catch (e) {
                    console.warn("AudioContext init error:", e);
                }
                return audioCtx;
            }

            // Automatically unlock AudioContext on first user touch/click (crucial for Android Chrome & iOS Safari)
            function unlockAudioOnUserGesture() {
                try {
                    const ctx = getAudioContext();
                    if (ctx && ctx.state === 'suspended') {
                        ctx.resume().then(() => {
                            if (window.Notification) {
                                updateStatusButton(Notification.permission);
                            }
                        });
                    }
                } catch (e) {}
            }

            ['click', 'touchstart', 'touchend', 'pointerdown', 'keydown'].forEach(evt => {
                document.addEventListener(evt, unlockAudioOnUserGesture, { passive: true });
            });

            // Synthesized Chime 1: Waiter Call Alert (Harmonic Double Ping: 880Hz -> 1174Hz)
            function playWaiterCallSound() {
                try {
                    const ctx = getAudioContext();
                    if (!ctx) return;

                    const now = ctx.currentTime;
                    const osc1 = ctx.createOscillator();
                    const gain1 = ctx.createGain();
                    osc1.type = 'sine';
                    osc1.frequency.setValueAtTime(880, now);
                    gain1.gain.setValueAtTime(0.6, now);
                    gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.5);
                    osc1.connect(gain1);
                    gain1.connect(ctx.destination);
                    osc1.start(now);
                    osc1.stop(now + 0.5);

                    const osc2 = ctx.createOscillator();
                    const gain2 = ctx.createGain();
                    osc2.type = 'sine';
                    osc2.frequency.setValueAtTime(1174.66, now + 0.16);
                    gain2.gain.setValueAtTime(0.6, now + 0.16);
                    gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.75);
                    osc2.connect(gain2);
                    gain2.connect(ctx.destination);
                    osc2.start(now + 0.16);
                    osc2.stop(now + 0.75);
                } catch (e) {
                    console.error("Error playing waiter call sound:", e);
                }
            }

            // Synthesized Chime 2: Ready Order Chime (Ascending 4-tone loud bell chime: D5 -> A5 -> D6 -> F#6)
            function playOrderReadySound() {
                try {
                    const ctx = getAudioContext();
                    if (!ctx) return;

                    const now = ctx.currentTime;
                    const notes = [587.33, 880.0, 1174.66, 1479.98];
                    notes.forEach((freq, idx) => {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'triangle';
                        const start = now + (idx * 0.13);
                        osc.frequency.setValueAtTime(freq, start);
                        gain.gain.setValueAtTime(0.7, start);
                        gain.gain.exponentialRampToValueAtTime(0.001, start + 0.55);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start(start);
                        osc.stop(start + 0.55);
                    });
                } catch (e) {
                    console.error("Error playing order ready sound:", e);
                }
            }

            // Synthesized Chime 3: Bill Requested Sound
            function playBillRequestedSound() {
                try {
                    const ctx = getAudioContext();
                    if (!ctx) return;

                    const now = ctx.currentTime;
                    const notes = [1046.5, 1318.5];
                    notes.forEach((freq, idx) => {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'sine';
                        const start = now + (idx * 0.12);
                        osc.frequency.setValueAtTime(freq, start);
                        gain.gain.setValueAtTime(0.5, start);
                        gain.gain.exponentialRampToValueAtTime(0.001, start + 0.4);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start(start);
                        osc.stop(start + 0.4);
                    });
                } catch (e) {
                    console.error("Error playing bill requested sound:", e);
                }
            }

            function resolveSoundType(detail) {
                if (!detail) return '';
                if (typeof detail === 'string') return detail;
                if (detail.type) return detail.type;
                if (Array.isArray(detail) && detail[0]) {
                    if (typeof detail[0] === 'string') return detail[0];
                    if (detail[0].type) return detail[0].type;
                }
                return '';
            }

            function handlePlaySound(data) {
                const type = resolveSoundType(data);
                if (type === 'call') {
                    playWaiterCallSound();
                } else if (type === 'ready') {
                    playOrderReadySound();
                } else if (type === 'bill') {
                    playBillRequestedSound();
                }
            }

            function triggerDesktopNotification(title, body) {
                if ("Notification" in window && Notification.permission === "granted") {
                    try {
                        const n = new Notification(title, {
                            body: body,
                            icon: "/img/annsathilogo.png",
                            requireInteraction: true,
                        });
                        n.onclick = function (event) {
                            event.preventDefault();
                            window.focus();
                            n.close();
                        };
                    } catch (err) {
                        console.warn("Desktop notification error:", err);
                    }
                }
            }

            function updateStatusButton(permission) {
                const btnText = document.getElementById('audio-btn-text');
                const btnIcon = document.getElementById('audio-btn-icon');
                if (!btnText) return;

                if (permission === 'granted') {
                    btnText.innerText = "Audio Alerts Active (Click to Test)";
                    btnIcon.innerText = "🔊";
                } else if (permission === 'denied') {
                    btnText.innerText = "Notifications Blocked (Unblock in browser)";
                    btnIcon.innerText = "🔇";
                } else {
                    btnText.innerText = "Enable Sound & Alerts";
                    btnIcon.innerText = "🔔";
                }
            }

            window.testAudioAndNotifications = function () {
                const ctx = getAudioContext();
                if (ctx && ctx.state === 'suspended') {
                    ctx.resume();
                }
                playOrderReadySound();

                speakText("Audio and food ready alerts are working.");

                if ("Notification" in window) {
                    if (Notification.permission !== "granted") {
                        Notification.requestPermission().then(permission => {
                            updateStatusButton(permission);
                            if (permission === 'granted') {
                                triggerDesktopNotification("🔔 Notifications Enabled!", "Ready order and waiter call alerts will pop up automatically.");
                            }
                        });
                    } else {
                        triggerDesktopNotification("🛎️ Waiter Audio Test", "Audio and notifications are active! Food ready bell just rang.");
                    }
                }
            };

            function speakText(text) {
                if (!('speechSynthesis' in window) || !text) return;
                try {
                    window.speechSynthesis.cancel();
                    const msg = new SpeechSynthesisUtterance(text);
                    const voices = window.speechSynthesis.getVoices();
                    if (voices && voices.length > 0) {
                        const preferredVoice = voices.find(v =>
                            v.lang.includes('gu-IN') ||
                            v.lang.includes('hi-IN') ||
                            v.lang.includes('en-IN')
                        );
                        if (preferredVoice) {
                            msg.voice = preferredVoice;
                        }
                    }
                    msg.rate = 1.0;
                    msg.pitch = 1.0;
                    msg.volume = 1.0;
                    window.speechSynthesis.speak(msg);
                } catch (e) {
                    console.warn("Speech synthesis error:", e);
                }
            }

            function setupListeners() {
                if ("Notification" in window) {
                    updateStatusButton(Notification.permission);
                    if (Notification.permission !== "granted" && Notification.permission !== "denied") {
                        Notification.requestPermission().then(updateStatusButton);
                    }
                }

                window.addEventListener('play-sound', function (e) {
                    handlePlaySound(e.detail);
                });

                document.addEventListener('play-sound', function (e) {
                    handlePlaySound(e.detail);
                });

                window.addEventListener('trigger-browser-notification', function (e) {
                    const data = e.detail && e.detail[0] ? e.detail[0] : (e.detail || {});
                    if (data && data.title) {
                        triggerDesktopNotification(data.title, data.body || '');
                    }
                });

                window.addEventListener('speak-notification', function (e) {
                    const payload = e.detail && e.detail[0] ? e.detail[0] : (e.detail || {});
                    const text = typeof payload === 'string' ? payload : (payload.text || '');
                    speakText(text);
                });

                if (window.Livewire) {
                    Livewire.on('play-sound', data => handlePlaySound(data));
                    Livewire.on('trigger-browser-notification', data => {
                        const payload = Array.isArray(data) ? data[0] : data;
                        if (payload && payload.title) triggerDesktopNotification(payload.title, payload.body || '');
                    });
                    Livewire.on('speak-notification', data => {
                        const payload = Array.isArray(data) ? data[0] : data;
                        const text = typeof payload === 'string' ? payload : (payload.text || '');
                        speakText(text);
                    });
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', setupListeners);
            } else {
                setupListeners();
            }

            document.addEventListener('livewire:navigated', setupListeners);
            document.addEventListener('livewire:init', setupListeners);
        })();
    </script>
</x-filament-panels::page>
