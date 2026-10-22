<x-filament-panels::page>
    <div class="manager-dashboard-container antialiased">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

        <style>
            /* ─── BASE RESETS & SCOPED FONTS ─── */
            .fi-header {
                display: none !important;
            }
            .fi-page, .fi-main {
                max-width: 100% !important;
                padding-top: 0 !important;
            }
            .fi-page-content {
                width: 100% !important;
            }

            .manager-dashboard-container {
                font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                color: #1e293b;
                width: 100%;
                margin-top: -1.25rem;
            }

            /* Custom Subtle Scrollbars */
            .stitch-scroll::-webkit-scrollbar,
            .pos-scroll::-webkit-scrollbar {
                width: 5px;
                height: 5px;
            }
            .stitch-scroll::-webkit-scrollbar-track,
            .pos-scroll::-webkit-scrollbar-track {
                background: #f1f5f9;
                border-radius: 9999px;
            }
            .stitch-scroll::-webkit-scrollbar-thumb,
            .pos-scroll::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 9999px;
            }
            .stitch-scroll::-webkit-scrollbar-thumb:hover,
            .pos-scroll::-webkit-scrollbar-thumb:hover {
                background: #94a3b8;
            }

            /* ─── FLOOR HEADER ─── */
            .stitch-header-row {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
                margin-bottom: 16px;
                padding-top: 4px;
            }
            .stitch-title {
                font-size: 24px;
                font-weight: 800;
                color: #0f172a;
                letter-spacing: -0.5px;
                margin: 0;
            }
            .stitch-subtitle {
                font-size: 13px;
                color: #64748b;
                margin: 2px 0 0 0;
            }
            .stitch-btn-primary {
                background: #f97316;
                color: #ffffff;
                font-weight: 700;
                font-size: 13px;
                padding: 9px 18px;
                border-radius: 12px;
                border: none;
                box-shadow: 0 2px 6px rgba(249, 115, 22, 0.3);
                display: inline-flex;
                align-items: center;
                gap: 8px;
                cursor: pointer;
                transition: all 0.2s;
            }
            .stitch-btn-primary:hover {
                background: #ea580c;
            }

            /* ─── 6-METRIC STAT GRID ─── */
            .stitch-stats-grid {
                display: grid;
                grid-template-columns: repeat(6, 1fr);
                gap: 12px;
                margin-bottom: 16px;
            }
            @media (max-width: 1200px) {
                .stitch-stats-grid {
                    grid-template-columns: repeat(3, 1fr);
                }
            }
            @media (max-width: 640px) {
                .stitch-stats-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }
            .stitch-stat-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                padding: 14px 16px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.04);
                transition: all 0.2s;
            }
            .stitch-stat-card:hover {
                border-color: #cbd5e1;
            }
            .stitch-stat-card.occupied { border-color: #fecdd3; }
            .stitch-stat-card.available { border-color: #a7f3d0; }
            .stitch-stat-card.bill-pending { border-color: #fde68a; }
            .stitch-stat-card.cleaning { border-color: #bfdbfe; }
            .stitch-stat-card.revenue {
                border-color: #fed7aa;
                background: linear-gradient(135deg, #fff7ed 0%, #ffffff 100%);
            }
            .stitch-stat-title {
                font-size: 11px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            .stitch-stat-val-row {
                display: flex;
                align-items: baseline;
                justify-content: space-between;
                margin-top: 6px;
            }
            .stitch-stat-number {
                font-size: 22px;
                font-weight: 800;
                color: #0f172a;
                line-height: 1;
            }
            .stitch-stat-badge {
                font-size: 11px;
                font-weight: 600;
            }
            .stitch-dot {
                width: 8px;
                height: 8px;
                border-radius: 9999px;
                display: inline-block;
            }

            /* ─── FILTER & SEARCH TOOLING BAR ─── */
            .stitch-filter-bar {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                padding: 8px 12px;
                box-shadow: 0 1px 2px rgba(0,0,0,0.03);
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                margin-bottom: 20px;
            }
            .stitch-filter-tabs {
                display: flex;
                align-items: center;
                gap: 6px;
                overflow-x: auto;
            }
            .stitch-tab-btn {
                border: none;
                border-radius: 12px;
                padding: 6px 14px;
                font-size: 12px;
                font-weight: 600;
                cursor: pointer;
                background: transparent;
                color: #475569;
                transition: all 0.15s;
                white-space: nowrap;
            }
            .stitch-tab-btn:hover {
                background: #f1f5f9;
                color: #0f172a;
            }
            .stitch-tab-btn.active {
                background: #0f172a;
                color: #ffffff;
                font-weight: 700;
                box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            }
            .stitch-search-wrapper {
                position: relative;
                width: 240px;
            }
            .stitch-search-input {
                width: 100%;
                box-sizing: border-box;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 6px 12px 6px 32px;
                font-size: 12px;
                color: #1e293b;
                outline: none;
                transition: all 0.2s;
            }
            .stitch-search-input:focus {
                border-color: #f97316;
                background: #ffffff;
            }

            /* ─── FLOOR GRID ─── */
            .stitch-floor-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 16px;
            }
            @media (max-width: 1300px) {
                .stitch-floor-grid {
                    grid-template-columns: repeat(3, 1fr);
                }
            }
            @media (max-width: 900px) {
                .stitch-floor-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }
            @media (max-width: 600px) {
                .stitch-floor-grid {
                    grid-template-columns: 1fr;
                }
            }

            /* ─── TABLE CARDS ─── */
            .stitch-card {
                background: #ffffff;
                border-radius: 16px;
                padding: 16px;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                min-height: 200px;
                cursor: pointer;
                transition: all 0.2s;
                box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            }
            .stitch-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 14px -2px rgba(0,0,0,0.08);
            }
            .stitch-card.occupied {
                border: 2px solid #fb923c;
            }
            .stitch-card.available {
                border: 2px dashed #10b981;
            }
            .stitch-card.available:hover {
                border-color: #059669;
            }
            .stitch-card.bill-pending {
                border: 2px solid #fbbf24;
            }
            .stitch-card.cleaning {
                border: 2px solid #93c5fd;
            }
            .stitch-card.parcel {
                border: 2px solid #fdba74;
            }
            .stitch-card-header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
            }
            .stitch-card-title {
                font-size: 18px;
                font-weight: 800;
                color: #0f172a;
                margin: 0;
            }
            .stitch-card-meta {
                font-size: 11px;
                font-weight: 700;
                color: #94a3b8;
                margin-top: 3px;
                display: flex;
                align-items: center;
                gap: 6px;
            }
            .stitch-pill {
                font-size: 10px;
                font-weight: 800;
                text-transform: uppercase;
                padding: 2px 8px;
                border-radius: 9999px;
                letter-spacing: 0.5px;
            }

            /* ─── MODAL OVERLAY & 3-COLUMN ENGINE (Screen 2) ─── */
            .stitch-modal-overlay {
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, 0.65);
                backdrop-filter: blur(4px);
                z-index: 50;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 16px;
            }

            
            /* ─── KOT VISUAL MENU MODAL (Stitch 1a515631d2ff4021b0346acbd2a7a53c) ─── */
            .stitch-order-overlay {
                position: fixed;
                inset: 0;
                background: rgba(11, 28, 48, 0.85);
                backdrop-filter: blur(8px);
                z-index: 99999;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 12px;
            }
            .stitch-order-modal {
                background: #ffffff;
                width: 100%;
                max-width: 1240px;
                height: 94vh;
                max-height: 900px;
                border-radius: 20px;
                box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.4), 0 10px 20px -8px rgba(163, 57, 0, 0.15);
                border: 1px solid rgba(226, 232, 240, 0.9);
                display: flex;
                flex-direction: column;
                overflow: hidden;
                position: relative;
            }
            .stitch-order-header {
                padding: 14px 22px;
                border-bottom: 1px solid #e2e8f0;
                background: #f8f9ff;
                display: flex;
                align-items: center;
                justify-content: space-between;
                flex-shrink: 0;
            }
            .stitch-order-context-bar {
                background: rgba(248, 249, 255, 0.95);
                border-bottom: 1px solid #e2e8f0;
                padding: 12px 22px;
                flex-shrink: 0;
            }
            .stitch-order-workspace {
                flex: 1;
                display: flex;
                flex-direction: row;
                overflow: hidden;
                min-height: 0;
                background: #ffffff;
            }
            @media (max-width: 1024px) {
                .stitch-order-workspace {
                    flex-direction: column;
                    overflow-y: auto;
                }
            }
            .stitch-order-cat-rail {
                width: 96px;
                background: #f4f7fe;
                border-right: 1px solid #e2e8f0;
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 12px 6px;
                overflow-y: auto;
                flex-shrink: 0;
                gap: 8px;
                user-select: none;
            }
            .stitch-order-catalog {
                flex: 1;
                overflow-y: auto;
                padding: 16px 20px;
                background: rgba(248, 249, 255, 0.4);
                min-width: 0;
            }
            .stitch-order-tray {
                width: 360px;
                background: #f8f9ff;
                border-left: 1px solid #e2e8f0;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                flex-shrink: 0;
                overflow-y: auto;
            }
            @media (max-width: 1024px) {
                .stitch-order-tray {
                    width: 100%;
                    border-left: none;
                    border-top: 1px solid #e2e8f0;
                }
            }
            .stitch-order-footer {
                padding: 12px 24px;
                border-top: 1px solid #e2e8f0;
                background: #ffffff;
                display: flex;
                align-items: center;
                justify-content: space-between;
                flex-shrink: 0;
            }
            .stitch-dish-card {
                background: #ffffff;
                border-radius: 14px;
                border: 1px solid #e2e8f0;
                padding: 14px;
                box-shadow: 0 1px 3px rgba(15, 28, 48, 0.04);
                transition: all 0.2s;
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 12px;
            }
            .stitch-dish-card:hover {
                border-color: #f8cbb4;
                box-shadow: 0 4px 14px rgba(163, 57, 0, 0.08);
            }
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
                border: 1.5px solid #e11d48;
                border-radius: 3px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: #ffffff;
                padding: 1px;
            }
            .stitch-nonveg-icon > span {
                width: 6px;
                height: 6px;
                border-radius: 9999px;
                background: #e11d48;
            }

            /* ─── MAKE FILAMENT ACTION MODALS (PLACE ORDER, ASSIGN GUEST) OPEN ON TOP ─── */
            .fi-modal {
                position: relative;
                z-index: 100000 !important;
            }
            .fi-modal-close-overlay {
                z-index: 100001 !important;
            }
            .fi-modal > div:last-child {
                z-index: 100002 !important;
            }
            .fi-modal-window-ctn,
            .fi-modal .fixed.inset-0 {
                z-index: 100002 !important;
            }
            .fi-modal-window {
                z-index: 100003 !important;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5) !important;
            }
            .fi-dropdown-panel {
                z-index: 100010 !important;
            }
            .stitch-modal-box {
                background: #ffffff;
                width: 100%;
                max-width: 1420px;
                height: 90vh;
                max-height: 860px;
                border-radius: 20px;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
                border: 1px solid #e2e8f0;
                display: flex;
                flex-direction: column;
                overflow: hidden;
            }
            .stitch-modal-header {
                padding: 16px 24px;
                border-bottom: 1px solid #e2e8f0;
                background: #ffffff;
                display: flex;
                align-items: center;
                justify-content: space-between;
                flex-shrink: 0;
            }
            .stitch-modal-body {
                display: flex;
                flex-direction: row;
                flex: 1;
                overflow: hidden;
                background: #f8fafc;
            }
            @media (max-width: 1024px) {
                .stitch-modal-body {
                    flex-direction: column;
                    overflow-y: auto;
                }
            }
            .stitch-col-guests {
                width: 320px;
                flex-shrink: 0;
                border-right: 1px solid #e2e8f0;
                background: #ffffff;
                padding: 18px;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                overflow-y: auto;
            }
            .stitch-col-orders {
                flex: 1;
                border-right: 1px solid #e2e8f0;
                background: #f8fafc;
                padding: 20px;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                overflow-y: auto;
                min-width: 0;
            }
            .stitch-col-billing {
                width: 380px;
                flex-shrink: 0;
                background: #ffffff;
                padding: 20px;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                overflow-y: auto;
            }
            .stitch-modal-footer {
                padding: 10px 24px;
                background: #f1f5f9;
                border-top: 1px solid #e2e8f0;
                font-size: 12px;
                color: #64748b;
                display: flex;
                align-items: center;
                justify-content: space-between;
                flex-shrink: 0;
            }

            /* Guest Cards */
            .stitch-guest-card {
                padding: 14px;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                margin-bottom: 10px;
                cursor: pointer;
                transition: all 0.2s;
                box-shadow: 0 1px 2px rgba(0,0,0,0.03);
            }
            .stitch-guest-card:hover {
                background: #f8fafc;
                border-color: #cbd5e1;
            }
            .stitch-guest-card.active {
                background: #eff6ff;
                border: 2px solid #2563eb;
                box-shadow: 0 2px 4px rgba(37, 99, 235, 0.1);
            }
            .stitch-guest-avatar {
                width: 32px;
                height: 32px;
                border-radius: 8px;
                background: #f1f5f9;
                color: #64748b;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 700;
            }
            .stitch-guest-avatar.active {
                background: #1e3a8a;
                color: #ffffff;
            }
            .stitch-icon-btn {
                background: transparent;
                border: none;
                padding: 4px;
                color: #94a3b8;
                cursor: pointer;
                border-radius: 4px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .stitch-icon-btn:hover {
                color: #334155;
            }

            /* Animations */
            @keyframes pulseDot {
                0% { opacity: 1; transform: scale(1); }
                50% { opacity: 0.4; transform: scale(0.85); }
                100% { opacity: 1; transform: scale(1); }
            }
            .animate-pulse-dot {
                animation: pulseDot 1.5s infinite ease-in-out;
            }
        </style>

        {{-- ======================================================== --}}
        {{-- 1. FLOOR HEADER (Screen 1)                               --}}
        {{-- ======================================================== --}}
        <div class="stitch-header-row">
            <div>
                <h1 class="stitch-title">Manager Dashboard Control</h1>
                <p class="stitch-subtitle">Real-time floor grid, seating status, live tab totals, and quick guest assignment.</p>
            </div>
            
            <div style="display: flex; align-items: center; gap: 10px;">
                @if($hasRoomsFacility)
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 3px; display: flex; gap: 4px;">
                        <button wire:click="switchTab('tables')" class="stitch-tab-btn {{ $currentTab == 'tables' ? 'active' : '' }}">Tables & Parcels</button>
                        <button wire:click="switchTab('rooms')" class="stitch-tab-btn {{ $currentTab == 'rooms' ? 'active' : '' }}">Room Service</button>
                    </div>
                @endif

                <button wire:click="mountAction('assignGuestAction')" class="stitch-btn-primary">
                    <x-heroicon-s-user-plus style="width: 16px; height: 16px;" />
                    <span>Assign Table (+ Guest)</span>
                </button>
            </div>
        </div>

        {{-- ======================================================== --}}
        {{-- 2. METRIC STAT CARDS BAR (Screen 1)                      --}}
        {{-- ======================================================== --}}
        <div class="stitch-stats-grid">
            {{-- Total Tables --}}
            <div class="stitch-stat-card">
                <div class="stitch-stat-title" style="color: #64748b;">
                    <span>Total Tables</span>
                </div>
                <div class="stitch-stat-val-row">
                    <span class="stitch-stat-number">{{ $totalTablesCount ?? count($tables) }}</span>
                    <span class="stitch-stat-badge" style="color: #94a3b8;">Floor Overview</span>
                </div>
            </div>

            {{-- Occupied --}}
            <div class="stitch-stat-card occupied">
                <div class="stitch-stat-title" style="color: #e11d48;">
                    <span>Occupied</span>
                    <span class="stitch-dot" style="background: #f43f5e;"></span>
                </div>
                <div class="stitch-stat-val-row">
                    <span class="stitch-stat-number">{{ $occupiedCount ?? 0 }}</span>
                    <span class="stitch-stat-badge" style="color: #e11d48;">{{ $occupancyRate ?? 0 }}% Full</span>
                </div>
            </div>

            {{-- Available --}}
            <div class="stitch-stat-card available">
                <div class="stitch-stat-title" style="color: #059669;">
                    <span>Available</span>
                    <span class="stitch-dot" style="background: #10b981;"></span>
                </div>
                <div class="stitch-stat-val-row">
                    <span class="stitch-stat-number">{{ $availableCount ?? 0 }}</span>
                    <span class="stitch-stat-badge" style="color: #059669;">Ready to Seat</span>
                </div>
            </div>

            {{-- Bill Pending --}}
            <div class="stitch-stat-card bill-pending">
                <div class="stitch-stat-title" style="color: #d97706;">
                    <span>Bill Pending</span>
                    <span class="stitch-dot" style="background: #f59e0b;"></span>
                </div>
                <div class="stitch-stat-val-row">
                    <span class="stitch-stat-number">{{ $billPendingCount ?? 0 }}</span>
                    <span class="stitch-stat-badge" style="color: #d97706;">To Settle</span>
                </div>
            </div>

            {{-- Cleaning --}}
            <div class="stitch-stat-card cleaning">
                <div class="stitch-stat-title" style="color: #2563eb;">
                    <span>Cleaning</span>
                    <span class="stitch-dot" style="background: #3b82f6;"></span>
                </div>
                <div class="stitch-stat-val-row">
                    <span class="stitch-stat-number">{{ $cleaningCount ?? 0 }}</span>
                    <span class="stitch-stat-badge" style="color: #2563eb;">Housekeeping</span>
                </div>
            </div>

            {{-- Shift Revenue --}}
            <div class="stitch-stat-card revenue">
                <div class="stitch-stat-title" style="color: #c2410c;">
                    <span>Shift Revenue</span>
                </div>
                <div class="stitch-stat-val-row">
                    <span class="stitch-stat-number" style="color: #ea580c; font-family: monospace;">₹{{ number_format($shiftRevenue ?? 0, 0) }}</span>
                    <span class="stitch-stat-badge" style="color: #64748b;">{{ $shiftPaidCount ?? 0 }} Paid Today</span>
                </div>
            </div>
        </div>

        {{-- ======================================================== --}}
        {{-- 3. FILTER & SEARCH TOOLING BAR (Screen 1)                --}}
        {{-- ======================================================== --}}
        <div class="stitch-filter-bar">
            {{-- Filter Tabs --}}
            <div class="stitch-filter-tabs">
                <button wire:click="setTableFilter('all')" class="stitch-tab-btn {{ $tableFilter === 'all' ? 'active' : '' }}">
                    All Tables ({{ $totalTablesCount ?? count($tables) }})
                </button>
                <button wire:click="setTableFilter('occupied')" class="stitch-tab-btn {{ $tableFilter === 'occupied' ? 'active' : '' }}">
                    Occupied ({{ $occupiedCount ?? 0 }})
                </button>
                <button wire:click="setTableFilter('available')" class="stitch-tab-btn {{ $tableFilter === 'available' ? 'active' : '' }}">
                    Available ({{ $availableCount ?? 0 }})
                </button>
                <button wire:click="setTableFilter('bill_pending')" class="stitch-tab-btn {{ $tableFilter === 'bill_pending' ? 'active' : '' }}">
                    Bill Pending ({{ $billPendingCount ?? 0 }})
                </button>
                <button wire:click="setTableFilter('cleaning')" class="stitch-tab-btn {{ $tableFilter === 'cleaning' ? 'active' : '' }}">
                    Cleaning ({{ $cleaningCount ?? 0 }})
                </button>
            </div>

            {{-- Search Bar --}}
            <div class="stitch-search-wrapper">
                <x-heroicon-m-magnifying-glass style="width: 14px; height: 14px; position: absolute; left: 10px; top: 9px; color: #94a3b8;" />
                <input wire:model.live.debounce.300ms="searchQuery" class="stitch-search-input" placeholder="Search table or guest..." type="text"/>
            </div>
        </div>

        {{-- ======================================================== --}}
        {{-- 4. ALERTS & ACTIVE KITCHEN STRIPS                         --}}
        {{-- ======================================================== --}}
        @if(($isAllInOne ?? false) && count($activeAlerts) > 0)
            <div style="background: #fff1f2; border: 1px solid #fecdd3; border-radius: 16px; padding: 14px 16px; margin-bottom: 16px;">
                <h2 style="color: #be123c; font-weight: 800; font-size: 11px; text-transform: uppercase; margin: 0 0 10px 0; display: flex; align-items: center; gap: 6px;">
                    <x-heroicon-s-bell-alert style="width: 16px; height: 16px;" class="animate-bounce" /> Customer Assistance Required ({{ count($activeAlerts) }})
                </h2>
                <div style="display: flex; gap: 12px; overflow-x: auto; padding-bottom: 6px;" class="pos-scroll">
                    @foreach($activeAlerts as $alert)
                        <div style="min-width: 280px; flex-shrink: 0; background: #ffffff; border: 2px solid #fda4af; border-radius: 12px; padding: 12px; display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <span style="font-size: 10px; font-weight: 800; background: #e11d48; color: #ffffff; padding: 2px 6px; border-radius: 4px;">WAITER CALL</span>
                                <p style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 4px 0 2px 0;">{{ $alert['table_number'] }}</p>
                                <p style="font-size: 12px; color: #64748b; margin: 0;">👤 {{ $alert['customer_name'] }} • ⏰ {{ $alert['time'] }}</p>
                            </div>
                            <button wire:click="resolveAlert('{{ $alert['id'] }}')" style="width: 38px; height: 38px; border-radius: 50%; background: #10b981; color: white; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                <x-heroicon-s-check style="width: 20px; height: 20px;" />
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ACTIVE DINE-IN ORDERS STRIP --}}
        @if($incomingTableOrders->count() > 0)
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 14px 16px; margin-bottom: 16px;">
                <h2 style="color: #0f172a; font-weight: 800; font-size: 11px; text-transform: uppercase; margin: 0 0 10px 0; display: flex; align-items: center; gap: 6px;">
                    <x-heroicon-s-clipboard-document-list style="width: 16px; height: 16px; color: #ea580c;" /> Active Table Orders ({{ $incomingTableOrders->count() }})
                </h2>
                <div style="display: flex; gap: 12px; overflow-x: auto; padding-bottom: 6px;" class="pos-scroll">
                    @foreach($incomingTableOrders as $order)
                        @php
                            $oStatus = $order->status;
                            $cBorder = '#fb923c';
                            $cBg = '#ffedd5';
                            $cColor = '#9a3412';
                            $cLabel = '🚨 NEW ORDER';
                            if ($oStatus === 'ready') {
                                $cBorder = '#60a5fa'; $cBg = '#dbeafe'; $cColor = '#1e40af'; $cLabel = '🛎️ READY';
                            } elseif ($oStatus === 'preparing') {
                                $cBorder = '#34d399'; $cBg = '#d1fae5'; $cColor = '#065f46'; $cLabel = 'PREPARING';
                            } elseif (in_array($oStatus, ['accepted', 'partial_accepted'])) {
                                $cBorder = '#fbbf24'; $cBg = '#fef3c7'; $cColor = '#92400e'; $cLabel = '📋 ACCEPTED';
                            }
                            $rawOrderNum = $order->restaurantTable->table_number ?? '?';
                            $cleanOrderNum = str_replace(['Table-', 'Table - ', 'Table ', 'T-', 't-'], '', $rawOrderNum);
                            $finalOrderNum = is_numeric(trim($cleanOrderNum)) ? sprintf('%02d', trim($cleanOrderNum)) : trim($cleanOrderNum);
                        @endphp
                        <div style="min-width: 270px; flex-shrink: 0; background: #ffffff; border: 2px solid {{ $cBorder }}; border-radius: 12px; padding: 12px; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 8px; border-bottom: 1px dashed #e2e8f0; margin-bottom: 8px;">
                                    <div>
                                        <span style="font-size: 10px; font-weight: 800; background: {{ $cBg }}; color: {{ $cColor }}; padding: 2px 6px; border-radius: 4px;">{{ $cLabel }}</span>
                                        <p style="font-size: 14px; font-weight: 800; color: #0f172a; margin: 4px 0 0 0;">Table-{{ $finalOrderNum }}</p>
                                        <p style="font-size: 11px; color: #64748b; margin: 2px 0 0 0;">{{ $order->customer_name }} • #{{ $order->id }}</p>
                                    </div>
                                    <span style="font-weight: 800; font-family: monospace; font-size: 14px; color: #0f172a;">₹{{ number_format($order->total_amount, 0) }}</span>
                                </div>
                                <div style="margin-bottom: 8px; max-height: 80px; overflow-y: auto;" class="stitch-scroll">
                                    @foreach($order->items as $item)
                                        <p style="font-size: 12px; color: #334155; margin: 0 0 2px 0; {{ $oStatus === 'ready' ? 'text-decoration: line-through; color: #94a3b8;' : '' }}">
                                            <strong style="color: #ea580c; font-family: monospace;">{{ $item->quantity }}x</strong> {{ $item->menuItem->name ?? $item->item_name }}
                                        </p>
                                    @endforeach
                                </div>
                            </div>
                            <div style="padding-top: 8px; border-top: 1px solid #f1f5f9;">
                                @if($oStatus === 'placed')
                                    <div style="display: flex; gap: 6px;">
                                        <button wire:click="updateStatus({{ $order->id }}, 'accepted')" style="flex: 1; padding: 6px; background: #ea580c; color: white; border: none; border-radius: 6px; font-weight: 700; font-size: 12px; cursor: pointer;">Accept</button>
                                        <button wire:click="updateStatus({{ $order->id }}, 'rejected')" onclick="confirm('Reject this order?') || event.stopImmediatePropagation()" style="padding: 6px 12px; border: 1px solid #fecdd3; color: #e11d48; background: transparent; border-radius: 6px; font-weight: 700; font-size: 12px; cursor: pointer;">Reject</button>
                                    </div>
                                @elseif(in_array($oStatus, ['accepted', 'partial_accepted']))
                                    <button wire:click="updateStatus({{ $order->id }}, 'preparing')" style="width: 100%; padding: 6px; background: #f59e0b; color: white; border: none; border-radius: 6px; font-weight: 700; font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px;">
                                        <x-heroicon-s-fire style="width: 14px; height: 14px;" /> START PREPARING
                                    </button>
                                @elseif($oStatus === 'preparing')
                                    <button wire:click="updateStatus({{ $order->id }}, 'ready')" style="width: 100%; padding: 6px; background: #10b981; color: white; border: none; border-radius: 6px; font-weight: 700; font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px;">
                                        <x-heroicon-s-check-circle style="width: 14px; height: 14px;" /> MARK READY
                                    </button>
                                @elseif($oStatus === 'ready')
                                    <button wire:click="updateStatus({{ $order->id }}, 'served')" style="width: 100%; padding: 6px; background: #2563eb; color: white; border: none; border-radius: 6px; font-weight: 700; font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px;">
                                        <x-heroicon-s-arrow-right-circle style="width: 14px; height: 14px;" /> SERVE CUSTOMER
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ======================================================== --}}
        {{-- 5. MAIN FLOOR GRID (Screen 1)                            --}}
        {{-- ======================================================== --}}
        @if($currentTab === 'tables')
            <div class="stitch-floor-grid">
                
                {{-- 1. Parcel Counters --}}
                @if($tableFilter === 'all')
                    @foreach($parcelCounters as $counter)
                        <div wire:click="openParcelCounter({{ $counter->id }})" class="stitch-card parcel">
                            <div>
                                <div class="stitch-card-header">
                                    <div>
                                        <h3 class="stitch-card-title" style="color: #ea580c; display: flex; align-items: center; gap: 6px;">🛍️ {{ $counter->name }}</h3>
                                        <div class="stitch-card-meta">
                                            <span>CUSTOMERS: {{ $counter->active_sessions_count }}</span>
                                        </div>
                                    </div>
                                    <span class="stitch-pill" style="background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa;">PARCEL QUEUE</span>
                                </div>
                                <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid #f1f5f9;">
                                    <div style="display: flex; justify-content: space-between; font-size: 12px; color: #475569;">
                                        <span>Active Queue</span>
                                        <span style="font-weight: 700; color: #0f172a;">{{ $counter->live_orders_count ?? 0 }} Items</span>
                                    </div>
                                </div>
                            </div>
                            <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
                                <div>
                                    <span style="font-size: 10px; font-weight: 700; text-transform: uppercase; color: #94a3b8; display: block;">Queue Total</span>
                                    <span style="font-size: 16px; font-weight: 800; font-family: monospace; color: #ea580c;">₹{{ number_format($counter->live_subtotal ?? 0, 2) }}</span>
                                </div>
                                <button wire:click.stop="openParcelCounter({{ $counter->id }})" style="background: #f97316; color: white; border: none; border-radius: 8px; padding: 6px 14px; font-size: 12px; font-weight: 700; cursor: pointer;">
                                    Manage
                                </button>
                            </div>
                        </div>
                    @endforeach
                @endif

                {{-- 2. Restaurant Tables --}}
                @foreach($tables as $table)
                    @php
                        $isOccupied = $table->active_sessions_count > 0;
                        $isCleaning = ($table->status ?? '') === 'cleaning';
                        $isBillPending = $isOccupied && ($table->pending_payment !== null);
                        $isReserved = !$isOccupied && (($table->status ?? '') === 'reserved' || ($table->is_reserved ?? false));
                        
                        $rawNum = $table->table_number;
                        $cleanNum = str_replace(['Table-', 'Table - ', 'Table ', 'T-', 't-'], '', $rawNum);
                        $finalNum = is_numeric(trim($cleanNum)) ? sprintf('%02d', trim($cleanNum)) : trim($cleanNum);
                        $seatedDuration = $table->seated_at ? $table->seated_at->diffForHumans(null, true) : null;
                    @endphp

                    @if($isCleaning)
                        {{-- CARD: CLEANING --}}
                        <div wire:click="openTable({{ $table->id }})" class="stitch-card cleaning">
                            <div>
                                <div class="stitch-card-header">
                                    <div>
                                        <h3 class="stitch-card-title">Table-{{ $finalNum }}</h3>
                                        <div class="stitch-card-meta">
                                            <span>CAPACITY: {{ $table->seating_capacity ?? 4 }}</span>
                                            <span>•</span>
                                            <span style="color: #2563eb;">Housekeeping</span>
                                        </div>
                                    </div>
                                    <span class="stitch-pill" style="background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe;">CLEANING</span>
                                </div>
                                <div style="margin: 24px 0; text-align: center;">
                                    <div style="width: 36px; height: 36px; border-radius: 50%; background: #dbeafe; color: #2563eb; display: flex; align-items: center; justify-content: center; margin: 0 auto 6px auto;">
                                        <x-heroicon-s-sparkles style="width: 20px; height: 20px;" />
                                    </div>
                                    <p style="font-size: 13px; font-weight: 700; color: #1e293b; margin: 0;">Cleaning in Progress</p>
                                    <p style="font-size: 11px; color: #94a3b8; margin: 2px 0 0 0;">Ready for next guests soon</p>
                                </div>
                            </div>
                            <div style="padding-top: 12px; border-top: 1px solid #f1f5f9;">
                                <button wire:click.stop="changeTableStatus({{ $table->id }}, 'available')" style="width: 100%; padding: 8px; border-radius: 10px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; font-size: 12px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;">
                                    <x-heroicon-s-check style="width: 16px; height: 16px;" />
                                    <span>Mark as Cleaned</span>
                                </button>
                            </div>
                        </div>

                    @elseif($isBillPending)
                        {{-- CARD: BILL PENDING --}}
                        <div wire:click="openTable({{ $table->id }})" class="stitch-card bill-pending">
                            <div>
                                <div class="stitch-card-header">
                                    <div>
                                        <h3 class="stitch-card-title">Table-{{ $finalNum }}</h3>
                                        <div class="stitch-card-meta">
                                            <span>CAPACITY: {{ $table->seating_capacity ?? 4 }}</span>
                                            @if($seatedDuration)
                                                <span>•</span>
                                                <span style="color: #64748b;">Seated {{ $seatedDuration }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="stitch-pill" style="background: #fef3c7; color: #92400e; border: 1px solid #fde68a;">BILL PENDING</span>
                                </div>
                                <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid #f1f5f9;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; font-size: 12px; margin-bottom: 6px;">
                                        <div style="display: flex; align-items: center; gap: 4px; color: #475569; font-weight: 600;">
                                            <x-heroicon-s-user-group style="width: 14px; height: 14px; color: #94a3b8;" />
                                            <span>Split Bills: {{ $table->active_sessions_count }}</span>
                                        </div>
                                        <span style="background: #f1f5f9; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; color: #334155; max-width: 110px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            {{ $table->primary_guest_name ?? 'Guest' }}
                                        </span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; font-size: 11px; color: #64748b;">
                                        <span>{{ $table->live_orders_count ?? 0 }} Items Ordered</span>
                                        <span style="color: #d97706; font-weight: 700;">Waiting Payment</span>
                                    </div>
                                </div>
                            </div>
                            <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
                                <div>
                                    <span style="font-size: 10px; font-weight: 700; text-transform: uppercase; color: #94a3b8; display: block;">Total Due</span>
                                    <span style="font-size: 16px; font-weight: 800; font-family: monospace; color: #d97706;">₹{{ number_format($table->live_due ?? 0, 2) }}</span>
                                </div>
                                <button wire:click.stop="openTable({{ $table->id }})" style="background: #f59e0b; color: white; border: none; border-radius: 8px; padding: 6px 14px; font-size: 12px; font-weight: 700; cursor: pointer; box-shadow: 0 1px 2px rgba(245, 158, 11, 0.3);">
                                    Settle Bill
                                </button>
                            </div>
                        </div>

                    @elseif($isOccupied)
                        {{-- CARD: OCCUPIED (IN DINING) --}}
                        <div wire:click="openTable({{ $table->id }})" class="stitch-card occupied">
                            <div>
                                <div class="stitch-card-header">
                                    <div>
                                        <h3 class="stitch-card-title">Table-{{ $finalNum }}</h3>
                                        <div class="stitch-card-meta">
                                            <span>CAPACITY: {{ $table->seating_capacity ?? 4 }}</span>
                                            @if($seatedDuration)
                                                <span>•</span>
                                                <span style="color: #64748b;">Seated {{ $seatedDuration }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="stitch-pill" style="background: #ffe4e6; color: #be123c; border: 1px solid #fecdd3;">OCCUPIED</span>
                                </div>
                                <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid #f1f5f9;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; font-size: 12px; margin-bottom: 6px;">
                                        <div style="display: flex; align-items: center; gap: 4px; color: #475569; font-weight: 600;">
                                            <x-heroicon-s-user-group style="width: 14px; height: 14px; color: #94a3b8;" />
                                            <span>Split Bills: {{ $table->active_sessions_count }}</span>
                                        </div>
                                        <span style="background: #f1f5f9; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; color: #334155; max-width: 110px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            {{ $table->primary_guest_name ?? 'Guest' }}
                                        </span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; font-size: 11px; color: #64748b;">
                                        <span>{{ $table->live_orders_count ?? 0 }} Items Active</span>
                                        <span style="color: #059669; font-weight: 700;">Serving</span>
                                    </div>
                                </div>
                            </div>
                            <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
                                <div>
                                    <span style="font-size: 10px; font-weight: 700; text-transform: uppercase; color: #94a3b8; display: block;">Total Due</span>
                                    <span style="font-size: 16px; font-weight: 800; font-family: monospace; color: #ea580c;">₹{{ number_format($table->live_due ?? 0, 2) }}</span>
                                </div>
                                <div style="display: flex; gap: 6px;">
                                    <button wire:click.stop="openTable({{ $table->id }})" style="background: #f1f5f9; color: #334155; border: 1px solid #e2e8f0; border-radius: 8px; padding: 6px 10px; font-size: 12px; font-weight: 700; cursor: pointer;">
                                        Manage
                                    </button>
                                    <button wire:click.stop="openPlaceOrder({{ $table->id }})" style="background: #f97316; color: white; border: none; border-radius: 8px; padding: 6px 12px; font-size: 12px; font-weight: 700; cursor: pointer;">
                                        + Order
                                    </button>
                                </div>
                            </div>
                        </div>

                    @elseif($isReserved)
                        {{-- CARD: RESERVED --}}
                        <div wire:click="openTable({{ $table->id }})" class="stitch-card" style="border: 2px solid #818cf8;">
                            <div>
                                <div class="stitch-card-header">
                                    <div>
                                        <h3 class="stitch-card-title">Table-{{ $finalNum }}</h3>
                                        <div class="stitch-card-meta">
                                            <span>CAPACITY: {{ $table->seating_capacity ?? 4 }}</span>
                                        </div>
                                    </div>
                                    <span class="stitch-pill" style="background: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe;">RESERVED</span>
                                </div>
                                <div style="margin: 24px 0; text-align: center;">
                                    <div style="width: 36px; height: 36px; border-radius: 50%; background: #e0e7ff; color: #4f46e5; display: flex; align-items: center; justify-content: center; margin: 0 auto 6px auto;">
                                        <x-heroicon-s-calendar style="width: 20px; height: 20px;" />
                                    </div>
                                    <p style="font-size: 13px; font-weight: 700; color: #1e293b; margin: 0;">Reserved Table</p>
                                </div>
                            </div>
                            <div style="padding-top: 12px; border-top: 1px solid #f1f5f9;">
                                <button wire:click.stop="mountAction('assignGuestAction', { table_id: {{ $table->id }} })" style="width: 100%; padding: 8px; border-radius: 10px; background: #eef2ff; border: 1px solid #c7d2fe; color: #4338ca; font-size: 12px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;">
                                    <x-heroicon-s-user-plus style="width: 16px; height: 16px;" />
                                    <span>Seat Reserved Guest</span>
                                </button>
                            </div>
                        </div>

                    @else
                        {{-- CARD: AVAILABLE (GREEN DASHED BORDER) --}}
                        <div wire:click="openTable({{ $table->id }})" class="stitch-card available">
                            <div>
                                <div class="stitch-card-header">
                                    <div>
                                        <h3 class="stitch-card-title">Table-{{ $finalNum }}</h3>
                                        <div class="stitch-card-meta">
                                            <span>CAPACITY: {{ $table->seating_capacity ?? 4 }}</span>
                                            <span>•</span>
                                            <span style="color: #059669;">Cleaned</span>
                                        </div>
                                    </div>
                                    <span class="stitch-pill" style="background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0;">AVAILABLE</span>
                                </div>
                                <div style="margin: 24px 0; text-align: center;">
                                    <div style="width: 36px; height: 36px; border-radius: 50%; background: #d1fae5; color: #059669; display: flex; align-items: center; justify-content: center; margin: 0 auto 6px auto;">
                                        <x-heroicon-s-check style="width: 20px; height: 20px;" />
                                    </div>
                                    <p style="font-size: 13px; font-weight: 700; color: #1e293b; margin: 0;">Ready for Seating</p>
                                    <p style="font-size: 11px; color: #94a3b8; margin: 2px 0 0 0;">Table QR code is online</p>
                                </div>
                            </div>
                            <div style="padding-top: 12px; border-top: 1px solid #f1f5f9;">
                                <button wire:click.stop="mountAction('assignGuestAction', { table_id: {{ $table->id }} })" style="width: 100%; padding: 8px; border-radius: 10px; background: transparent; border: 1px solid #10b981; color: #047857; font-size: 12px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;">
                                    <x-heroicon-s-plus style="width: 16px; height: 16px;" />
                                    <span>+ Assign Guest</span>
                                </button>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @elseif($currentTab === 'rooms')
            {{-- ROOMS GRID --}}
            <div class="stitch-floor-grid">
                @foreach($rooms as $room)
                    <div wire:click="openRoom({{ $room->id }})" class="stitch-card" style="border: 2px solid {{ $room->status === 'occupied' ? '#fb923c' : ($room->status === 'cleaning' ? '#93c5fd' : '#34d399') }};">
                        <div>
                            <div class="stitch-card-header">
                                <h3 class="stitch-card-title">Room {{ $room->room_number }}</h3>
                                @if($room->status === 'occupied')
                                    <span class="stitch-pill" style="background: #ffe4e6; color: #be123c;">OCCUPIED</span>
                                @elseif($room->status === 'cleaning')
                                    <span class="stitch-pill" style="background: #dbeafe; color: #1e40af;">CLEANING</span>
                                @else
                                    <span class="stitch-pill" style="background: #d1fae5; color: #065f46;">AVAILABLE</span>
                                @endif
                            </div>
                            <div style="margin: 20px 0; text-align: center;">
                                @if($room->status === 'occupied')
                                    <p style="font-size: 13px; font-weight: 700; color: #1e293b; margin: 0;">👤 {{ $room->guest_name ?? 'Guest' }}</p>
                                    <p style="font-size: 15px; font-weight: 800; font-family: monospace; color: #ea580c; margin: 4px 0 0 0;">₹ Due: {{ number_format($room->live_due ?? 0, 2) }}</p>
                                @elseif($room->status === 'cleaning')
                                    <p style="font-size: 12px; font-weight: 700; color: #2563eb; margin: 0;">🧹 Housekeeping in progress</p>
                                @else
                                    <p style="font-size: 12px; font-weight: 700; color: #059669; margin: 0;">✔️ Ready for guest</p>
                                @endif
                            </div>
                        </div>
                        <div style="padding-top: 12px; border-top: 1px solid #f1f5f9;">
                            <button wire:click.stop="openRoom({{ $room->id }})" style="width: 100%; padding: 8px; border-radius: 10px; background: #f1f5f9; border: 1px solid #e2e8f0; color: #334155; font-size: 12px; font-weight: 700; cursor: pointer;">
                                Manage Room
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ======================================================== --}}
        {{-- 6. UNIFIED MODAL ENGINE: 3-COLUMN LAYOUT (Screen 2)      --}}
        {{-- ======================================================== --}}
        @if($selectedTableId || $selectedParcelCounterId || $selectedRoomId)
            <div class="stitch-modal-overlay" wire:click.self="closeReceiptModal">
                <section class="stitch-modal-box">
                    
                    {{-- MODAL HEADER --}}
                    <header class="stitch-modal-header">
                        <div style="display: flex; align-items: center; gap: 14px;">
                            <div style="width: 44px; height: 44px; background: #eef2ff; color: #4f46e5; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                @if($selectedParcelCounterId)
                                    <x-heroicon-s-shopping-bag style="width: 24px; height: 24px; color: #ea580c;" />
                                @elseif($selectedRoomId)
                                    <x-heroicon-s-home style="width: 24px; height: 24px;" />
                                @else
                                    <x-heroicon-s-cake style="width: 24px; height: 24px;" />
                                @endif
                            </div>
                            <div>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #94a3b8; letter-spacing: 0.5px;">Managing Session</span>
                                    @php
                                        $hasActiveGuests = $activeDinersList->count() > 0;
                                        $primaryDinerTime = $activeDinersList->first()?->created_at?->diffForHumans(null, true);
                                    @endphp
                                    <span style="display: inline-flex; align-items: center; gap: 6px; padding: 2px 10px; border-radius: 9999px; font-size: 12px; font-weight: 600; {{ $hasActiveGuests ? 'background: #d1fae5; color: #065f46;' : 'background: #f1f5f9; color: #475569;' }}">
                                        <span class="stitch-dot animate-pulse-dot" style="background: {{ $hasActiveGuests ? '#10b981' : '#94a3b8' }};"></span>
                                        {{ $hasActiveGuests ? 'Occupied (' . ($primaryDinerTime ?? 'Active') . ')' : 'Available' }}
                                    </span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px; margin-top: 2px;">
                                    <h1 style="font-size: 22px; font-weight: 800; color: #0f172a; margin: 0; line-height: 1.2;">
                                        @if($selectedParcelCounterId)
                                            🛍️ {{ $selectedEntityData->name ?? 'Parcel Queue' }}
                                        @elseif($selectedRoomId)
                                            🚪 Room {{ $selectedEntityData->room_number ?? '?' }}
                                        @else
                                            @php
                                                $rawModalNum = $selectedEntityData->table_number ?? '?';
                                                $cleanModalNum = str_replace(['Table-', 'Table - ', 'Table ', 'T-', 't-'], '', $rawModalNum);
                                                $finalModalNum = is_numeric(trim($cleanModalNum)) ? sprintf('%02d', trim($cleanModalNum)) : trim($cleanModalNum);
                                            @endphp
                                            🍽️ Table-{{ $finalModalNum }}
                                        @endif
                                    </h1>
                                    @if($selectedTableId)
                                        <span style="color: #94a3b8; font-size: 13px; font-weight: 500;">• {{ $selectedEntityData->seating_capacity ?? 4 }} Seater • Floor Area</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Close & Refresh --}}
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <button wire:click="$refresh" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 7px; color: #64748b; cursor: pointer;" title="Refresh Orders">
                                <x-heroicon-s-arrow-path style="width: 18px; height: 18px;" />
                            </button>
                            <button wire:click="closeReceiptModal" style="width: 36px; height: 36px; border-radius: 50%; background: #f1f5f9; border: none; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                <x-heroicon-s-x-mark style="width: 20px; height: 20px;" />
                            </button>
                        </div>
                    </header>

                    {{-- THREE COLUMN GRID BODY --}}
                    <div class="stitch-modal-body">
                        
                        {{-- ======================================================== --}}
                        {{-- COLUMN 1: GUESTS & SPLITS (Width: 320px)                 --}}
                        {{-- ======================================================== --}}
                        <aside class="stitch-col-guests stitch-scroll">
                            <div>
                                @if($selectedRoomId)
                                    <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 14px;">Room Management</div>
                                    @if($selectedEntityData->status === 'occupied')
                                        <div style="background: #eff6ff; padding: 16px; border-radius: 14px; border: 1px solid #bfdbfe; text-align: center; margin-bottom: 14px;">
                                            <p style="font-weight: 800; color: #1e3a8a; margin: 0 0 10px 0;">Guest: {{ $selectedEntityData->guest_name }}</p>
                                            @if($selectedEntityData->qr_path)
                                                <img src="{{ asset('storage/' . $selectedEntityData->qr_path) }}" style="width: 120px; height: 120px; margin: 0 auto; border-radius: 8px; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.1);" />
                                                <button wire:click="mountAction('printStayQrAction')" style="margin-top: 10px; font-size: 12px; font-weight: 700; color: #2563eb; background: none; border: none; cursor: pointer; text-decoration: underline;">
                                                    Customize & Print QR
                                                </button>
                                            @endif
                                        </div>
                                        <button wire:click="mountAction('checkoutAction', { room_id: {{ $selectedRoomId }} })" style="width: 100%; padding: 12px; border-radius: 12px; background: #e11d48; color: white; font-weight: 700; font-size: 13px; border: none; cursor: pointer;">
                                            Checkout Guest
                                        </button>
                                    @elseif($selectedEntityData->status === 'cleaning')
                                        <button wire:click="mountAction('markCleanAction', { room_id: {{ $selectedRoomId }} })" style="width: 100%; padding: 12px; border-radius: 12px; background: #2563eb; color: white; font-weight: 700; font-size: 13px; border: none; cursor: pointer;">
                                            Ready for Next Guest
                                        </button>
                                    @else
                                        <button wire:click="mountAction('checkInAction', { room_id: {{ $selectedRoomId }} })" style="width: 100%; padding: 12px; border-radius: 12px; background: #10b981; color: white; font-weight: 700; font-size: 13px; border: none; cursor: pointer;">
                                            Check In Guest
                                        </button>
                                    @endif
                                @else
                                    {{-- GUESTS & SPLITS HEADER --}}
                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Guests & Splits</span>
                                            <span style="background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700;">{{ $activeDinersList->count() }} Guests</span>
                                        </div>
                                        @if($selectedTableId)
                                            <button wire:click="mountAction('assignGuestAction', { table_id: {{ $selectedTableId }} })" style="background: #1e3a8a; color: white; border: none; border-radius: 8px; padding: 5px 10px; font-size: 11px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                                <x-heroicon-s-user-plus style="width: 12px; height: 12px;" />
                                                <span>+ Assign Guest</span>
                                            </button>
                                        @elseif($selectedParcelCounterId)
                                            <button wire:click="mountAction('assignGuestAction', { counter_id: {{ $selectedParcelCounterId }} })" style="background: #ea580c; color: white; border: none; border-radius: 8px; padding: 5px 10px; font-size: 11px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                                <x-heroicon-s-plus style="width: 12px; height: 12px;" />
                                                <span>+ Add Customer</span>
                                            </button>
                                        @endif
                                    </div>

                                    {{-- Guest Cards List --}}
                                    @if($activeDinersList->count() === 0)
                                        <div style="text-align: center; padding: 24px 14px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 14px; margin-top: 10px;">
                                            <x-heroicon-o-user-plus style="width: 36px; height: 36px; color: #94a3b8; margin: 0 auto 8px auto;" />
                                            <p style="font-size: 13px; font-weight: 700; color: #1e293b; margin: 0 0 4px 0;">No active customers seated</p>
                                            <p style="font-size: 11px; color: #64748b; margin: 0 0 14px 0;">Assign a guest with their name to start placing orders on their behalf.</p>
                                            @if($selectedTableId)
                                                <button wire:click="mountAction('assignGuestAction', { table_id: {{ $selectedTableId }} })" style="width: 100%; padding: 10px; background: #f97316; color: white; border: none; border-radius: 10px; font-weight: 700; font-size: 12px; cursor: pointer;">
                                                    👤 Assign Guest
                                                </button>
                                            @elseif($selectedParcelCounterId)
                                                <button wire:click="mountAction('assignGuestAction', { counter_id: {{ $selectedParcelCounterId }} })" style="width: 100%; padding: 10px; background: #f97316; color: white; border: none; border-radius: 10px; font-weight: 700; font-size: 12px; cursor: pointer;">
                                                    🛍️ Add Customer
                                                </button>
                                            @endif
                                        </div>
                                    @else
                                        @if($selectedTableId && !empty($hostParties))
                                            {{-- DINE-IN PARTIES & GUESTS HIERARCHY --}}
                                            @foreach($hostParties as $party)
                                                @php
                                                    $host = $party['host'];
                                                    $isHostSelected = ($selectedSessionId === $host->id);
                                                @endphp
                                                <div style="background: #ffffff; border: 1px solid {{ $party['is_any_member_selected'] ? '#bfdbfe' : '#e2e8f0' }}; border-radius: 16px; padding: 12px; margin-bottom: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                                                    {{-- PARTY HEADER BADGE --}}
                                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; padding-bottom: 6px; border-bottom: 1px solid #f1f5f9;">
                                                        <div style="display: flex; align-items: center; gap: 6px;">
                                                            <span style="font-size: 10px; font-weight: 800; background: #1e3a8a; color: white; padding: 2px 7px; border-radius: 6px; letter-spacing: 0.5px;">
                                                                PARTY #{{ $party['party_number'] }}
                                                            </span>
                                                            <span style="font-size: 11px; font-weight: 700; color: #64748b;">
                                                                {{ $party['total_diners_count'] }} {{ \Illuminate\Support\Str::plural('Diner', $party['total_diners_count']) }}
                                                            </span>
                                                        </div>
                                                        <div style="text-align: right;">
                                                            <span style="font-size: 10px; font-weight: 600; color: #94a3b8;">Party Total: </span>
                                                            <span style="font-size: 12px; font-weight: 900; font-family: monospace; color: #0f172a;">₹{{ number_format($party['party_subtotal'], 2) }}</span>
                                                        </div>
                                                    </div>

                                                    {{-- PRIMARY HOST CARD --}}
                                                    <div wire:click="selectCustomerSession({{ $host->id }})" 
                                                         class="stitch-guest-card {{ $isHostSelected ? 'active' : '' }}" 
                                                         style="margin-bottom: {{ $party['guests']->isNotEmpty() ? '8px' : '0' }}; border-color: {{ $isHostSelected ? '#2563eb' : '#fed7aa' }}; background: {{ $isHostSelected ? '#eff6ff' : '#fffaf5' }};">
                                                        <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                                <div class="stitch-guest-avatar {{ $isHostSelected ? 'active' : '' }}" style="background: {{ $isHostSelected ? '#2563eb' : '#ffedd5' }}; color: {{ $isHostSelected ? 'white' : '#ea580c' }};">
                                                                    <x-heroicon-s-user style="width: 16px; height: 16px;" />
                                                                </div>
                                                                <div>
                                                                    <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                                                        <span style="font-size: 14px; font-weight: 800; color: #0f172a;">{{ $host->customer_name ?? 'Host' }}</span>
                                                                        <span style="font-size: 9px; font-weight: 800; background: #ea580c; color: white; padding: 1px 6px; border-radius: 4px;">
                                                                            👑 HOST
                                                                        </span>
                                                                    </div>
                                                                    <span style="font-size: 11px; color: #64748b;">Seated {{ $host->created_at->diffForHumans(null, true) }} ago</span>
                                                                </div>
                                                            </div>
                                                            <div style="display: flex; align-items: center; gap: 4px;">
                                                                <button wire:click.stop="mountAction('editCustomerNameAction', { session_id: {{ $host->id }} })" class="stitch-icon-btn" title="Rename Host">
                                                                    <x-heroicon-m-pencil-square style="width: 14px; height: 14px;" />
                                                                </button>
                                                                <button wire:click.stop="cleanDineInSession({{ $host->id }}, true)" 
                                                                        onclick="confirm('Vacate Party #{{ $party['party_number'] }} (Host {{ $host->customer_name }} and any joined guests)?') || event.stopImmediatePropagation()" 
                                                                        class="stitch-icon-btn" 
                                                                        style="color: #e11d48;" 
                                                                        title="Vacate Party #{{ $party['party_number'] }}">
                                                                    <x-heroicon-m-arrow-right-on-rectangle style="width: 14px; height: 14px;" />
                                                                </button>
                                                            </div>
                                                        </div>
                                                        <div style="margin-top: 10px; padding-top: 8px; border-top: 1px solid rgba(226, 232, 240, 0.8); display: flex; align-items: center; justify-content: space-between; font-size: 11px;">
                                                            <span style="color: #64748b; font-weight: 600;">Host Orders: {{ $party['host_orders_count'] }} • {{ $party['host_items_count'] }} items</span>
                                                            <span style="font-weight: 800; font-family: monospace; font-size: 13px; color: #0f172a;">₹{{ number_format($party['host_subtotal'], 2) }}</span>
                                                        </div>
                                                    </div>

                                                    {{-- JOINED GUESTS (NESTED UNDER HOST) --}}
                                                    @if($party['guests']->isNotEmpty())
                                                        <div style="margin-left: 8px; padding-left: 10px; border-left: 2px dashed #cbd5e1; margin-top: 8px; display: flex; flex-direction: column; gap: 8px;">
                                                            <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">
                                                                ↳ Joined Guests ({{ $party['guests']->count() }})
                                                            </div>
                                                            @foreach($party['guests'] as $guest)
                                                                @php
                                                                    $isGuestSelected = ($selectedSessionId === $guest->id);
                                                                @endphp
                                                                <div wire:click="selectCustomerSession({{ $guest->id }})" 
                                                                     class="stitch-guest-card {{ $isGuestSelected ? 'active' : '' }}" 
                                                                     style="margin-bottom: 0; padding: 10px 12px; background: {{ $isGuestSelected ? '#eff6ff' : '#f8fafc' }};">
                                                                    <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                                                                        <div style="display: flex; align-items: center; gap: 8px;">
                                                                            <div class="stitch-guest-avatar {{ $isGuestSelected ? 'active' : '' }}" style="width: 26px; height: 26px;">
                                                                                <x-heroicon-s-user style="width: 14px; height: 14px;" />
                                                                            </div>
                                                                            <div>
                                                                                <div style="display: flex; align-items: center; gap: 5px; flex-wrap: wrap;">
                                                                                    <span style="font-size: 13px; font-weight: 700; color: #1e293b;">{{ $guest->customer_name ?? 'Guest' }}</span>
                                                                                    <span style="font-size: 9px; font-weight: 700; background: #e0e7ff; color: #3730a3; padding: 1px 5px; border-radius: 4px;">
                                                                                        👥 Joined {{ $host->customer_name }}
                                                                                    </span>
                                                                                </div>
                                                                                <div style="font-size: 10px; color: #94a3b8;">Joined {{ $guest->created_at->diffForHumans(null, true) }} ago</div>
                                                                            </div>
                                                                        </div>
                                                                        <div style="display: flex; align-items: center; gap: 3px;">
                                                                            <button wire:click.stop="mountAction('editCustomerNameAction', { session_id: {{ $guest->id }} })" class="stitch-icon-btn" title="Edit Guest Name">
                                                                                <x-heroicon-m-pencil-square style="width: 13px; height: 13px;" />
                                                                            </button>
                                                                            <button wire:click.stop="cleanDineInSession({{ $guest->id }}, false)" 
                                                                                    onclick="confirm('Vacate guest {{ $guest->customer_name }} from this table?') || event.stopImmediatePropagation()" 
                                                                                    class="stitch-icon-btn" 
                                                                                    style="color: #e11d48;" 
                                                                                    title="Vacate this Guest">
                                                                                <x-heroicon-m-x-mark style="width: 13px; height: 13px;" />
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                    <div style="margin-top: 8px; padding-top: 6px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; font-size: 11px;">
                                                                        <span style="color: #64748b;">{{ $guest->orders_count }} orders • {{ $guest->items_count }} items</span>
                                                                        <span style="font-weight: 700; font-family: monospace; color: #334155;">₹{{ number_format($guest->subtotal, 2) }}</span>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        @else
                                            {{-- PARCEL QUEUE CARDS --}}
                                            @foreach($activeDinersList as $diner)
                                                @php
                                                    $isActiveDiner = ($selectedSessionId === $diner->id);
                                                    $dinerOrders = \App\Models\Order::where('parcel_qr_session_id', $diner->id)->whereIn('status', ['placed', 'accepted', 'partial_accepted', 'preparing', 'ready', 'served'])->with('items')->get();
                                                    $dinerSubtotal = $dinerOrders->sum(fn($o) => $o->confirmed_total ?? $o->total_amount);
                                                    $dinerItemsCount = $dinerOrders->sum(fn($o) => $o->items->sum('quantity'));
                                                @endphp

                                                <div wire:click="selectCustomerSession({{ $diner->id }})" class="stitch-guest-card {{ $isActiveDiner ? 'active' : '' }}">
                                                    <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                                                        <div style="display: flex; align-items: center; gap: 10px;">
                                                            <div class="stitch-guest-avatar {{ $isActiveDiner ? 'active' : '' }}">
                                                                <x-heroicon-s-user style="width: 16px; height: 16px;" />
                                                            </div>
                                                            <div>
                                                                <span style="font-size: 14px; font-weight: 800; color: #0f172a;">{{ $diner->customer_name ?? 'Guest' }}</span>
                                                                <span style="font-size: 11px; color: #64748b; display: block;">Queued {{ $diner->created_at->diffForHumans(null, true) }} ago</span>
                                                            </div>
                                                        </div>
                                                        <button wire:click.stop="mountAction('editCustomerNameAction', { session_id: {{ $diner->id }}, is_parcel: true })" class="stitch-icon-btn" title="Edit Customer">
                                                            <x-heroicon-m-pencil-square style="width: 14px; height: 14px;" />
                                                        </button>
                                                    </div>
                                                    <div style="margin-top: 12px; padding-top: 10px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; font-size: 12px;">
                                                        <span style="color: #64748b;">{{ $dinerOrders->count() }} orders • {{ $dinerItemsCount }} items</span>
                                                        <span style="font-weight: 800; font-family: monospace; font-size: 14px; color: #0f172a;">₹{{ number_format($dinerSubtotal, 2) }}</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif
                                    @endif
                                @endif
                            </div>

                            {{-- Bottom Split Helper Info --}}
                            <div style="padding: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin-top: 14px;">
                                <div style="display: flex; align-items: center; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
                                    <span style="color: #64748b; font-weight: 600;">Split Bill Strategy</span>
                                    <span style="font-weight: 800; color: #1e3a8a;">Itemized Split</span>
                                </div>
                                <p style="font-size: 11px; color: #94a3b8; margin: 0;">Separate receipts per seat</p>
                            </div>
                        </aside>

                        {{-- ======================================================== --}}
                        {{-- COLUMN 2: ORDERS STREAM & LIVE KITCHEN (Center Column)   --}}
                        {{-- ======================================================== --}}
                        <main class="stitch-col-orders stitch-scroll">
                            @if(!$selectedSessionId)
                                <div style="height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; color: #94a3b8; padding: 32px;">
                                    <x-heroicon-o-user-plus style="width: 48px; height: 48px; color: #cbd5e1; margin-bottom: 12px;" />
                                    <h4 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0 0 6px 0;">No Active Guest Selected</h4>
                                    <p style="font-size: 12px; color: #64748b; margin: 0 0 16px 0; max-width: 320px;">Select a guest from the left column or assign a new guest to place orders on their behalf.</p>
                                    @if($selectedTableId)
                                        <div style="display: flex; gap: 8px;">
                                            <button wire:click="mountAction('assignGuestAction', { table_id: {{ $selectedTableId }} })" style="padding: 9px 16px; background: #0f172a; color: white; border: none; border-radius: 10px; font-weight: 700; font-size: 12px; cursor: pointer;">
                                                👤 Assign Guest
                                            </button>
                                            <button wire:click="openPlaceOrder" style="padding: 9px 16px; background: #f97316; color: white; border: none; border-radius: 10px; font-weight: 700; font-size: 12px; cursor: pointer;">
                                                🛒 + Place Order Directly
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            @else
                                @php
                                    $currentCustName = $activeSession?->customer_name ?? $activeSession?->guest_name ?? 'Guest';
                                @endphp

                                <div>
                                    {{-- Active Guest Meta Header --}}
                                    <div style="background: #ffffff; padding: 14px 16px; border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 1px 2px rgba(0,0,0,0.03); display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            @php
                                                $isHostRole = $activeSession?->is_primary || empty($activeSession?->host_session_id);
                                            @endphp
                                            <div style="width: 36px; height: 36px; background: {{ $isHostRole ? '#ffedd5' : '#e0e7ff' }}; color: {{ $isHostRole ? '#9a3412' : '#3730a3' }}; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                                <x-heroicon-s-user style="width: 20px; height: 20px;" />
                                            </div>
                                            <div>
                                                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                                    <h2 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0;">{{ $currentCustName }}'s Ordered Items</h2>
                                                    @if($selectedTableId)
                                                        @if($isHostRole)
                                                            <span style="font-size: 9px; font-weight: 800; background: #ffedd5; color: #9a3412; padding: 2px 6px; border-radius: 4px;">👑 Primary Host (Party Orders)</span>
                                                        @else
                                                            <span style="font-size: 9px; font-weight: 800; background: #e0e7ff; color: #3730a3; padding: 2px 6px; border-radius: 4px;">👥 Joined Guest</span>
                                                        @endif
                                                    @endif
                                                    <button wire:click="mountAction('editCustomerNameAction', { session_id: {{ $selectedSessionId }} })" class="stitch-icon-btn" title="Edit Customer Name">
                                                        <x-heroicon-m-pencil-square style="width: 14px; height: 14px;" />
                                                    </button>
                                                </div>
                                                <p style="font-size: 11px; color: #64748b; font-weight: 500; margin: 2px 0 0 0;">
                                                    @if($selectedTableId && $isHostRole)
                                                        Ordering on behalf of {{ $currentCustName }} • Showing party orders
                                                    @else
                                                        Ordering mode: On behalf of {{ $currentCustName }}
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                        @if(!$pendingPayment)
                                            <button wire:click="openPlaceOrder" style="background: #1e3a8a; color: white; border: none; border-radius: 8px; padding: 7px 16px; font-size: 12px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 1px 2px rgba(30, 58, 138, 0.2);">
                                                <x-heroicon-s-plus style="width: 14px; height: 14px;" />
                                                <span>Place Order</span>
                                            </button>
                                        @endif
                                    </div>

                                    {{-- Status Stage Banner --}}
                                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 0 4px; margin-bottom: 12px;">
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span class="stitch-dot animate-pulse-dot" style="background: #10b981;"></span>
                                            <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #065f46; letter-spacing: 0.5px;">Ready to Serve / In Dining</span>
                                        </div>
                                        <span style="font-size: 11px; color: #94a3b8; font-weight: 500;">KOT Synchronized</span>
                                    </div>

                                    {{-- Order Tickets List --}}
                                    @if($tableOrders->count() === 0)
                                        <div style="text-align: center; padding: 36px 16px; background: #ffffff; border: 1px dashed #cbd5e1; border-radius: 14px;">
                                            <x-heroicon-o-shopping-bag style="width: 36px; height: 36px; color: #cbd5e1; margin: 0 auto 8px auto;" />
                                            <p style="font-size: 14px; font-weight: 700; color: #0f172a; margin: 0 0 4px 0;">No orders placed yet</p>
                                            <p style="font-size: 12px; color: #64748b; margin: 0 0 16px 0;">Click below to place the first order on behalf of {{ $currentCustName }}.</p>
                                            <button wire:click="openPlaceOrder" style="padding: 8px 18px; background: #f97316; color: white; border: none; border-radius: 8px; font-weight: 700; font-size: 12px; cursor: pointer;">
                                                + Place Order Now
                                            </button>
                                        </div>
                                    @else
                                        <div style="display: flex; flex-direction: column; gap: 12px;">
                                            @foreach($tableOrders as $order)
                                                @php
                                                    $isCancelled = in_array($order->status, ['cancelled', 'rejected']);
                                                    $isPaid = $order->payment_status === 'paid';
                                                @endphp
                                                <article style="background: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.03); overflow: hidden; {{ $isCancelled ? 'opacity: 0.5;' : '' }}">
                                                    <header style="background: #f8fafc; padding: 10px 16px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; font-size: 12px;">
                                                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                                            <span style="font-weight: 800; color: #0f172a;">Order #{{ $order->id }}</span>
                                                            <span style="font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 4px; {{ $order->status === 'served' ? 'background: #d1fae5; color: #065f46;' : ($order->status === 'ready' ? 'background: #dbeafe; color: #1e40af;' : 'background: #f1f5f9; color: #475569;') }}">
                                                                {{ ucfirst($order->status) }}
                                                            </span>
                                                            <span style="font-size: 11px; font-weight: 600; color: #475569; background: #f1f5f9; padding: 2px 6px; border-radius: 4px;">
                                                                👤 {{ $order->customer_name }}
                                                            </span>
                                                            @if($isPaid)
                                                                <span style="font-size: 10px; font-weight: 800; background: #d1fae5; color: #065f46; padding: 2px 6px; border-radius: 4px;">PAID</span>
                                                            @endif
                                                            <span style="font-size: 11px; color: #94a3b8;">{{ $order->created_at->diffForHumans(null, true) }} ago</span>
                                                        </div>
                                                        <div>
                                                            @if(!$isCancelled && !$pendingPayment && !$isPaid)
                                                                <button wire:click="mountAction('editOrderAction', { orderId: {{ $order->id }} })" style="background: white; border: 1px solid #cbd5e1; border-radius: 6px; padding: 2px 8px; font-size: 11px; font-weight: 600; color: #475569; cursor: pointer;">
                                                                    Edit
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </header>
                                                    <div style="padding: 14px 16px; display: flex; flex-direction: column; gap: 8px;">
                                                        @foreach($order->items as $item)
                                                            @php
                                                                $displayQty = $item->confirmed_qty ?? $item->quantity;
                                                                $isOos = $displayQty === 0;
                                                            @endphp
                                                            <div style="display: flex; align-items: center; justify-content: space-between; font-size: 13px; padding-bottom: 6px; border-bottom: 1px solid #f8fafc;">
                                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                                    @if($isOos)
                                                                        <span style="color: #e11d48; font-weight: 800; font-size: 11px;">[OOS]</span>
                                                                    @else
                                                                        <span style="font-weight: 800; color: #c2410c; font-family: monospace; font-size: 13px; width: 22px;">{{ $displayQty }}x</span>
                                                                    @endif
                                                                    <div>
                                                                        <span style="font-weight: 600; color: #1e293b; {{ $isOos ? 'text-decoration: line-through; color: #94a3b8;' : '' }}">{{ $item->item_name }}</span>
                                                                        @if(!empty($item->notes))
                                                                            <p style="font-size: 11px; color: #d97706; margin: 2px 0 0 0;">Note: {{ $item->notes }}</p>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                <span style="font-weight: 800; font-family: monospace; color: #0f172a; {{ $isOos ? 'text-decoration: line-through; color: #94a3b8;' : '' }}">₹{{ number_format($item->unit_price * ($isOos ? $item->quantity : $displayQty), 0) }}</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </article>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                {{-- Quick Add Inline Bar --}}
                                @if(!$pendingPayment)
                                    <div style="margin-top: 14px; padding-top: 12px; border-top: 1px solid #e2e8f0;">
                                        <button wire:click="openPlaceOrder" style="width: 100%; padding: 10px 14px; border: 1px dashed #cbd5e1; border-radius: 12px; background: transparent; color: #475569; font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                                            <x-heroicon-s-plus-circle style="width: 16px; height: 16px; color: #ea580c;" />
                                            <span>Quick Add Dish or Beverage for {{ $currentCustName }}</span>
                                        </button>
                                    </div>
                                @endif
                            @endif
                        </main>

                        {{-- ======================================================== --}}
                        {{-- COLUMN 3: BILL SUMMARY & SETTLEMENT (Right Column)       --}}
                        {{-- ======================================================== --}}
                        <aside class="stitch-col-billing stitch-scroll">
                            @if($selectedSessionId)
                                @php
                                    $validOrders = $tableOrders->whereIn('status', ['placed', 'accepted', 'partial_accepted', 'preparing', 'ready', 'served']);
                                    $subtotal = $validOrders->sum(fn($o) => $o->confirmed_total ?? $o->total_amount);
                                    $amountAlreadyPaid = $validOrders->where('payment_status', 'paid')->sum(fn($o) => $o->confirmed_total ?? $o->total_amount);

                                    if ($pendingPayment) {
                                        $displaySubtotal = (float) $pendingPayment->subtotal;
                                        $displayDiscount = (float) $pendingPayment->discount_amount;
                                        $displayTax = (float) $pendingPayment->tax_amount;
                                        $displayExtra = (float) $pendingPayment->extra_charges;
                                        $taxable = max(0, $displaySubtotal - $displayDiscount);
                                        $displayTaxPercent = ($taxable > 0 && $displayTax > 0) ? round(($displayTax / $taxable) * 100, 1) : 0;
                                        $displayTotal = (float) $pendingPayment->amount;
                                    } else {
                                        $displaySubtotal = (float) $subtotal;
                                        $displayDiscount = (float) $discountAmount;
                                        $taxable = max(0, $displaySubtotal - $displayDiscount);
                                        $displayTaxPercent = (float) $taxPercentage;
                                        $displayTax = $taxable * ($displayTaxPercent / 100);
                                        $displayExtra = (float) $extraCharges;
                                        $displayTotal = max(0, ($taxable + $displayTax + $displayExtra) - $amountAlreadyPaid);
                                    }
                                    $totalItemsCount = $validOrders->sum(fn($o) => $o->items->sum('quantity'));
                                @endphp

                                <div>
                                    {{-- Management: Clean & Vacate --}}
                                    @if($selectedTableId)
                                        <div style="margin-bottom: 16px; display: flex; flex-direction: column; gap: 8px;">
                                            <span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #94a3b8; display: block;">Session & Table Turnover</span>
                                            
                                            {{-- 1. Vacate Current Selected Session / Party --}}
                                            @php
                                                $isCurrentPrimary = $activeSession?->is_primary || empty($activeSession?->host_session_id);
                                                $vacateLabel = $isCurrentPrimary 
                                                    ? "Vacate & Complete Party ({$currentCustName})" 
                                                    : "Vacate & Complete Guest ({$currentCustName})";
                                                $vacateConfirm = $isCurrentPrimary
                                                    ? "Are you sure you want to end the session for Party '{$currentCustName}' and their joined guests? If other parties are still dining at this table, they will remain active."
                                                    : "Are you sure you want to end the session for guest '{$currentCustName}'? Other diners will remain active.";
                                            @endphp
                                            <button wire:click.stop="cleanDineInSession({{ $selectedSessionId }}, {{ $isCurrentPrimary ? 'true' : 'false' }})" 
                                                    onclick="confirm('{{ $vacateConfirm }}') || event.stopImmediatePropagation()" 
                                                    style="width: 100%; padding: 10px 14px; background: #d1fae5; border: 1px solid #a7f3d0; color: #065f46; border-radius: 12px; font-size: 12px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 1px 2px rgba(6, 95, 70, 0.08);">
                                                <x-heroicon-s-check-circle style="width: 16px; height: 16px; color: #059669;" />
                                                <span>{{ $vacateLabel }}</span>
                                            </button>

                                            {{-- 2. Clean & Vacate Entire Table (Secondary Danger Action) --}}
                                            <button wire:click.stop="cleanTable({{ $selectedTableId }})" 
                                                    onclick="confirm('⚠️ WARNING: This will immediately end ALL active sessions for ALL parties at this table and mark it Available. Are you sure you want to clean the entire table?') || event.stopImmediatePropagation()" 
                                                    style="width: 100%; padding: 8px 12px; background: #fff1f2; border: 1px solid #fecdd3; color: #be123c; border-radius: 10px; font-size: 11px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;">
                                                <x-heroicon-s-sparkles style="width: 14px; height: 14px; color: #e11d48;" />
                                                <span>Clean & Vacate Entire Table (All Parties)</span>
                                            </button>

                                            {{-- All-In-One Quick Status --}}
                                            @if(($isAllInOne ?? false) && $selectedTableId)
                                                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; margin-top: 4px;">
                                                    <button wire:click.stop="changeTableStatus({{ $selectedTableId }}, 'available')" style="padding: 6px; font-size: 11px; font-weight: 700; border-radius: 8px; border: 1px solid #a7f3d0; background: #d1fae5; color: #065f46; cursor: pointer;">Available</button>
                                                    <button wire:click.stop="changeTableStatus({{ $selectedTableId }}, 'occupied')" style="padding: 6px; font-size: 11px; font-weight: 700; border-radius: 8px; border: 1px solid #fecdd3; background: #ffe4e6; color: #be123c; cursor: pointer;">Occupied</button>
                                                    <button wire:click.stop="changeTableStatus({{ $selectedTableId }}, 'cleaning')" style="padding: 6px; font-size: 11px; font-weight: 700; border-radius: 8px; border: 1px solid #fde68a; background: #fef3c7; color: #92400e; cursor: pointer;">Cleaning</button>
                                                </div>
                                            @endif
                                        </div>
                                    @elseif($selectedParcelCounterId)
                                        <div style="margin-bottom: 16px;">
                                            <span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #94a3b8; display: block; margin-bottom: 6px;">Queue Actions</span>
                                            <button wire:click.stop="cleanParcelSession({{ $selectedSessionId }})" onclick="confirm('Has this customer paid and received their food? This will close their session.') || event.stopImmediatePropagation()" style="width: 100%; padding: 10px 14px; background: #d1fae5; border: 1px solid #a7f3d0; color: #065f46; border-radius: 12px; font-size: 12px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                                                <x-heroicon-s-check-circle style="width: 16px; height: 16px;" />
                                                <span>Complete Parcel & Clear</span>
                                            </button>
                                        </div>
                                    @endif

                                    {{-- Bill Computation Section --}}
                                    <div style="padding-top: 12px; border-top: 1px solid #e2e8f0;">
                                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                                            <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Bill Computation</span>
                                            <span style="font-size: 12px; color: #64748b;">Guest: <strong style="color: #0f172a;">{{ $currentCustName }}</strong></span>
                                        </div>

                                        {{-- Subtotal Row --}}
                                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: #334155; margin-bottom: 8px;">
                                            <span style="font-weight: 500;">Orders Subtotal ({{ $totalItemsCount }} Items)</span>
                                            <span style="font-weight: 800; font-family: monospace; color: #0f172a; font-size: 14px;">₹{{ number_format($displaySubtotal, 2) }}</span>
                                        </div>

                                        @if($amountAlreadyPaid > 0)
                                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #059669; margin-bottom: 8px;">
                                                <span>Already Paid</span>
                                                <span style="font-weight: 800; font-family: monospace;">- ₹{{ number_format($amountAlreadyPaid, 2) }}</span>
                                            </div>
                                        @endif

                                        {{-- 3 Quick Adjust Inputs (Discount, GST, Extra) --}}
                                        @if(!$pendingPayment && $subtotal > 0)
                                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin: 12px 0;">
                                                <div>
                                                    <label style="display: block; font-size: 10px; font-weight: 700; color: #64748b; margin-bottom: 2px;">Discount (₹)</label>
                                                    <input wire:model.live="discountAmount" style="width: 100%; box-sizing: border-box; padding: 6px 8px; border-radius: 8px; border: 1px solid #cbd5e1; background: #f8fafc; font-size: 12px; font-weight: 700; outline: none;" type="number" placeholder="0"/>
                                                </div>
                                                <div>
                                                    <label style="display: block; font-size: 10px; font-weight: 700; color: #64748b; margin-bottom: 2px;">GST / Tax (%)</label>
                                                    <input wire:model.live="taxPercentage" style="width: 100%; box-sizing: border-box; padding: 6px 8px; border-radius: 8px; border: 1px solid #cbd5e1; background: #f8fafc; font-size: 12px; font-weight: 700; outline: none;" type="number" placeholder="0"/>
                                                </div>
                                                <div>
                                                    <label style="display: block; font-size: 10px; font-weight: 700; color: #64748b; margin-bottom: 2px;">Extra / Round (₹)</label>
                                                    <input wire:model.live="extraCharges" style="width: 100%; box-sizing: border-box; padding: 6px 8px; border-radius: 8px; border: 1px solid #cbd5e1; background: #f8fafc; font-size: 12px; font-weight: 700; outline: none;" type="number" placeholder="0"/>
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Tax Breakdown Details --}}
                                        <div style="padding: 8px 0; border-bottom: 1px dashed #e2e8f0; font-size: 12px; color: #64748b; display: flex; flex-direction: column; gap: 4px;">
                                            <div style="display: flex; justify-content: space-between;">
                                                <span>SGST + CGST ({{ $displayTaxPercent > 0 ? $displayTaxPercent . '%' : '0%' }})</span>
                                                <span style="font-family: monospace; font-weight: 600; color: #334155;">+₹{{ number_format($displayTax, 2) }}</span>
                                            </div>
                                            <div style="display: flex; justify-content: space-between;">
                                                <span>Discounts Applied</span>
                                                <span style="font-family: monospace; font-weight: 600; color: {{ $displayDiscount > 0 ? '#059669' : '#334155' }};">-₹{{ number_format($displayDiscount, 2) }}</span>
                                            </div>
                                            @if($displayExtra > 0)
                                                <div style="display: flex; justify-content: space-between;">
                                                    <span>Extra / Rounding Charges</span>
                                                    <span style="font-family: monospace; font-weight: 600; color: #334155;">+₹{{ number_format($displayExtra, 2) }}</span>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Net Grand Total Display --}}
                                        <div style="padding: 14px 0; display: flex; align-items: baseline; justify-content: space-between;">
                                            <div>
                                                <p style="font-size: 14px; font-weight: 800; color: #0f172a; margin: 0;">Net Amount Due</p>
                                                <p style="font-size: 11px; color: #94a3b8; margin: 2px 0 0 0;">Inclusive of all applied taxes</p>
                                            </div>
                                            <div style="text-align: right;">
                                                <span style="font-size: 28px; font-weight: 900; color: #ea580c; font-family: monospace; line-height: 1;">₹{{ number_format($displayTotal, 2) }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Customer Settlement Status Card --}}
                                    @if($pendingPayment && $pendingPayment->status === 'paid')
                                        <div style="padding: 12px; background: #d1fae5; border: 1px solid #a7f3d0; border-radius: 12px; text-align: center; margin-bottom: 14px;">
                                            <x-heroicon-s-check-circle style="width: 24px; height: 24px; color: #059669; margin: 0 auto 4px auto;" />
                                            <p style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #065f46; margin: 0;">Bill Settled Successfully</p>
                                            <p style="font-size: 13px; font-weight: 800; color: #064e3b; margin: 2px 0 0 0;">Paid: ₹{{ number_format($pendingPayment->amount, 2) }}</p>
                                        </div>
                                    @elseif($pendingPayment)
                                        <div style="padding: 12px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; text-align: center; margin-bottom: 14px;">
                                            <p style="font-size: 10px; font-weight: 800; text-transform: uppercase; color: #b45309; margin: 0;">Payment Method Selection</p>
                                            @if($pendingPayment->payment_method === 'cash')
                                                <div style="display: flex; align-items: center; justify-content: center; gap: 6px; margin-top: 4px;">
                                                    <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #16a34a;"></span>
                                                    <p style="font-size: 14px; font-weight: 900; color: #1e3a8a; margin: 0;">PAY AT COUNTER (CASH)</p>
                                                </div>
                                            @elseif($pendingPayment->payment_method === 'upi')
                                                <div style="display: flex; align-items: center; justify-content: center; gap: 6px; margin-top: 4px;">
                                                    <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #2563eb;"></span>
                                                    <p style="font-size: 14px; font-weight: 900; color: #065f46; margin: 0;">UPI SELECTED BY CUSTOMER</p>
                                                </div>
                                            @else
                                                <div style="display: flex; align-items: center; justify-content: center; gap: 6px; margin-top: 4px;">
                                                    <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #16a34a;"></span>
                                                    <p style="font-size: 14px; font-weight: 900; color: #1e3a8a; margin: 0;">PAY AT COUNTER (CASH)</p>
                                                </div>
                                            @endif

                                            @if(!empty($upiId))
                                                <div style="margin-top: 10px; padding: 8px; background: #ffffff; border: 1px dashed #34d399; border-radius: 8px; display: inline-block;">
                                                    <p style="font-size: 10px; font-weight: 800; color: #059669; margin: 0 0 4px 0;">SCAN TO PAY ₹{{ number_format($pendingPayment->amount, 0) }}</p>
                                                    <div style="display: flex; justify-content: center;">
                                                        {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(105)->margin(0)->generate("upi://pay?pa={$upiId}&pn=" . urlencode($restaurantName) . "&am={$pendingPayment->amount}&cu=INR&tr={$pendingPayment->transaction_reference}") !!}
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    
                                    @endif
                                </div>

                                {{-- Checkout & Settlement Actions --}}
                                <div style="margin-top: 12px; padding-top: 10px; display: flex; flex-direction: column; gap: 8px;">
                                    @if($selectedRoomId)
                                        <button wire:click="settleRoomBill" style="width: 100%; padding: 12px 16px; background: #10b981; color: white; border: none; border-radius: 12px; font-weight: 700; font-size: 13px; cursor: pointer;">
                                            Settle Food Bill to Room
                                        </button>
                                    @else
                                        @if(!$pendingPayment && $subtotal > 0)
                                            <button wire:click="sendBillToCustomer" style="width: 100%; padding: 12px 16px; background: #1e3a8a; color: white; border: none; border-radius: 12px; font-weight: 700; font-size: 14px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 2px 4px rgba(30, 58, 138, 0.25);">
                                                <x-heroicon-s-paper-airplane style="width: 16px; height: 16px;" />
                                                <span>Generate Final Bill & Send QR</span>
                                            </button>
                                        @elseif($pendingPayment && $pendingPayment->status !== 'paid')
                                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                                <button wire:click="printPendingBill" style="padding: 10px; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 10px; font-weight: 700; font-size: 12px; color: #334155; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;">
                                                    <x-heroicon-s-printer style="width: 16px; height: 16px;" />
                                                    <span>Print Bill</span>
                                                </button>
                                                <button wire:click="mountAction('confirmPaymentAction')" style="padding: 10px; background: #059669; border: none; border-radius: 10px; font-weight: 700; font-size: 12px; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;">
                                                    <x-heroicon-s-check-circle style="width: 16px; height: 16px;" />
                                                    <span>Confirm Payment</span>
                                                </button>
                                            </div>
                                            <button wire:click="cancelPendingBill" onclick="confirm('Cancel generated bill?') || event.stopImmediatePropagation()" style="background: none; border: none; padding: 4px; color: #e11d48; font-size: 12px; font-weight: 600; cursor: pointer; text-decoration: underline; text-align: center;">
                                                Cancel Generated Bill
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            @else
                                <div style="height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; color: #94a3b8; padding: 24px;">
                                    <x-heroicon-o-currency-rupee style="width: 48px; height: 48px; color: #cbd5e1; margin-bottom: 8px;" />
                                    <p style="font-weight: 700; color: #334155; font-size: 14px; margin: 0;">Select a customer</p>
                                    <p style="font-size: 12px; color: #94a3b8; margin: 4px 0 0 0;">Select a seated guest or assign a guest to view itemized bills and settlement options.</p>
                                </div>
                            @endif
                        </aside>
                    </div>

                    {{-- MODAL FOOTER STATUS STRIP --}}
                    <footer class="stitch-modal-footer">
                        <div style="display: flex; align-items: center; gap: 16px;">
                            <span style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600; color: #334155;">
                                <span class="stitch-dot" style="background: #10b981;"></span>
                                Cloud POS Sync: Online
                            </span>
                            <span style="color: #94a3b8;">Ann Sathi Floor OS</span>
                        </div>
                        <div style="color: #94a3b8;">
                            Shortcuts: <kbd style="background: white; border: 1px solid #cbd5e1; border-radius: 4px; padding: 2px 6px; font-size: 10px; font-family: monospace; color: #334155;">ESC</kbd> Close
                        </div>
                    </footer>

                </section>
            </div>
        @endif

        
        {{-- ======================================================== --}}
        {{-- 🌟 STITCH SCREEN 3: PLACE ORDER / KOT VISUAL MENU MODAL  --}}
        {{-- Screen ID: 1a515631d2ff4021b0346acbd2a7a53c              --}}
        {{-- ======================================================== --}}
        @if($isPlaceOrderOpen)
            <div class="stitch-order-overlay" wire:click.self="closePlaceOrder" @keydown.escape.window="$wire.closePlaceOrder()">
                <main class="stitch-order-modal" role="dialog" aria-modal="true" aria-labelledby="order-modal-title">
                    
                    {{-- ── 1. MODAL HEADER ── --}}
                    <header class="stitch-order-header">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 12px; background: #fff7ed; border: 1px solid #fed7aa; display: flex; align-items: center; justify-content: center; color: #a33900; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <h1 id="order-modal-title" style="font-size: 17px; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.3px;">
                                        Place Order & Send KOT
                                    </h1>
                                    <span style="font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 9999px; background: #ffedd5; color: #a33900; border: 1px solid #fed7aa;">
                                        KOT #{{ now()->format('Hi') }}
                                    </span>
                                </div>
                                <p style="font-size: 11px; color: #64748b; font-weight: 500; margin: 2px 0 0 0;">
                                    Ann Sathi (अन्न साथी) Floor POS • Kitchen Order Ticket Generation
                                </p>
                            </div>
                        </div>

                        {{-- Metadata Pills & Close Button --}}
                        <div style="display: flex; align-items: center; gap: 10px;">
                            {{-- Table indicator badge --}}
                            @php
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
                            <div style="display: flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 10px; background: #ffffff; border: 1px solid #cbd5e1; font-size: 12px; font-weight: 700; color: #334155; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
                                <span class="stitch-dot animate-pulse-dot" style="background: #10b981;"></span>
                                <span>{{ $destLabel }}</span>
                            </div>

                            {{-- Captain / Manager badge --}}
                            <div style="display: flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 10px; background: #f1f5f9; font-size: 12px; font-weight: 600; color: #475569; border: 1px solid #e2e8f0;">
                                <x-heroicon-m-user style="width: 14px; height: 14px; color: #64748b;" />
                                <span>Captain: <strong style="color: #0f172a;">{{ auth()->user()->name }}</strong></span>
                            </div>

                            {{-- Close X Button --}}
                            <button wire:click="closePlaceOrder" style="width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; border: 1px solid #e2e8f0; background: #ffffff; color: #64748b; cursor: pointer; transition: all 0.2s;" title="Close (ESC)">
                                <x-heroicon-m-x-mark style="width: 18px; height: 18px;" />
                            </button>
                        </div>
                    </header>

                    {{-- ── 2. CONTEXT BAR: CUSTOMER, PAYMENT STATUS & KITCHEN NOTES ── --}}
                    <div class="stitch-order-context-bar">
                        <div style="display: grid; grid-template-columns: 2fr 1.5fr 2.5fr; gap: 14px; align-items: center;">
                            {{-- Customer / Guest Input --}}
                            <div>
                                <label style="display: block; font-size: 10px; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; margin-bottom: 4px;">
                                    Customer / Guest Name *
                                </label>
                                <div style="position: relative;">
                                    <x-heroicon-m-user style="width: 14px; height: 14px; color: #94a3b8; position: absolute; left: 10px; top: 9px;" />
                                    <input wire:model.live.debounce.300ms="placeOrderCustomerName" type="text" placeholder="Enter guest name..." style="width: 100%; box-sizing: border-box; padding: 7px 10px 7px 30px; border-radius: 10px; border: 1px solid #cbd5e1; background: #ffffff; font-size: 12px; font-weight: 700; color: #0f172a; outline: none;">
                                </div>
                            </div>

                            {{-- Payment Status Select --}}
                            <div>
                                <label style="display: block; font-size: 10px; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; margin-bottom: 4px;">
                                    Payment Status *
                                </label>
                                <div style="position: relative;">
                                    <select wire:model.live="placeOrderPaymentStatus" style="width: 100%; box-sizing: border-box; padding: 7px 12px; border-radius: 10px; border: 1px solid #cbd5e1; background: #ffffff; font-size: 12px; font-weight: 700; color: #0f172a; outline: none; cursor: pointer;">
                                        <option value="pending">⏳ Pay Later (At Checkout / Settle)</option>
                                        <option value="paid_upi">⚡ Paid - UPI (GPay/PhonePe)</option>
                                        <option value="paid_card">💳 Paid - Card (EDC Terminal)</option>
                                        <option value="paid_cash">💵 Paid - Cash at Counter</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Kitchen / Chef Instructions --}}
                            <div>
                                <label style="display: block; font-size: 10px; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; margin-bottom: 4px;">
                                    Kitchen / Chef Instructions
                                </label>
                                <div style="position: relative;">
                                    <x-heroicon-m-chat-bubble-left-ellipsis style="width: 14px; height: 14px; color: #94a3b8; position: absolute; left: 10px; top: 9px;" />
                                    <input wire:model.live.debounce.300ms="placeOrderNotes" type="text" placeholder="e.g. Serve starters first, less spicy, allergy notes..." style="width: 100%; box-sizing: border-box; padding: 7px 10px 7px 30px; border-radius: 10px; border: 1px solid #cbd5e1; background: #ffffff; font-size: 12px; color: #0f172a; outline: none;">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ── 3. MAIN WORKSPACE (LEFT RAIL + CENTER CATALOG + RIGHT TRAY) ── --}}
                    <div class="stitch-order-workspace">
                        
                        {{-- LEFT VERTICAL CATEGORY RAIL --}}
                        <aside class="stitch-order-cat-rail stitch-scroll">
                            {{-- All Items Button --}}
                            <button wire:click="setOrderCategory('all')" style="width: 80px; padding: 8px 4px; border-radius: 14px; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; border: 1px solid {{ $placeOrderCategory === 'all' ? '#f8cbb4' : 'transparent' }}; background: {{ $placeOrderCategory === 'all' ? '#fdeee4' : 'transparent' }}; transition: all 0.2s;" type="button">
                                <div style="width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 4px; background: {{ $placeOrderCategory === 'all' ? '#f6d7c3' : '#e2e8f0' }}; color: {{ $placeOrderCategory === 'all' ? '#8f3100' : '#64748b' }};">
                                    <x-heroicon-s-squares-2x2 style="width: 20px; height: 20px;" />
                                </div>
                                <span style="font-size: 11px; font-weight: 800; color: {{ $placeOrderCategory === 'all' ? '#8f3100' : '#475569' }}; text-align: center; line-height: 1.1;">All Items</span>
                                <span style="margin-top: 4px; font-size: 10px; font-weight: 800; padding: 1px 6px; border-radius: 9999px; background: {{ $placeOrderCategory === 'all' ? '#8f3100' : '#cbd5e1' }}; color: {{ $placeOrderCategory === 'all' ? '#ffffff' : '#475569' }}; min-width: 18px; text-align: center;">
                                    {{ $totalMenuItemsCount ?? 0 }}
                                </span>
                            </button>

                            {{-- Categories from DB --}}
                            @foreach($placeOrderCategories ?? [] as $cat)
                                @php
                                    $isActive = (string) $placeOrderCategory === (string) $cat->id;
                                @endphp
                                <button wire:click="setOrderCategory({{ $cat->id }})" style="width: 80px; padding: 8px 4px; border-radius: 14px; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; border: 1px solid {{ $isActive ? '#f8cbb4' : 'transparent' }}; background: {{ $isActive ? '#fdeee4' : 'transparent' }}; transition: all 0.2s;" type="button">
                                    <div style="width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 4px; background: {{ $isActive ? '#f6d7c3' : '#e2e8f0' }}; color: {{ $isActive ? '#8f3100' : '#64748b' }}; font-weight: 800; font-size: 12px; text-transform: uppercase;">
                                        {{ substr($cat->name, 0, 2) }}
                                    </div>
                                    <span style="font-size: 10px; font-weight: 700; color: {{ $isActive ? '#8f3100' : '#475569' }}; text-align: center; line-height: 1.1; max-width: 72px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        {{ $cat->name }}
                                    </span>
                                    <span style="margin-top: 4px; font-size: 10px; font-weight: 800; padding: 1px 6px; border-radius: 9999px; background: {{ $isActive ? '#8f3100' : '#cbd5e1' }}; color: {{ $isActive ? '#ffffff' : '#475569' }}; min-width: 18px; text-align: center;">
                                        {{ $cat->menu_items_count }}
                                    </span>
                                </button>
                            @endforeach
                        </aside>

                        {{-- CENTER DISH CATALOG --}}
                        <section class="stitch-order-catalog stitch-scroll">
                            {{-- Catalog Subheader: Search, Veg filter, and Count --}}
                            <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 12px; margin-bottom: 14px; border-bottom: 1px solid #e2e8f0;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <h2 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0;">
                                        @if($placeOrderCategory === 'all')
                                            All Dishes & Beverages
                                        @else
                                            {{ optional($placeOrderCategories->firstWhere('id', $placeOrderCategory))->name ?? 'Category' }}
                                        @endif
                                    </h2>
                                    <span style="font-size: 12px; font-weight: 600; color: #64748b;">
                                        ({{ count($placeOrderMenuItems ?? []) }} items)
                                    </span>
                                </div>

                                {{-- Search & Veg Chips --}}
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    {{-- Veg / Non-Veg Chips --}}
                                    <div style="display: flex; align-items: center; gap: 4px; background: #ffffff; padding: 3px; border-radius: 10px; border: 1px solid #cbd5e1;">
                                        <button wire:click="setOrderVegFilter('all')" style="padding: 4px 10px; border-radius: 8px; font-size: 11px; font-weight: 700; border: none; cursor: pointer; background: {{ $placeOrderVegFilter === 'all' ? '#0f172a' : 'transparent' }}; color: {{ $placeOrderVegFilter === 'all' ? '#ffffff' : '#475569' }};">
                                            All
                                        </button>
                                        <button wire:click="setOrderVegFilter('veg')" style="padding: 4px 10px; border-radius: 8px; font-size: 11px; font-weight: 700; border: none; cursor: pointer; display: flex; align-items: center; gap: 4px; background: {{ $placeOrderVegFilter === 'veg' ? '#059669' : 'transparent' }}; color: {{ $placeOrderVegFilter === 'veg' ? '#ffffff' : '#059669' }};">
                                            <span style="width: 6px; height: 6px; border-radius: 50%; background: currentColor;"></span>
                                            Veg
                                        </button>
                                        <button wire:click="setOrderVegFilter('non-veg')" style="padding: 4px 10px; border-radius: 8px; font-size: 11px; font-weight: 700; border: none; cursor: pointer; display: flex; align-items: center; gap: 4px; background: {{ $placeOrderVegFilter === 'non-veg' ? '#e11d48' : 'transparent' }}; color: {{ $placeOrderVegFilter === 'non-veg' ? '#ffffff' : '#e11d48' }};">
                                            <span style="width: 6px; height: 6px; border-radius: 50%; background: currentColor;"></span>
                                            Non-Veg
                                        </button>
                                    </div>

                                    {{-- Search Input --}}
                                    <div style="position: relative; width: 200px;">
                                        <x-heroicon-m-magnifying-glass style="width: 14px; height: 14px; color: #94a3b8; position: absolute; left: 10px; top: 9px;" />
                                        <input wire:model.live.debounce.250ms="placeOrderSearch" type="text" placeholder="Search dish..." style="width: 100%; box-sizing: border-box; padding: 6px 10px 6px 30px; border-radius: 10px; border: 1px solid #cbd5e1; background: #ffffff; font-size: 12px; color: #0f172a; outline: none;">
                                    </div>
                                </div>
                            </div>

                            {{-- Dishes Stack / Grid --}}
                            <div style="display: flex; flex-direction: column; gap: 10px; padding-bottom: 24px;">
                                @forelse($placeOrderMenuItems ?? [] as $mItem)
                                    @php
                                        $cQty = $placeOrderCart[$mItem->id]['quantity'] ?? 0;
                                        $isVeg = ($mItem->type ?? 'veg') === 'veg';
                                        $isOos = $mItem->track_stock && $mItem->stock_quantity !== null && $mItem->stock_quantity <= 0;
                                        $imgUrl = $mItem->image_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($mItem->image_path) : null;
                                    @endphp
                                    <article class="stitch-dish-card">
                                        {{-- Dish Details --}}
                                        <div style="flex: 1; min-width: 0;">
                                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                                                @if($isVeg)
                                                    <span class="stitch-veg-icon" title="Vegetarian"><span></span></span>
                                                @else
                                                    <span class="stitch-nonveg-icon" title="Non-Vegetarian"><span></span></span>
                                                @endif
                                                <span style="font-size: 10px; font-weight: 700; text-transform: uppercase; background: #f1f5f9; color: #475569; padding: 2px 6px; border-radius: 4px;">
                                                    {{ $mItem->category?->name ?? 'Dish' }}
                                                </span>
                                            </div>

                                            <h3 style="font-size: 14px; font-weight: 800; color: #0f172a; margin: 0 0 4px 0; line-height: 1.2;">
                                                {{ $mItem->name }}
                                            </h3>

                                            <div style="display: flex; align-items: baseline; gap: 8px; margin-bottom: 6px;">
                                                <span style="font-size: 14px; font-weight: 800; color: #0f172a; font-family: monospace;">₹{{ number_format($mItem->price, 0) }}</span>
                                                @if($mItem->track_stock && $mItem->stock_quantity !== null)
                                                    <span style="font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 4px; border: 1px solid {{ $isOos ? '#fecdd3' : '#a7f3d0' }}; background: {{ $isOos ? '#fff1f2' : '#ecfdf5' }}; color: {{ $isOos ? '#e11d48' : '#059669' }};">
                                                        {{ $isOos ? 'Sold Out' : "Stock: {$mItem->stock_quantity}" }}
                                                    </span>
                                                @endif
                                            </div>

                                            @if($mItem->description)
                                                <p style="font-size: 11px; color: #64748b; line-height: 1.35; margin: 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                                    {{ $mItem->description }}
                                                </p>
                                            @endif
                                        </div>

                                        {{-- Dish Image & Add / Stepper Controls --}}
                                        <div style="display: flex; flex-direction: column; align-items: center; flex-shrink: 0; width: 96px;">
                                            @if($imgUrl)
                                                <img src="{{ $imgUrl }}" alt="{{ $mItem->name }}" style="width: 96px; height: 76px; border-radius: 12px; object-fit: cover; border: 1px solid #e2e8f0; box-shadow: 0 1px 2px rgba(0,0,0,0.04);">
                                            @else
                                                <div style="width: 96px; height: 76px; border-radius: 12px; background: #ffedd5; border: 1px solid #fed7aa; display: flex; align-items: center; justify-content: center; color: #ea580c;">
                                                    <x-heroicon-o-cake style="width: 32px; height: 32px; opacity: 0.6;" />
                                                </div>
                                            @endif

                                            @if($isOos)
                                                <span style="margin-top: -12px; z-index: 10; display: inline-flex; align-items: center; background: #fff1f2; border: 1px solid #fecdd3; color: #e11d48; border-radius: 9999px; padding: 2px 10px; font-size: 10px; font-weight: 800;">
                                                    SOLD OUT
                                                </span>
                                            @elseif($cQty > 0)
                                                <div style="margin-top: -12px; z-index: 10; display: inline-flex; align-items: center; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 9999px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); padding: 2px 6px;">
                                                    <button wire:click="decreaseCart({{ $mItem->id }})" style="width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: none; background: transparent; color: #475569; font-weight: 800; font-size: 14px; cursor: pointer;">−</button>
                                                    <span style="padding: 0 8px; font-size: 12px; font-weight: 800; color: #0f172a;">{{ $cQty }}</span>
                                                    <button wire:click="addToCart({{ $mItem->id }})" style="width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: none; background: transparent; color: #a33900; font-weight: 800; font-size: 14px; cursor: pointer;">+</button>
                                                </div>
                                            @else
                                                <button wire:click="addToCart({{ $mItem->id }})" style="margin-top: -12px; z-index: 10; display: inline-flex; align-items: center; gap: 4px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 9999px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); padding: 4px 12px; font-size: 11px; font-weight: 800; color: #334155; cursor: pointer; transition: all 0.2s;">
                                                    <span style="color: #a33900; font-size: 12px;">+</span> ADD
                                                </button>
                                            @endif
                                        </div>
                                    </article>
                                @empty
                                    <div style="text-align: center; padding: 40px 16px; background: #ffffff; border: 1px dashed #cbd5e1; border-radius: 14px;">
                                        <p style="font-size: 13px; font-weight: 700; color: #64748b; margin: 0;">No dishes found matching your search or category filter.</p>
                                    </div>
                                @endforelse
                            </div>
                        </section>

                        {{-- RIGHT ORDER TICKET TRAY & SUMMARY --}}
                        <aside class="stitch-order-tray stitch-scroll">
                            @php
                                $kotSubtotal = collect($placeOrderCart)->sum(fn($i) => $i['unit_price'] * $i['quantity']);
                                $kotTax = round($kotSubtotal * 0.05, 2);
                                $kotTotal = $kotSubtotal + $kotTax;
                                $kotTotalItems = count($placeOrderCart);
                                $kotTotalQty = collect($placeOrderCart)->sum('quantity');
                            @endphp

                            {{-- Tray Top Header --}}
                            <div style="padding: 16px; border-bottom: 1px solid #e2e8f0; background: #ffffff; flex-shrink: 0;">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                                    <h3 style="font-size: 12px; font-weight: 800; text-transform: uppercase; color: #0f172a; letter-spacing: 0.5px; margin: 0; display: flex; align-items: center; gap: 6px;">
                                        <span>Current KOT Items</span>
                                        <span style="font-size: 10px; font-weight: 800; padding: 2px 6px; border-radius: 9999px; background: #ffedd5; color: #a33900;">
                                            {{ $kotTotalItems }} Items ({{ $kotTotalQty }} Qty)
                                        </span>
                                    </h3>
                                    @if($kotTotalItems > 0)
                                        <button wire:click="clearCart" style="font-size: 11px; font-weight: 700; color: #e11d48; border: none; background: transparent; cursor: pointer; text-decoration: underline;">
                                            Clear All
                                        </button>
                                    @endif
                                </div>
                                <p style="font-size: 11px; color: #64748b; margin: 0;">Destination: <strong style="color: #334155;">Kitchen Display (Station 1)</strong></p>
                            </div>

                            {{-- Tray Selected Items List --}}
                            <div style="padding: 14px; display: flex; flex-direction: column; gap: 10px; flex: 1; overflow-y: auto;">
                                @forelse($placeOrderCart as $cId => $cItem)
                                    <div style="padding: 10px; border-radius: 12px; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
                                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                            <div style="display: flex; align-items: center; gap: 6px;">
                                                @if(($cItem['type'] ?? 'veg') === 'veg')
                                                    <span class="stitch-veg-icon" style="width: 12px; height: 12px;"><span style="width: 5px; height: 5px;"></span></span>
                                                @else
                                                    <span class="stitch-nonveg-icon" style="width: 12px; height: 12px;"><span style="width: 5px; height: 5px;"></span></span>
                                                @endif
                                                <span style="font-size: 12px; font-weight: 800; color: #0f172a;">{{ $cItem['name'] }}</span>
                                            </div>
                                            <span style="font-size: 12px; font-weight: 800; font-family: monospace; color: #0f172a;">₹{{ number_format($cItem['unit_price'] * $cItem['quantity'], 2) }}</span>
                                        </div>

                                        <div style="display: flex; align-items: center; justify-content: space-between; font-size: 11px; color: #64748b; padding-bottom: 6px; border-bottom: 1px dashed #f1f5f9;">
                                            <span>{{ $cItem['quantity'] }} × ₹{{ number_format($cItem['unit_price'], 0) }}</span>
                                            <button wire:click="removeFromCart({{ $cId }})" style="border: none; background: transparent; color: #94a3b8; cursor: pointer; padding: 2px;" title="Remove dish">
                                                <x-heroicon-m-trash style="width: 14px; height: 14px;" />
                                            </button>
                                        </div>

                                        {{-- Line Note --}}
                                        <div style="margin-top: 6px;">
                                            <input wire:model.live.debounce.300ms="placeOrderCart.{{ $cId }}.notes" type="text" placeholder="Cooking note (e.g. extra spicy)..." style="width: 100%; box-sizing: border-box; font-size: 10px; font-style: italic; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 4px 6px; color: #334155; outline: none;">
                                        </div>
                                    </div>
                                @empty
                                    <div style="text-align: center; padding: 36px 14px; background: #ffffff; border-radius: 12px; border: 1px dashed #cbd5e1;">
                                        <x-heroicon-o-shopping-bag style="width: 32px; height: 32px; color: #cbd5e1; margin: 0 auto 6px auto;" />
                                        <p style="font-size: 12px; font-weight: 700; color: #475569; margin: 0;">No items selected</p>
                                        <p style="font-size: 11px; color: #94a3b8; margin: 2px 0 0 0;">Click + ADD on any dish to build KOT ticket.</p>
                                    </div>
                                @endforelse

                                {{-- KOT Priority Options --}}
                                <div style="padding-top: 10px;">
                                    <span style="display: block; font-size: 10px; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; margin-bottom: 6px;">
                                        KOT Priority
                                    </span>
                                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px;">
                                        <label style="cursor: pointer; text-align: center;">
                                            <input wire:model.live="placeOrderPriority" type="radio" value="normal" style="display: none;">
                                            <span style="display: block; padding: 6px 4px; font-size: 11px; font-weight: 700; border-radius: 8px; border: 1px solid {{ $placeOrderPriority === 'normal' ? '#0f172a' : '#cbd5e1' }}; background: {{ $placeOrderPriority === 'normal' ? '#0f172a' : '#ffffff' }}; color: {{ $placeOrderPriority === 'normal' ? '#ffffff' : '#475569' }};">
                                                Standard
                                            </span>
                                        </label>
                                        <label style="cursor: pointer; text-align: center;">
                                            <input wire:model.live="placeOrderPriority" type="radio" value="urgent" style="display: none;">
                                            <span style="display: block; padding: 6px 4px; font-size: 11px; font-weight: 700; border-radius: 8px; border: 1px solid {{ $placeOrderPriority === 'urgent' ? '#a33900' : '#cbd5e1' }}; background: {{ $placeOrderPriority === 'urgent' ? '#a33900' : '#ffffff' }}; color: {{ $placeOrderPriority === 'urgent' ? '#ffffff' : '#a33900' }};">
                                                ⚡ Rush
                                            </span>
                                        </label>
                                        <label style="cursor: pointer; text-align: center;">
                                            <input wire:model.live="placeOrderPriority" type="radio" value="hold" style="display: none;">
                                            <span style="display: block; padding: 6px 4px; font-size: 11px; font-weight: 700; border-radius: 8px; border: 1px solid {{ $placeOrderPriority === 'hold' ? '#4f46e5' : '#cbd5e1' }}; background: {{ $placeOrderPriority === 'hold' ? '#4f46e5' : '#ffffff' }}; color: {{ $placeOrderPriority === 'hold' ? '#ffffff' : '#4f46e5' }};">
                                                ⏸️ Hold
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- Financial Computation Box --}}
                            <div style="padding: 16px; background: #ffffff; border-top: 1px solid #e2e8f0; flex-shrink: 0; font-size: 12px; display: flex; flex-direction: column; gap: 4px;">
                                <div style="display: flex; justify-content: space-between; color: #64748b;">
                                    <span>Item Subtotal</span>
                                    <span style="font-weight: 700; color: #0f172a; font-family: monospace;">₹{{ number_format($kotSubtotal, 2) }}</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; color: #64748b;">
                                    <span>GST / Taxes (5%)</span>
                                    <span style="font-weight: 700; color: #475569; font-family: monospace;">₹{{ number_format($kotTax, 2) }}</span>
                                </div>
                                <div style="display: flex; align-items: baseline; justify-content: space-between; padding-top: 8px; margin-top: 4px; border-top: 1px dashed #cbd5e1;">
                                    <span style="font-size: 13px; font-weight: 800; color: #0f172a;">KOT Total</span>
                                    <span style="font-size: 20px; font-weight: 900; color: #a33900; font-family: monospace;">₹{{ number_format($kotTotal, 2) }}</span>
                                </div>
                            </div>
                        </aside>
                    </div>

                    {{-- ── 4. MODAL FOOTER ACTIONS ── --}}
                    <footer class="stitch-order-footer">
                        {{-- Left Checkbox & Reset --}}
                        <div style="display: flex; align-items: center; gap: 14px;">
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; color: #334155; cursor: pointer; user-select: none;">
                                <input wire:model="placeOrderAutoPrint" type="checkbox" style="border-radius: 4px; color: #a33900;">
                                <span>Auto-Print Kitchen Slip (Thermal 80mm)</span>
                            </label>
                            @if(count($placeOrderCart) > 0)
                                <span style="color: #cbd5e1;">|</span>
                                <button wire:click="clearCart" style="border: none; background: transparent; font-size: 12px; color: #64748b; font-weight: 600; cursor: pointer; text-decoration: underline;">
                                    Reset Selection
                                </button>
                            @endif
                        </div>

                        {{-- Right Buttons --}}
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <button wire:click="closePlaceOrder" style="padding: 10px 18px; border-radius: 12px; border: 1px solid #cbd5e1; background: #ffffff; color: #475569; font-weight: 700; font-size: 12px; cursor: pointer;">
                                Cancel
                            </button>
                            <button wire:click="submitPlaceOrder" style="padding: 10px 22px; border-radius: 12px; border: none; background: #a33900; color: #ffffff; font-weight: 800; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(163, 57, 0, 0.35);">
                                <svg style="width: 16px; height: 16px;" class="animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <span>Send Order to Kitchen (KOT)</span>
                            </button>
                        </div>
                    </footer>
                </main>
            </div>
        @endif

        <x-filament-actions::modals />

        {{-- BROWSER NOTIFICATION AND GUJARATI/HINDI TTS SCRIPT --}}
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

                // Dynamic Text To Speech (Gujarati / Hindi / Indian Accent)
                window.addEventListener('speak-notification', function (e) {
                    if ('speechSynthesis' in window) {
                        const text = e.detail.text || (e.detail[0] ? e.detail[0].text : '');
                        if(!text) return;

                        window.speechSynthesis.cancel();
                        
                        let msg = new SpeechSynthesisUtterance(text);
                        let voices = window.speechSynthesis.getVoices();
                        let indianVoice = voices.find(voice => 
                            voice.lang.includes('gu-IN') || 
                            voice.lang.includes('hi-IN') || 
                            voice.lang.includes('en-IN')
                        );
                        
                        if (indianVoice) {
                            msg.voice = indianVoice;
                        }
                        
                        msg.rate = 0.9;
                        msg.pitch = 1;
                        msg.volume = 1;

                        window.speechSynthesis.speak(msg);
                    }
                });
            });
        </script>
    </div>
</x-filament-panels::page>