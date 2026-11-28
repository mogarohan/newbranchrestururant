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
                max-width: 100%;
                overflow-x: hidden;
                margin-top: -1.25rem;
                padding-bottom: 2rem;
                box-sizing: border-box;
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
                gap: 10px;
                margin-bottom: 12px;
                padding-top: 2px;
            }
            @media (max-width: 768px) {
                .stitch-header-row {
                    flex-direction: column !important;
                    align-items: stretch !important;
                    gap: 8px !important;
                }
                .stitch-header-row > div:last-child {
                    display: grid !important;
                    grid-template-columns: repeat(2, 1fr) !important;
                    gap: 6px !important;
                    width: 100% !important;
                }
                .stitch-header-row > div:last-child button {
                    width: 100% !important;
                    justify-content: center !important;
                    font-size: 11px !important;
                    padding: 8px 6px !important;
                }
            }
            .stitch-title {
                font-size: 20px;
                font-weight: 800;
                color: #0f172a;
                letter-spacing: -0.5px;
                margin: 0;
            }
            .stitch-btn-primary {
                background: #f97316;
                color: #ffffff;
                font-weight: 700;
                font-size: 12px;
                padding: 7px 14px;
                border-radius: 10px;
                border: none;
                box-shadow: 0 2px 6px rgba(249, 115, 22, 0.25);
                display: inline-flex;
                align-items: center;
                gap: 6px;
                cursor: pointer;
                transition: all 0.2s;
            }
            .stitch-btn-primary:hover {
                background: #ea580c;
            }

            /* ─── COMPACT STAT METRICS BAR ─── */
            .stitch-stats-grid {
                display: grid;
                grid-template-columns: repeat(6, 1fr);
                gap: 8px;
                margin-bottom: 12px;
            }
            @media (max-width: 1200px) {
                .stitch-stats-grid {
                    grid-template-columns: repeat(3, 1fr);
                }
            }
            @media (max-width: 640px) {
                .stitch-stats-grid {
                    grid-template-columns: repeat(2, 1fr);
                    gap: 6px;
                }
                .stitch-stat-card {
                    padding: 6px 8px !important;
                }
                .stitch-stat-number {
                    font-size: 16px !important;
                }
            }
            .stitch-stat-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 8px 12px;
                box-shadow: 0 1px 2px rgba(0,0,0,0.03);
                transition: all 0.2s;
            }
            .stitch-stat-card:hover {
                border-color: #cbd5e1;
            }
            .stitch-stat-card.occupied { border-color: #fecdd3; background: #fff5f5; }
            .stitch-stat-card.available { border-color: #a7f3d0; background: #f0fdf4; }
            .stitch-stat-card.bill-pending { border-color: #fde68a; background: #fffbeb; }
            .stitch-stat-card.cleaning { border-color: #bfdbfe; background: #eff6ff; }
            .stitch-stat-card.revenue {
                border-color: #fed7aa;
                background: linear-gradient(135deg, #fff7ed 0%, #ffffff 100%);
            }
            .stitch-stat-title {
                font-size: 10px;
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
                margin-top: 3px;
            }
            .stitch-stat-number {
                font-size: 18px;
                font-weight: 800;
                color: #0f172a;
                line-height: 1;
            }
            .stitch-stat-badge {
                font-size: 10px;
                font-weight: 600;
            }
            .stitch-dot {
                width: 7px;
                height: 7px;
                border-radius: 9999px;
                display: inline-block;
            }

            /* ─── FILTER & SEARCH TOOLING BAR ─── */
            .stitch-filter-bar {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 6px 10px;
                box-shadow: 0 1px 2px rgba(0,0,0,0.02);
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 8px;
                margin-bottom: 14px;
            }
            @media (max-width: 640px) {
                .stitch-filter-bar {
                    flex-direction: column !important;
                    align-items: stretch !important;
                    gap: 6px !important;
                    padding: 6px 8px !important;
                }
                .stitch-filter-tabs {
                    width: 100% !important;
                    overflow-x: auto !important;
                    padding-bottom: 4px !important;
                    -webkit-overflow-scrolling: touch;
                }
                .stitch-search-wrapper {
                    width: 100% !important;
                }
            }
            .stitch-filter-tabs {
                display: flex;
                align-items: center;
                gap: 4px;
                overflow-x: auto;
            }
            .stitch-tab-btn {
                border: none;
                border-radius: 8px;
                padding: 5px 10px;
                font-size: 11px;
                font-weight: 700;
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
                box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            }
            .stitch-search-wrapper {
                position: relative;
                width: 220px;
            }
            .stitch-search-input {
                width: 100%;
                box-sizing: border-box;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                padding: 5px 10px 5px 28px;
                font-size: 11px;
                color: #1e293b;
                outline: none;
                transition: all 0.2s;
            }
            .stitch-search-input:focus {
                border-color: #f97316;
                background: #ffffff;
            }

            /* ─── RESPONSIVE COMPACT FLOOR GRID ─── */
            .stitch-floor-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 12px;
            }
            @media (max-width: 640px) {
                .stitch-floor-grid {
                    grid-template-columns: repeat(2, 1fr);
                    gap: 6px;
                }
                .stitch-card {
                    padding: 8px 8px !important;
                    min-height: 115px !important;
                }
                .stitch-card-title {
                    font-size: 13px !important;
                }
                .stitch-card-meta {
                    font-size: 9px !important;
                }
            }
            @media (max-width: 360px) {
                .stitch-floor-grid {
                    grid-template-columns: 1fr !important;
                }
            }

            /* ─── HIGH DENSITY TABLE CARDS (~135px height) ─── */
            .stitch-card {
                background: #ffffff;
                border-radius: 12px;
                padding: 10px 12px;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                min-height: 125px;
                cursor: pointer;
                transition: all 0.15s;
                box-shadow: 0 1px 2px rgba(0,0,0,0.04);
            }
            .stitch-card:hover {
                transform: translateY(-1.5px);
                box-shadow: 0 4px 10px -2px rgba(0,0,0,0.08);
            }
            .stitch-card.occupied {
                border: 1.5px solid #fb923c;
            }
            .stitch-card.available {
                border: 1.5px dashed #10b981;
            }
            .stitch-card.available:hover {
                border-color: #059669;
            }
            .stitch-card.bill-pending {
                border: 1.5px solid #fbbf24;
            }
            .stitch-card.cleaning {
                border: 1.5px solid #93c5fd;
            }
            .stitch-card.parcel {
                border: 1.5px solid #fdba74;
            }
            .stitch-card-header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
            }
            .stitch-card-title {
                font-size: 15px;
                font-weight: 800;
                color: #0f172a;
                margin: 0;
            }
            .stitch-card-meta {
                font-size: 10px;
                font-weight: 700;
                color: #94a3b8;
                margin-top: 2px;
                display: flex;
                align-items: center;
                gap: 4px;
            }
            .stitch-pill {
                font-size: 9px;
                font-weight: 800;
                text-transform: uppercase;
                padding: 2px 8px;
                border-radius: 9999px;
                letter-spacing: 0.5px;
            }

            /* ─── MODAL OVERLAYS & Z-INDEX HIERARCHY ─── */
            .stitch-modal-overlay {
                position: fixed !important;
                inset: 0 !important;
                background: rgba(15, 23, 42, 0.65);
                backdrop-filter: blur(4px);
                z-index: 8000 !important;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 16px !important;
                box-sizing: border-box;
            }
            @media (max-width: 640px) {
                .stitch-modal-overlay {
                    padding: 6px !important;
                }
            }

            /* ─── HIDE FIXED FOOTER ON MANAGER DASHBOARD & MODALS ─── */
            .fi-footer-custom {
                z-index: 30 !important;
            }
            body:has(.stitch-order-overlay) .fi-footer-custom,
            body:has(.stitch-modal-overlay) .fi-footer-custom,
            body:has(.fi-modal-open) .fi-footer-custom,
            body:has(.manager-dashboard-container) .fi-footer-custom {
                display: none !important;
                visibility: hidden !important;
                opacity: 0 !important;
                pointer-events: none !important;
                height: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
                border: none !important;
            }

            /* ─── KOT VISUAL MENU & INSTANT BILL MODAL (MOBILE-PERFECT STITCH DESIGN) ─── */
            .stitch-order-overlay {
                position: fixed !important;
                inset: 0 !important;
                background: rgba(11, 28, 48, 0.88);
                backdrop-filter: blur(8px);
                z-index: 9999999 !important;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 16px !important;
                box-sizing: border-box;
            }
            @media (max-width: 1024px) {
                .stitch-order-overlay {
                    padding: 0 !important;
                    height: 100vh !important;
                    height: 100dvh !important;
                    width: 100vw !important;
                }
            }
            .stitch-order-modal {
                background: #ffffff;
                width: 100%;
                max-width: 1240px;
                height: 100%;
                max-height: calc(100vh - 32px) !important;
                border-radius: 18px;
                box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.4), 0 10px 20px -8px rgba(163, 57, 0, 0.15);
                border: 1px solid rgba(226, 232, 240, 0.9);
                display: flex;
                flex-direction: column;
                overflow: hidden;
                position: relative;
            }
            @media (max-width: 1024px) {
                .stitch-order-modal {
                    max-width: 100vw !important;
                    width: 100vw !important;
                    height: 100vh !important;
                    height: 100dvh !important;
                    max-height: 100dvh !important;
                    border-radius: 0 !important;
                    border: none !important;
                }
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
            @media (max-width: 1024px) {
                .stitch-order-header {
                    padding: 8px 12px !important;
                    min-height: 44px !important;
                }
            }
            .stitch-order-context-bar {
                background: rgba(248, 249, 255, 0.95);
                border-bottom: 1px solid #e2e8f0;
                padding: 10px 16px;
                flex-shrink: 0;
            }
            .stitch-order-context-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 10px;
                align-items: center;
            }
            @media (max-width: 1024px) {
                .stitch-order-context-grid {
                    grid-template-columns: repeat(2, 1fr) !important;
                    gap: 8px !important;
                }
            }
            @media (max-width: 640px) {
                .stitch-order-context-bar {
                    padding: 8px 10px !important;
                }
                .stitch-order-context-grid {
                    grid-template-columns: 1fr !important;
                    gap: 6px !important;
                }
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
                    flex-direction: row !important;
                    height: calc(100dvh - 54px) !important;
                    overflow: hidden !important;
                }
            }
            .stitch-order-cat-rail {
                width: 82px;
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
            @media (max-width: 1024px) {
                .stitch-order-cat-rail {
                    width: 74px !important;
                    padding: 6px 2px !important;
                    background: #f8fafc !important;
                    border-right: 1px solid #e2e8f0 !important;
                    flex-direction: column !important;
                    overflow-y: auto !important;
                    flex-shrink: 0 !important;
                }
            }
            .stitch-cat-btn {
                width: 74px;
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
            @media (max-width: 1024px) {
                .stitch-cat-btn {
                    width: 68px !important;
                    padding: 5px 2px !important;
                }
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
                padding: 12px 16px;
                background: rgba(248, 249, 255, 0.4);
                min-width: 0;
            }
            @media (max-width: 1024px) {
                .stitch-order-catalog {
                    padding: 8px 10px !important;
                    height: 100% !important;
                    flex: 1 !important;
                }
            }
            .stitch-catalog-toolbar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding-bottom: 8px;
                margin-bottom: 10px;
                border-bottom: 1px solid #e2e8f0;
                gap: 8px;
                flex-wrap: wrap;
            }
            @media (max-width: 640px) {
                .stitch-catalog-toolbar {
                    flex-direction: column !important;
                    align-items: stretch !important;
                    gap: 6px !important;
                }
                .stitch-catalog-toolbar > div {
                    width: 100% !important;
                    justify-content: space-between !important;
                }
                .stitch-catalog-search {
                    width: 100% !important;
                }
            }
            .stitch-order-tray {
                width: 380px;
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
                    width: 100% !important;
                    max-width: 100% !important;
                    border-left: none !important;
                    height: 100% !important;
                    overflow-y: auto !important;
                }
                .stitch-order-footer {
                    display: none !important;
                }
                .stitch-order-cat-rail[style*="display: none"],
                .stitch-order-catalog[style*="display: none"],
                .stitch-order-tray[style*="display: none"] {
                    display: none !important;
                }
            }
            .stitch-dish-card {
                background: #ffffff;
                border-radius: 14px;
                border: 1px solid #e2e8f0;
                padding: 10px 14px;
                box-shadow: 0 1px 3px rgba(15, 28, 48, 0.04);
                transition: all 0.2s;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
            }
            .stitch-dish-card:hover {
                border-color: #f8cbb4;
                box-shadow: 0 4px 14px rgba(163, 57, 0, 0.08);
            }
            @media (max-width: 480px) {
                .stitch-dish-card {
                    padding: 8px 10px !important;
                    gap: 8px !important;
                }
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

            /* ─── FILAMENT ACTION MODALS (ASSIGN GUEST, EDIT ORDER, CONFIRM PAYMENT) ─── */
            .fi-modal:not(.fi-modal-open) {
                display: none !important;
                pointer-events: none !important;
            }
            .fi-modal.fi-modal-open {
                position: fixed !important;
                inset: 0 !important;
                z-index: 9000 !important;
                pointer-events: auto !important;
            }
            .fi-modal.fi-modal-open .fi-modal-close-overlay {
                position: fixed !important;
                inset: 0 !important;
                z-index: 9001 !important;
                background: rgba(15, 23, 42, 0.65) !important;
                backdrop-filter: blur(4px) !important;
            }
            .fi-modal.fi-modal-open .fixed.inset-0 {
                position: fixed !important;
                inset: 0 !important;
                z-index: 9002 !important;
            }
            .fi-modal.fi-modal-open .fi-modal-window {
                position: relative !important;
                z-index: 9003 !important;
                box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.55) !important;
            }
            .fi-dropdown-panel,
            .choices__list--dropdown,
            .flatpickr-calendar {
                z-index: 9500 !important;
            }
            .fi-notifications,
            .fi-notification {
                z-index: 99999 !important;
            }

            /* ─── STITCH ASSIGN GUEST MODAL STYLING (High Density & Responsive) ─── */
            .stitch-assign-guest-modal {
                border-radius: 20px !important;
                border: 1px solid #e2e8f0 !important;
                box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.45) !important;
                overflow: hidden !important;
                max-width: 620px !important;
                width: 100% !important;
                max-height: calc(100vh - 80px) !important;
                font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif !important;
            }
            .stitch-assign-guest-modal .fi-modal-header {
                padding: 16px 20px !important;
                background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%) !important;
                border-bottom: 1px solid #e2e8f0 !important;
            }
            .stitch-assign-guest-modal .fi-modal-heading {
                font-size: 16px !important;
                font-weight: 800 !important;
                color: #0f172a !important;
                letter-spacing: -0.3px !important;
            }
            .stitch-assign-guest-modal .fi-modal-description {
                font-size: 11px !important;
                color: #64748b !important;
                margin-top: 2px !important;
            }
            .stitch-assign-guest-modal .fi-modal-content {
                padding: 18px 20px !important;
                overflow-y: auto !important;
                max-height: calc(100vh - 210px) !important;
            }
            .stitch-assign-guest-modal .fi-modal-footer {
                padding: 12px 20px !important;
                background: #f8fafc !important;
                border-top: 1px solid #e2e8f0 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: flex-end !important;
                gap: 8px !important;
            }
            .stitch-assign-guest-modal .fi-input-wrapper {
                border-radius: 10px !important;
                border-color: #cbd5e1 !important;
                background-color: #f8fafc !important;
                transition: all 0.2s !important;
            }
            .stitch-assign-guest-modal .fi-input-wrapper:focus-within {
                border-color: #f97316 !important;
                background-color: #ffffff !important;
                box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.15) !important;
            }
            .stitch-assign-guest-modal .fi-btn-primary {
                background: linear-gradient(135deg, #f97316 0%, #ea580c 100%) !important;
                color: #ffffff !important;
                font-weight: 800 !important;
                font-size: 12px !important;
                border-radius: 10px !important;
                padding: 8px 16px !important;
                border: none !important;
                box-shadow: 0 2px 8px rgba(249, 115, 22, 0.25) !important;
                transition: all 0.2s !important;
            }
            .stitch-assign-guest-modal .fi-btn-primary:hover {
                background: #ea580c !important;
                box-shadow: 0 4px 12px rgba(249, 115, 22, 0.35) !important;
            }
            .stitch-assign-guest-modal .fi-btn-secondary {
                border-radius: 10px !important;
                font-size: 12px !important;
                font-weight: 700 !important;
                padding: 8px 14px !important;
            }
            @media (max-width: 640px) {
                .stitch-assign-guest-modal {
                    margin: 8px !important;
                    width: calc(100% - 16px) !important;
                }
                .stitch-assign-guest-modal .fi-modal-header,
                .stitch-assign-guest-modal .fi-modal-content,
                .stitch-assign-guest-modal .fi-modal-footer {
                    padding: 12px 14px !important;
                }
            }
            .stitch-modal-box {
                background: #ffffff;
                width: 100%;
                max-width: 1400px;
                height: 100%;
                max-height: calc(100vh - 36px) !important;
                border-radius: 18px;
                box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.45);
                border: 1px solid #e2e8f0;
                display: flex;
                flex-direction: column;
                overflow: hidden;
            }
            .stitch-modal-box.stitch-modal-compact {
                max-width: 500px !important;
                height: auto !important;
                max-height: 85vh !important;
                margin: auto !important;
            }
            @media (max-width: 640px) {
                .stitch-modal-box {
                    border-radius: 14px !important;
                    max-height: calc(100vh - 12px) !important;
                    height: calc(100vh - 12px) !important;
                }
                .stitch-modal-box.stitch-modal-compact {
                    max-width: calc(100vw - 20px) !important;
                    height: auto !important;
                    max-height: calc(100vh - 24px) !important;
                    margin: auto !important;
                }
            }
            .stitch-modal-header {
                padding: 12px 20px;
                border-bottom: 1px solid #e2e8f0;
                background: #ffffff;
                display: flex;
                align-items: center;
                justify-content: space-between;
                flex-shrink: 0;
                gap: 10px;
            }
            @media (max-width: 640px) {
                .stitch-modal-header {
                    padding: 10px 12px !important;
                }
            }
            .stitch-mobile-tab-bar {
                display: none;
                padding: 6px 12px;
                background: #f8fafc;
                border-bottom: 1px solid #e2e8f0;
                gap: 6px;
                flex-shrink: 0;
            }
            .stitch-mobile-tab-btn {
                flex: 1;
                padding: 7px 8px;
                border-radius: 8px;
                font-size: 11px;
                font-weight: 700;
                border: 1px solid #cbd5e1;
                background: #ffffff;
                color: #475569;
                cursor: pointer;
                text-align: center;
                transition: all 0.15s;
            }
            .stitch-mobile-tab-btn.active {
                background: #0f172a;
                color: #ffffff;
                border-color: #0f172a;
                box-shadow: 0 1px 3px rgba(0,0,0,0.12);
            }
            .stitch-modal-body {
                display: flex;
                flex-direction: row;
                flex: 1;
                overflow: hidden;
                background: #f8fafc;
                min-height: 0;
            }
            @media (max-width: 1024px) {
                .stitch-mobile-tab-bar {
                    display: flex !important;
                }
                .stitch-modal-body {
                    flex-direction: column !important;
                    overflow-y: auto !important;
                }
                .stitch-col-guests {
                    display: none !important;
                    width: 100% !important;
                    max-width: 100% !important;
                    border-right: none !important;
                    padding: 12px 14px !important;
                    box-sizing: border-box !important;
                }
                .stitch-col-orders {
                    display: none !important;
                    width: 100% !important;
                    max-width: 100% !important;
                    border-right: none !important;
                    padding: 12px 14px !important;
                    box-sizing: border-box !important;
                }
                .stitch-col-billing {
                    display: none !important;
                    width: 100% !important;
                    max-width: 100% !important;
                    border-right: none !important;
                    padding: 12px 14px !important;
                    box-sizing: border-box !important;
                }
                .stitch-col-guests.show-mobile,
                .stitch-col-orders.show-mobile,
                .stitch-col-billing.show-mobile {
                    display: flex !important;
                    flex: 1 !important;
                }
            }
            .stitch-col-guests {
                width: 310px;
                flex-shrink: 0;
                border-right: 1px solid #e2e8f0;
                background: #ffffff;
                padding: 14px;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                overflow-y: auto;
            }
            .stitch-col-orders {
                flex: 1;
                border-right: 1px solid #e2e8f0;
                background: #f8fafc;
                padding: 16px;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                overflow-y: auto;
                min-width: 0;
            }
            .stitch-col-billing {
                width: 360px;
                flex-shrink: 0;
                background: #ffffff;
                padding: 16px;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                overflow-y: auto;
            }
            .stitch-modal-footer {
                padding: 8px 20px;
                background: #f8fafc;
                border-top: 1px solid #e2e8f0;
                font-size: 11px;
                color: #64748b;
                display: flex;
                align-items: center;
                justify-content: space-between;
                flex-shrink: 0;
            }
            @media (max-width: 640px) {
                .stitch-modal-footer {
                    padding: 6px 12px !important;
                    font-size: 10px !important;
                }
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
        {{-- 1. FLOOR HEADER (Screen 1 - Minimal & Concise)            --}}
        {{-- ======================================================== --}}
        <div class="stitch-header-row">
            <div style="display: flex; align-items: center; gap: 10px;">
                <h1 class="stitch-title">Floor Control</h1>
                <span style="font-size: 11px; font-weight: 700; background: #e2e8f0; color: #475569; padding: 2px 8px; border-radius: 6px;">Live POS</span>
            </div>
            
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                @if($hasRoomsFacility)
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 2px; display: flex; gap: 2px;">
                        <button wire:click="switchTab('tables')" class="stitch-tab-btn {{ $currentTab == 'tables' ? 'active' : '' }}">Tables</button>
                        <button wire:click="switchTab('rooms')" class="stitch-tab-btn {{ $currentTab == 'rooms' ? 'active' : '' }}">Rooms</button>
                    </div>
                @endif

                <button wire:click="openInstantBilling" class="stitch-btn-primary" style="background: linear-gradient(135deg, #15803d 0%, #16a34a 100%); box-shadow: 0 2px 6px rgba(21, 128, 61, 0.25);">
                    <x-heroicon-s-bolt style="width: 14px; height: 14px;" />
                    <span>⚡ Instant Bill</span>
                </button>

                <button wire:click="openPlaceOrder" class="stitch-btn-primary" style="background: #0f172a; box-shadow: 0 2px 6px rgba(15, 23, 42, 0.25);">
                    <x-heroicon-s-plus-circle style="width: 14px; height: 14px;" />
                    <span>+ Place Order (Table)</span>
                </button>

                <button wire:click="mountAction('assignGuestAction')" class="stitch-btn-primary">
                    <x-heroicon-s-user-plus style="width: 14px; height: 14px;" />
                    <span>Assign Guest</span>
                </button>
            </div>
        </div>

        {{-- ======================================================== --}}
        {{-- 2. METRIC STAT CARDS BAR (Screen 1 - Compact)            --}}
        {{-- ======================================================== --}}
        <div class="stitch-stats-grid">
            {{-- Total Tables --}}
            <div class="stitch-stat-card">
                <div class="stitch-stat-title" style="color: #64748b;">
                    <span>Tables</span>
                </div>
                <div class="stitch-stat-val-row">
                    <span class="stitch-stat-number">{{ $totalTablesCount ?? count($tables) }}</span>
                    <span class="stitch-stat-badge" style="color: #64748b;">Floor</span>
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
                    <span class="stitch-stat-badge" style="color: #e11d48;">{{ $occupancyRate ?? 0 }}%</span>
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
                    <span class="stitch-stat-badge" style="color: #059669;">Ready</span>
                </div>
            </div>

            {{-- Bill Pending --}}
            <div class="stitch-stat-card bill-pending">
                <div class="stitch-stat-title" style="color: #d97706;">
                    <span>Bill Due</span>
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
                    <span class="stitch-stat-badge" style="color: #2563eb;">Housekeep</span>
                </div>
            </div>

            {{-- Shift Revenue --}}
            <div class="stitch-stat-card revenue">
                <div class="stitch-stat-title" style="color: #c2410c;">
                    <span>Revenue</span>
                </div>
                <div class="stitch-stat-val-row">
                    <span class="stitch-stat-number" style="color: #ea580c; font-family: monospace;">₹{{ number_format($shiftRevenue ?? 0, 0) }}</span>
                    <span class="stitch-stat-badge" style="color: #64748b;">{{ $shiftPaidCount ?? 0 }} Paid</span>
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
        @if(count($activeAlerts ?? []) > 0)
            <div style="background: #fff1f2; border: 1px solid #fecdd3; border-radius: 16px; padding: 14px 16px; margin-bottom: 16px;">
                <h2 style="color: #be123c; font-weight: 800; font-size: 11px; text-transform: uppercase; margin: 0 0 10px 0; display: flex; align-items: center; gap: 6px;">
                    <x-heroicon-s-bell-alert style="width: 16px; height: 16px;" class="animate-bounce" /> Customer Assistance Required ({{ count($activeAlerts) }})
                </h2>
                <div style="display: flex; gap: 12px; overflow-x: auto; padding-bottom: 6px; -webkit-overflow-scrolling: touch;" class="pos-scroll">
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
                <div style="display: flex; gap: 12px; overflow-x: auto; padding-bottom: 6px; -webkit-overflow-scrolling: touch;" class="pos-scroll">
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
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <span style="font-size: 10px; font-weight: 800; background: {{ $cBg }}; color: {{ $cColor }}; padding: 2px 6px; border-radius: 4px;">{{ $cLabel }}</span>
                                            <select wire:change="updateStatus({{ $order->id }}, $event.target.value)" wire:loading.attr="disabled"
                                                style="font-size: 10px; font-weight: 800; border-radius: 4px; padding: 1px 4px; border: 1px solid #cbd5e1; background: #f8fafc; color: #334155; cursor: pointer;">
                                                <option value="placed" {{ $oStatus === 'placed' ? 'selected' : '' }}>Placed</option>
                                                <option value="accepted" {{ in_array($oStatus, ['accepted', 'partial_accepted']) ? 'selected' : '' }}>Accepted</option>
                                                <option value="preparing" {{ $oStatus === 'preparing' ? 'selected' : '' }}>Preparing</option>
                                                <option value="ready" {{ $oStatus === 'ready' ? 'selected' : '' }}>Ready</option>
                                                <option value="served" {{ $oStatus === 'served' ? 'selected' : '' }}>Served</option>
                                                <option value="rejected" {{ in_array($oStatus, ['rejected', 'cancelled']) ? 'selected' : '' }}>Cancel</option>
                                            </select>
                                        </div>
                                        <p style="font-size: 14px; font-weight: 800; color: #0f172a; margin: 4px 0 0 0;">Table-{{ $finalOrderNum }}</p>
                                        <p style="font-size: 11px; color: #64748b; margin: 2px 0 0 0;">{{ $order->customer_name }} • #{{ $order->id }}</p>
                                        @if(!empty($order->notes))
                                            <p style="font-size: 10px; font-weight: 700; color: #b45309; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 4px; padding: 2px 6px; margin: 4px 0 0 0;">
                                                📝 {{ $order->notes }}
                                            </p>
                                        @endif
                                    </div>
                                    <span style="font-weight: 800; font-family: monospace; font-size: 14px; color: #0f172a;">₹{{ number_format($order->total_amount, 0) }}</span>
                                </div>
                                <div style="margin-bottom: 8px; max-height: 90px; overflow-y: auto;" class="stitch-scroll">
                                    @foreach($order->items as $item)
                                        @php
                                            $iStatus = $item->status ?? 'placed';
                                            $iColor = match($iStatus) {
                                                'ready' => '#10b981',
                                                'preparing' => '#f97316',
                                                'served' => '#2563eb',
                                                default => '#64748b'
                                            };
                                            $iBg = match($iStatus) {
                                                'ready' => '#dcfce7',
                                                'preparing' => '#ffedd5',
                                                'served' => '#dbeafe',
                                                default => '#f1f5f9'
                                            };
                                        @endphp
                                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #334155; margin: 0 0 4px 0; gap: 4px;">
                                            <span style="flex: 1; {{ in_array($iStatus, ['ready', 'served']) ? 'text-decoration: line-through; color: #94a3b8;' : '' }}">
                                                <strong style="color: #ea580c; font-family: monospace;">{{ $item->quantity }}x</strong> {{ $item->menuItem->name ?? $item->item_name }}
                                            </span>
                                            <select wire:change="updateItemStatus({{ $item->id }}, $event.target.value)" wire:loading.attr="disabled"
                                                style="font-size: 9px; font-weight: 800; padding: 1px 4px; border-radius: 4px; border: 1px solid {{ $iColor }}50; background: {{ $iBg }}; color: {{ $iColor }}; cursor: pointer; text-transform: uppercase;">
                                                <option value="placed" {{ $iStatus === 'placed' ? 'selected' : '' }}>Waiting</option>
                                                <option value="preparing" {{ $iStatus === 'preparing' ? 'selected' : '' }}>Cooking</option>
                                                <option value="ready" {{ $iStatus === 'ready' ? 'selected' : '' }}>Ready</option>
                                                <option value="served" {{ $iStatus === 'served' ? 'selected' : '' }}>Served</option>
                                            </select>
                                        </div>
                                        @if(!empty($item->notes))
                                            <p style="font-size: 10px; color: #ea580c; font-style: italic; margin: -2px 0 4px 14px;">
                                                🌶️ {{ $item->notes }}
                                            </p>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                            <div style="padding-top: 8px; border-top: 1px solid #f1f5f9;">
                                @if($oStatus === 'placed')
                                    <div style="display: flex; gap: 6px;">
                                        <button wire:click="updateStatus({{ $order->id }}, 'accepted')" wire:loading.attr="disabled" style="flex: 1; padding: 6px; background: #ea580c; color: white; border: none; border-radius: 6px; font-weight: 700; font-size: 12px; cursor: pointer;">Accept</button>
                                        <button wire:click="updateStatus({{ $order->id }}, 'rejected')" wire:loading.attr="disabled" onclick="confirm('Reject this order?') || event.stopImmediatePropagation()" style="padding: 6px 12px; border: 1px solid #fecdd3; color: #e11d48; background: transparent; border-radius: 6px; font-weight: 700; font-size: 12px; cursor: pointer;">Reject</button>
                                    </div>
                                @elseif(in_array($oStatus, ['accepted', 'partial_accepted']))
                                    <button wire:click="updateStatus({{ $order->id }}, 'preparing')" wire:loading.attr="disabled" style="width: 100%; padding: 6px; background: #f59e0b; color: white; border: none; border-radius: 6px; font-weight: 700; font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px;">
                                        <x-heroicon-s-fire style="width: 14px; height: 14px;" /> START PREPARING
                                    </button>
                                @elseif($oStatus === 'preparing')
                                    <button wire:click="updateStatus({{ $order->id }}, 'ready')" wire:loading.attr="disabled" style="width: 100%; padding: 6px; background: #10b981; color: white; border: none; border-radius: 6px; font-weight: 700; font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px;">
                                        <x-heroicon-s-check-circle style="width: 14px; height: 14px;" /> MARK READY
                                    </button>
                                @elseif($oStatus === 'ready')
                                    <button wire:click="updateStatus({{ $order->id }}, 'served')" wire:loading.attr="disabled" style="width: 100%; padding: 6px; background: #2563eb; color: white; border: none; border-radius: 6px; font-weight: 700; font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px;">
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
                                <div style="margin: 8px 0; text-align: center;">
                                    <div style="width: 28px; height: 28px; border-radius: 50%; background: #dbeafe; color: #2563eb; display: flex; align-items: center; justify-content: center; margin: 0 auto 4px auto;">
                                        <x-heroicon-s-sparkles style="width: 16px; height: 16px;" />
                                    </div>
                                    <p style="font-size: 12px; font-weight: 700; color: #1e293b; margin: 0;">Cleaning in Progress</p>
                                    <p style="font-size: 10px; color: #94a3b8; margin: 1px 0 0 0;">Ready soon</p>
                                </div>
                            </div>
                            <div style="padding-top: 8px; border-top: 1px solid #f1f5f9;">
                                <button wire:click.stop="changeTableStatus({{ $table->id }}, 'available')" style="width: 100%; padding: 6px; border-radius: 8px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; font-size: 11px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px;">
                                    <x-heroicon-s-check style="width: 14px; height: 14px;" />
                                    <span>Mark Cleaned</span>
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
                                <div style="margin-top: 8px; padding-top: 6px; border-top: 1px solid #f1f5f9;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; font-size: 11px; margin-bottom: 4px;">
                                        <div style="display: flex; align-items: center; gap: 4px; color: #475569; font-weight: 600;">
                                            <x-heroicon-s-user-group style="width: 13px; height: 13px; color: #94a3b8;" />
                                            <span>Bills: {{ $table->active_sessions_count }}</span>
                                        </div>
                                        <span style="background: #f1f5f9; padding: 1px 6px; border-radius: 4px; font-size: 10px; font-weight: 600; color: #334155; max-width: 100px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            {{ $table->primary_guest_name ?? 'Guest' }}
                                        </span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; font-size: 10px; color: #64748b;">
                                        <span>{{ $table->live_orders_count ?? 0 }} Items</span>
                                        <span style="color: #d97706; font-weight: 700;">Waiting Payment</span>
                                    </div>
                                </div>
                            </div>
                            <div style="margin-top: 8px; padding-top: 6px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
                                <div>
                                    <span style="font-size: 9px; font-weight: 700; text-transform: uppercase; color: #94a3b8; display: block;">Total Due</span>
                                    <span style="font-size: 15px; font-weight: 800; font-family: monospace; color: #d97706;">₹{{ number_format($table->live_due ?? 0, 2) }}</span>
                                </div>
                                <button wire:click.stop="openTable({{ $table->id }})" style="background: #f59e0b; color: white; border: none; border-radius: 8px; padding: 5px 12px; font-size: 11px; font-weight: 700; cursor: pointer; box-shadow: 0 1px 2px rgba(245, 158, 11, 0.3);">
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
                                                <span style="color: #64748b;">{{ $seatedDuration }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="stitch-pill" style="background: #ffe4e6; color: #be123c; border: 1px solid #fecdd3;">OCCUPIED</span>
                                </div>
                                <div style="margin-top: 8px; padding-top: 6px; border-top: 1px solid #f1f5f9;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; font-size: 11px; margin-bottom: 4px;">
                                        <div style="display: flex; align-items: center; gap: 4px; color: #475569; font-weight: 600;">
                                            <x-heroicon-s-user-group style="width: 13px; height: 13px; color: #94a3b8;" />
                                            <span>Bills: {{ $table->active_sessions_count }}</span>
                                        </div>
                                        <span style="background: #f1f5f9; padding: 1px 6px; border-radius: 4px; font-size: 10px; font-weight: 600; color: #334155; max-width: 100px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            {{ $table->primary_guest_name ?? 'Guest' }}
                                        </span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; font-size: 10px; color: #64748b;">
                                        <span>{{ $table->live_orders_count ?? 0 }} Items</span>
                                        <span style="color: #059669; font-weight: 700;">Serving</span>
                                    </div>
                                </div>
                            </div>
                            <div style="margin-top: 8px; padding-top: 6px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
                                <div>
                                    <span style="font-size: 9px; font-weight: 700; text-transform: uppercase; color: #94a3b8; display: block;">Total Due</span>
                                    <span style="font-size: 15px; font-weight: 800; font-family: monospace; color: #ea580c;">₹{{ number_format($table->live_due ?? 0, 2) }}</span>
                                </div>
                                <div>
                                    <button wire:click.stop="openTable({{ $table->id }})" style="background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; border-radius: 8px; padding: 5px 12px; font-size: 11px; font-weight: 700; cursor: pointer;">
                                        Manage
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
                                <div style="margin: 8px 0; text-align: center;">
                                    <div style="width: 28px; height: 28px; border-radius: 50%; background: #e0e7ff; color: #4f46e5; display: flex; align-items: center; justify-content: center; margin: 0 auto 4px auto;">
                                        <x-heroicon-s-calendar style="width: 16px; height: 16px;" />
                                    </div>
                                    <p style="font-size: 12px; font-weight: 700; color: #1e293b; margin: 0;">Reserved Table</p>
                                </div>
                            </div>
                            <div style="padding-top: 8px; border-top: 1px solid #f1f5f9;">
                                <button wire:click.stop="mountAction('assignGuestAction', { table_id: {{ $table->id }} })" style="width: 100%; padding: 6px; border-radius: 8px; background: #eef2ff; border: 1px solid #c7d2fe; color: #4338ca; font-size: 11px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px;">
                                    <x-heroicon-s-user-plus style="width: 14px; height: 14px;" />
                                    <span>Seat Guest</span>
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
                                <div style="margin: 8px 0; text-align: center;">
                                    <div style="width: 28px; height: 28px; border-radius: 50%; background: #d1fae5; color: #059669; display: flex; align-items: center; justify-content: center; margin: 0 auto 4px auto;">
                                        <x-heroicon-s-check style="width: 16px; height: 16px;" />
                                    </div>
                                    <p style="font-size: 12px; font-weight: 700; color: #1e293b; margin: 0;">Ready for Seating</p>
                                    <p style="font-size: 10px; color: #94a3b8; margin: 1px 0 0 0;">QR Online</p>
                                </div>
                            </div>
                            <div style="padding-top: 8px; border-top: 1px solid #f1f5f9;">
                                <button wire:click.stop="mountAction('assignGuestAction', { table_id: {{ $table->id }} })" style="width: 100%; padding: 6px; border-radius: 8px; background: transparent; border: 1px solid #10b981; color: #047857; font-size: 11px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px;">
                                    <x-heroicon-s-plus style="width: 14px; height: 14px;" />
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
            @php
                $hasActiveGuests = $activeDinersList->count() > 0;
                $primaryDinerTime = $activeDinersList->first()?->created_at?->diffForHumans(null, true);
            @endphp
            <div class="stitch-modal-overlay" wire:click.self="closeReceiptModal">
                <section class="stitch-modal-box {{ (!$hasActiveGuests && !$selectedRoomId) ? 'stitch-modal-compact' : '' }}" x-data="{ mobileTab: '{{ $selectedSessionId ? 'orders' : ($hasActiveGuests ? 'guests' : 'billing') }}' }">
                    
                    {{-- MODAL HEADER --}}
                    <header class="stitch-modal-header">
                        <div style="display: flex; align-items: center; gap: 14px;">
                            <div style="width: 44px; height: 44px; background: #eef2ff; color: #4f46e5; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
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
                                    <span style="display: inline-flex; align-items: center; gap: 6px; padding: 2px 10px; border-radius: 9999px; font-size: 12px; font-weight: 600; {{ $hasActiveGuests ? 'background: #d1fae5; color: #065f46;' : 'background: #f1f5f9; color: #475569;' }}">
                                        <span class="stitch-dot animate-pulse-dot" style="background: {{ $hasActiveGuests ? '#10b981' : '#94a3b8' }};"></span>
                                        {{ $hasActiveGuests ? 'Occupied (' . ($primaryDinerTime ?? 'Active') . ')' : 'Available' }}
                                    </span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px; margin-top: 2px;">
                                    <h1 style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 0; line-height: 1.2;">
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
                                        <span style="color: #94a3b8; font-size: 12px; font-weight: 500;">• {{ $selectedEntityData->seating_capacity ?? 4 }} Seater • Floor Area</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Close & Refresh --}}
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <button wire:click="$refresh" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 7px; color: #64748b; cursor: pointer;" title="Refresh Orders">
                                <x-heroicon-s-arrow-path style="width: 18px; height: 18px;" />
                            </button>
                            <button wire:click="closeReceiptModal" style="width: 36px; height: 36px; border-radius: 50%; background: #f1f5f9; border: none; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                <x-heroicon-s-x-mark style="width: 20px; height: 20px;" />
                            </button>
                        </div>
                    </header>

                    @if(!$hasActiveGuests && !$selectedRoomId)
                        {{-- ======================================================== --}}
                        {{-- 🌟 STREAMLINED QUICK TABLE CONSOLE (Empty Table View)    --}}
                        {{-- Eliminates cavernous empty columns and gives 1-click execution --}}
                        {{-- ======================================================== --}}
                        <div style="flex: 1; display: flex; flex-direction: column; justify-content: center; padding: 24px 20px; overflow-y: auto;">
                            <div style="text-align: center;">
                                <div style="width: 52px; height: 52px; border-radius: 50%; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px auto;">
                                    <x-heroicon-s-check-circle style="width: 30px; height: 30px;" />
                                </div>
                                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 3px 12px; border-radius: 9999px; font-size: 11px; font-weight: 700; background: #d1fae5; color: #065f46; margin-bottom: 8px;">
                                    <span class="stitch-dot" style="background: #10b981;"></span> Ready for Guests
                                </span>
                                <h2 style="font-size: 19px; font-weight: 800; color: #0f172a; margin: 0 0 6px 0;">
                                    @if($selectedParcelCounterId)
                                        Parcel Counter Ready
                                    @else
                                        Table-{{ $finalModalNum }} is Available
                                    @endif
                                </h2>
                                <p style="font-size: 12px; color: #64748b; margin: 0 0 20px 0;">Seat a guest to start order tracking, or place a direct instant order.</p>

                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 20px;">
                                    @if($selectedTableId)
                                        <button wire:click="mountAction('assignGuestAction', { table_id: {{ $selectedTableId }} })" 
                                                style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 16px 12px; background: linear-gradient(135deg, #1e3a8a, #2563eb); color: white; border: none; border-radius: 14px; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);">
                                            <x-heroicon-s-user-plus style="width: 22px; height: 22px; margin-bottom: 6px;" />
                                            <span style="font-size: 13px; font-weight: 800;">Seat & Assign</span>
                                            <span style="font-size: 11px; opacity: 0.85; margin-top: 2px;">Enter guest name</span>
                                        </button>

                                        <button wire:click="openPlaceOrder({{ $selectedTableId }})" 
                                                style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 16px 12px; background: linear-gradient(135deg, #ea580c, #f97316); color: white; border: none; border-radius: 14px; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 12px rgba(249, 115, 22, 0.2);">
                                            <x-heroicon-s-bolt style="width: 22px; height: 22px; margin-bottom: 6px;" />
                                            <span style="font-size: 13px; font-weight: 800;">Direct Order</span>
                                            <span style="font-size: 11px; opacity: 0.85; margin-top: 2px;">Instant bill / POS</span>
                                        </button>
                                    @elseif($selectedParcelCounterId)
                                        <button wire:click="mountAction('assignGuestAction', { counter_id: {{ $selectedParcelCounterId }} })" 
                                                style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 16px 12px; background: linear-gradient(135deg, #1e3a8a, #2563eb); color: white; border: none; border-radius: 14px; cursor: pointer; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);">
                                            <x-heroicon-s-user-plus style="width: 22px; height: 22px; margin-bottom: 6px;" />
                                            <span style="font-size: 13px; font-weight: 800;">Add Takeaway</span>
                                            <span style="font-size: 11px; opacity: 0.85; margin-top: 2px;">Name & phone</span>
                                        </button>
                                        <button wire:click="openPlaceOrder(null, {{ $selectedParcelCounterId }})" 
                                                style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 16px 12px; background: linear-gradient(135deg, #ea580c, #f97316); color: white; border: none; border-radius: 14px; cursor: pointer; box-shadow: 0 4px 12px rgba(249, 115, 22, 0.2);">
                                            <x-heroicon-s-bolt style="width: 22px; height: 22px; margin-bottom: 6px;" />
                                            <span style="font-size: 13px; font-weight: 800;">Direct Order</span>
                                            <span style="font-size: 11px; opacity: 0.85; margin-top: 2px;">Instant bill / POS</span>
                                        </button>
                                    @endif
                                </div>

                                @if($selectedTableId)
                                    <div style="padding-top: 14px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: center; gap: 8px; flex-wrap: wrap;">
                                        <span style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Status:</span>
                                        <button wire:click.stop="changeTableStatus({{ $selectedTableId }}, 'available')" style="padding: 4px 10px; border-radius: 8px; border: 1px solid #10b981; background: #ecfdf5; color: #065f46; font-size: 11px; font-weight: 700; cursor: pointer;">🟢 Available</button>
                                        <button wire:click.stop="changeTableStatus({{ $selectedTableId }}, 'cleaning')" style="padding: 4px 10px; border-radius: 8px; border: 1px solid #f59e0b; background: #fffbeb; color: #b45309; font-size: 11px; font-weight: 700; cursor: pointer;">🧹 Cleaning</button>
                                        <button wire:click.stop="changeTableStatus({{ $selectedTableId }}, 'occupied')" style="padding: 4px 10px; border-radius: 8px; border: 1px solid #f43f5e; background: #fff1f2; color: #be123c; font-size: 11px; font-weight: 700; cursor: pointer;">📌 Occupied</button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @else
                        {{-- MOBILE / TABLET SEGMENTED TAB BAR (Hidden on desktop > 1024px) --}}
                        <nav class="stitch-mobile-tab-bar">
                            <button @click="mobileTab = 'guests'" :class="mobileTab === 'guests' ? 'active' : ''" class="stitch-mobile-tab-btn" type="button">
                                👥 Diners ({{ $activeDinersList->count() }})
                            </button>
                            <button @click="mobileTab = 'orders'" :class="mobileTab === 'orders' ? 'active' : ''" class="stitch-mobile-tab-btn" type="button">
                                🍽️ Orders ({{ $tableOrders->count() }})
                            </button>
                            <button @click="mobileTab = 'billing'" :class="mobileTab === 'billing' ? 'active' : ''" class="stitch-mobile-tab-btn" type="button">
                                💳 Settle Bill
                            </button>
                        </nav>

                        {{-- THREE COLUMN GRID BODY --}}
                        <div class="stitch-modal-body">
                        
                        {{-- ======================================================== --}}
                        {{-- COLUMN 1: GUESTS & SPLITS (Width: 320px)                 --}}
                        {{-- ======================================================== --}}
                        <aside class="stitch-col-guests stitch-scroll" :class="mobileTab === 'guests' ? 'show-mobile' : ''">
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
                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Diners & Splits</span>
                                            <span style="background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700;">{{ $activeDinersList->count() }}</span>
                                        </div>
                                        @if($selectedTableId)
                                            <button wire:click="mountAction('assignGuestAction', { table_id: {{ $selectedTableId }} })" style="background: #1e3a8a; color: white; border: none; border-radius: 8px; padding: 5px 10px; font-size: 11px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                                <x-heroicon-s-user-plus style="width: 12px; height: 12px;" />
                                                <span>+ Assign</span>
                                            </button>
                                        @elseif($selectedParcelCounterId)
                                            <button wire:click="mountAction('assignGuestAction', { counter_id: {{ $selectedParcelCounterId }} })" style="background: #ea580c; color: white; border: none; border-radius: 8px; padding: 5px 10px; font-size: 11px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                                <x-heroicon-s-plus style="width: 12px; height: 12px;" />
                                                <span>+ Add</span>
                                            </button>
                                        @endif
                                    </div>

                                    {{-- Guest Cards List --}}
                                    @if($activeDinersList->count() === 0)
                                        <div style="text-align: center; padding: 20px 14px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; margin-top: 8px;">
                                            <p style="font-size: 12px; font-weight: 700; color: #1e293b; margin: 0 0 4px 0;">No seated diners</p>
                                            <p style="font-size: 11px; color: #64748b; margin: 0 0 10px 0;">Assign a guest to begin.</p>
                                            @if($selectedTableId)
                                                <button wire:click="mountAction('assignGuestAction', { table_id: {{ $selectedTableId }} })" style="width: 100%; padding: 8px; background: #f97316; color: white; border: none; border-radius: 8px; font-weight: 700; font-size: 11px; cursor: pointer;">
                                                    👤 Assign Guest
                                                </button>
                                            @elseif($selectedParcelCounterId)
                                                <button wire:click="mountAction('assignGuestAction', { counter_id: {{ $selectedParcelCounterId }} })" style="width: 100%; padding: 8px; background: #f97316; color: white; border: none; border-radius: 8px; font-weight: 700; font-size: 11px; cursor: pointer;">
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
                                                <div style="background: #ffffff; border: 1px solid {{ $party['is_any_member_selected'] ? '#bfdbfe' : '#e2e8f0' }}; border-radius: 14px; padding: 10px; margin-bottom: 10px; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                                                    {{-- PARTY HEADER BADGE --}}
                                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; padding-bottom: 6px; border-bottom: 1px solid #f1f5f9;">
                                                        <div style="display: flex; align-items: center; gap: 5px;">
                                                            <span style="font-size: 9px; font-weight: 800; background: #1e3a8a; color: white; padding: 2px 6px; border-radius: 4px; letter-spacing: 0.5px;">
                                                                PARTY #{{ $party['party_number'] }}
                                                            </span>
                                                            <span style="font-size: 11px; font-weight: 700; color: #64748b;">
                                                                {{ $party['total_diners_count'] }} {{ \Illuminate\Support\Str::plural('Diner', $party['total_diners_count']) }}
                                                            </span>
                                                        </div>
                                                        <div style="text-align: right;">
                                                            <span style="font-size: 12px; font-weight: 900; font-family: monospace; color: #0f172a;">₹{{ number_format($party['party_subtotal'], 2) }}</span>
                                                        </div>
                                                    </div>

                                                    {{-- PRIMARY HOST CARD --}}
                                                    <div wire:click="selectCustomerSession({{ $host->id }})" 
                                                         @click="if (window.innerWidth <= 1024) mobileTab = 'orders'"
                                                         class="stitch-guest-card {{ $isHostSelected ? 'active' : '' }}" 
                                                         style="margin-bottom: {{ $party['guests']->isNotEmpty() ? '6px' : '0' }}; border-color: {{ $isHostSelected ? '#2563eb' : '#fed7aa' }}; background: {{ $isHostSelected ? '#eff6ff' : '#fffaf5' }}; padding: 8px 10px;">
                                                        <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                                <div class="stitch-guest-avatar {{ $isHostSelected ? 'active' : '' }}" style="width: 28px; height: 28px; background: {{ $isHostSelected ? '#2563eb' : '#ffedd5' }}; color: {{ $isHostSelected ? 'white' : '#ea580c' }};">
                                                                    <x-heroicon-s-user style="width: 15px; height: 15px;" />
                                                                </div>
                                                                <div>
                                                                    <div style="display: flex; align-items: center; gap: 4px; flex-wrap: wrap;">
                                                                        <span style="font-size: 13px; font-weight: 800; color: #0f172a;">{{ $host->customer_name ?? 'Host' }}</span>
                                                                        <span style="font-size: 9px; font-weight: 800; background: #ea580c; color: white; padding: 1px 5px; border-radius: 4px;">
                                                                            👑 HOST
                                                                        </span>
                                                                    </div>
                                                                    <span style="font-size: 10px; color: #64748b;">{{ $host->created_at->diffForHumans(null, true) }} ago</span>
                                                                </div>
                                                            </div>
                                                            <div style="display: flex; align-items: center; gap: 3px;">
                                                                <button wire:click.stop="mountAction('editCustomerNameAction', { session_id: {{ $host->id }} })" class="stitch-icon-btn" title="Rename Host">
                                                                    <x-heroicon-m-pencil-square style="width: 13px; height: 13px;" />
                                                                </button>
                                                                <button wire:click.stop="cleanDineInSession({{ $host->id }}, true)" 
                                                                        onclick="confirm('Vacate Party #{{ $party['party_number'] }} (Host {{ $host->customer_name }})?') || event.stopImmediatePropagation()" 
                                                                        class="stitch-icon-btn" 
                                                                        style="color: #e11d48;" 
                                                                        title="Vacate Party">
                                                                    <x-heroicon-m-arrow-right-on-rectangle style="width: 13px; height: 13px;" />
                                                                </button>
                                                            </div>
                                                        </div>
                                                        <div style="margin-top: 6px; padding-top: 6px; border-top: 1px solid rgba(226, 232, 240, 0.8); display: flex; align-items: center; justify-content: space-between; font-size: 11px;">
                                                            <span style="color: #64748b;">{{ $party['host_orders_count'] }} orders • {{ $party['host_items_count'] }} items</span>
                                                            <span style="font-weight: 800; font-family: monospace; font-size: 12px; color: #0f172a;">₹{{ number_format($party['host_subtotal'], 2) }}</span>
                                                        </div>
                                                    </div>

                                                    {{-- JOINED GUESTS (NESTED UNDER HOST) --}}
                                                    @if($party['guests']->isNotEmpty())
                                                        <div style="margin-left: 6px; padding-left: 8px; border-left: 2px dashed #cbd5e1; margin-top: 6px; display: flex; flex-direction: column; gap: 6px;">
                                                            @foreach($party['guests'] as $guest)
                                                                @php
                                                                    $isGuestSelected = ($selectedSessionId === $guest->id);
                                                                @endphp
                                                                <div wire:click="selectCustomerSession({{ $guest->id }})" 
                                                                     @click="if (window.innerWidth <= 1024) mobileTab = 'orders'"
                                                                     class="stitch-guest-card {{ $isGuestSelected ? 'active' : '' }}" 
                                                                     style="margin-bottom: 0; padding: 6px 8px; background: {{ $isGuestSelected ? '#eff6ff' : '#f8fafc' }};">
                                                                    <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                                                                        <div style="display: flex; align-items: center; gap: 6px;">
                                                                            <div class="stitch-guest-avatar {{ $isGuestSelected ? 'active' : '' }}" style="width: 24px; height: 24px;">
                                                                                <x-heroicon-s-user style="width: 13px; height: 13px;" />
                                                                            </div>
                                                                            <div>
                                                                                <div style="display: flex; align-items: center; gap: 4px;">
                                                                                    <span style="font-size: 12px; font-weight: 700; color: #1e293b;">{{ $guest->customer_name ?? 'Guest' }}</span>
                                                                                    <span style="font-size: 9px; font-weight: 700; background: #e0e7ff; color: #3730a3; padding: 1px 4px; border-radius: 3px;">
                                                                                        👥 Joined
                                                                                    </span>
                                                                                </div>
                                                                                <div style="font-size: 10px; color: #94a3b8;">{{ $guest->created_at->diffForHumans(null, true) }} ago</div>
                                                                            </div>
                                                                        </div>
                                                                        <div style="display: flex; align-items: center; gap: 2px;">
                                                                            <button wire:click.stop="mountAction('editCustomerNameAction', { session_id: {{ $guest->id }} })" class="stitch-icon-btn" title="Edit Guest Name">
                                                                                <x-heroicon-m-pencil-square style="width: 12px; height: 12px;" />
                                                                            </button>
                                                                            <button wire:click.stop="cleanDineInSession({{ $guest->id }}, false)" 
                                                                                    onclick="confirm('Vacate guest {{ $guest->customer_name }}?') || event.stopImmediatePropagation()" 
                                                                                    class="stitch-icon-btn" 
                                                                                    style="color: #e11d48;" 
                                                                                    title="Vacate Guest">
                                                                                <x-heroicon-m-x-mark style="width: 12px; height: 12px;" />
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                    <div style="margin-top: 4px; padding-top: 4px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; font-size: 10px;">
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

                                                <div wire:click="selectCustomerSession({{ $diner->id }})" 
                                                     @click="if (window.innerWidth <= 1024) mobileTab = 'orders'"
                                                     class="stitch-guest-card {{ $isActiveDiner ? 'active' : '' }}" 
                                                     style="padding: 10px 12px; margin-bottom: 8px;">
                                                    <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                                                        <div style="display: flex; align-items: center; gap: 8px;">
                                                            <div class="stitch-guest-avatar {{ $isActiveDiner ? 'active' : '' }}" style="width: 28px; height: 28px;">
                                                                <x-heroicon-s-user style="width: 15px; height: 15px;" />
                                                            </div>
                                                            <div>
                                                                <span style="font-size: 13px; font-weight: 800; color: #0f172a;">{{ $diner->customer_name ?? 'Guest' }}</span>
                                                                <span style="font-size: 10px; color: #64748b; display: block;">Queued {{ $diner->created_at->diffForHumans(null, true) }} ago</span>
                                                            </div>
                                                        </div>
                                                        <button wire:click.stop="mountAction('editCustomerNameAction', { session_id: {{ $diner->id }}, is_parcel: true })" class="stitch-icon-btn" title="Edit Customer">
                                                            <x-heroicon-m-pencil-square style="width: 13px; height: 13px;" />
                                                        </button>
                                                    </div>
                                                    <div style="margin-top: 8px; padding-top: 6px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; font-size: 11px;">
                                                        <span style="color: #64748b;">{{ $dinerOrders->count() }} orders • {{ $dinerItemsCount }} items</span>
                                                        <span style="font-weight: 800; font-family: monospace; font-size: 13px; color: #0f172a;">₹{{ number_format($dinerSubtotal, 2) }}</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif
                                    @endif
                                @endif
                            </div>

                            {{-- Bottom Split Helper Info --}}
                            <div style="padding: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; margin-top: 10px; display: flex; align-items: center; justify-content: space-between; font-size: 11px;">
                                <span style="color: #64748b; font-weight: 600;">Split Strategy:</span>
                                <span style="font-weight: 800; color: #1e3a8a;">Itemized Per Seat</span>
                            </div>
                        </aside>

                        {{-- ======================================================== --}}
                        {{-- COLUMN 2: ORDERS STREAM & LIVE KITCHEN (Center Column)   --}}
                        {{-- ======================================================== --}}
                        <main class="stitch-col-orders stitch-scroll" :class="mobileTab === 'orders' ? 'show-mobile' : ''">
                            @if(!$selectedSessionId)
                                <div style="height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; color: #94a3b8; padding: 24px;">
                                    <x-heroicon-o-user-plus style="width: 42px; height: 42px; color: #cbd5e1; margin-bottom: 10px;" />
                                    <h4 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0 0 4px 0;">Select a Diner</h4>
                                    <p style="font-size: 12px; color: #64748b; margin: 0 0 14px 0; max-width: 300px;">Choose a guest from Diners or seat a new guest to place orders.</p>
                                    @if($selectedTableId)
                                        <div style="display: flex; gap: 8px; flex-wrap: wrap; justify-content: center;">
                                            <button wire:click="mountAction('assignGuestAction', { table_id: {{ $selectedTableId }} })" 
                                                    @click="if (window.innerWidth <= 1024) mobileTab = 'guests'"
                                                    style="padding: 8px 14px; background: #0f172a; color: white; border: none; border-radius: 10px; font-weight: 700; font-size: 11px; cursor: pointer;">
                                                👤 Assign Guest
                                            </button>
                                            <button wire:click="openPlaceOrder" style="padding: 8px 14px; background: #f97316; color: white; border: none; border-radius: 10px; font-weight: 700; font-size: 11px; cursor: pointer;">
                                                🛒 Place Order
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
                                                        <div style="display: flex; align-items: center; gap: 8px;">
                                                            <div style="display: flex; align-items: center; gap: 4px;">
                                                                <span style="font-size: 11px; font-weight: 700; color: #64748b;">Status:</span>
                                                                <select wire:change="updateStatus({{ $order->id }}, $event.target.value)" wire:loading.attr="disabled"
                                                                    style="font-size: 11px; font-weight: 800; border-radius: 6px; padding: 2px 6px; border: 1px solid #cbd5e1; background: #ffffff; color: #1e293b; cursor: pointer;">
                                                                    <option value="placed" {{ $order->status === 'placed' ? 'selected' : '' }}>Placed</option>
                                                                    <option value="accepted" {{ in_array($order->status, ['accepted', 'partial_accepted']) ? 'selected' : '' }}>Accepted</option>
                                                                    <option value="preparing" {{ $order->status === 'preparing' ? 'selected' : '' }}>Preparing</option>
                                                                    <option value="ready" {{ $order->status === 'ready' ? 'selected' : '' }}>Ready</option>
                                                                    <option value="served" {{ $order->status === 'served' ? 'selected' : '' }}>Served</option>
                                                                    <option value="rejected" {{ in_array($order->status, ['rejected', 'cancelled']) ? 'selected' : '' }}>Cancel / Reject</option>
                                                                </select>
                                                            </div>
                                                            @if(!$isCancelled && !$pendingPayment && !$isPaid)
                                                                <button wire:click="mountAction('editOrderAction', { orderId: {{ $order->id }} })" style="background: white; border: 1px solid #cbd5e1; border-radius: 6px; padding: 2px 8px; font-size: 11px; font-weight: 600; color: #475569; cursor: pointer;">
                                                                    Edit
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </header>
                                                    @if(!empty($order->notes))
                                                        <div style="margin: 0 16px 8px 16px; padding: 4px 8px; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 6px; font-size: 11px; font-weight: 600; color: #b45309;">
                                                            📝 Order Note: {{ $order->notes }}
                                                        </div>
                                                    @endif
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
                                                                        <div style="display: flex; align-items: center; gap: 6px;">
                                                                            <span style="font-weight: 600; color: #1e293b; {{ $isOos ? 'text-decoration: line-through; color: #94a3b8;' : '' }}">{{ $item->item_name }}</span>
                                                                        </div>
                                                                        @if(!empty($item->notes))
                                                                            <p style="font-size: 11px; color: #d97706; margin: 2px 0 0 0;">Note: {{ $item->notes }}</p>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                                    @if(!$isOos)
                                                                        @php
                                                                            $iStatus = $item->status ?? 'placed';
                                                                            $iColor = match($iStatus) {
                                                                                'ready' => '#10b981',
                                                                                'preparing' => '#f97316',
                                                                                'served' => '#2563eb',
                                                                                default => '#64748b'
                                                                            };
                                                                            $iBg = match($iStatus) {
                                                                                'ready' => '#dcfce7',
                                                                                'preparing' => '#ffedd5',
                                                                                'served' => '#dbeafe',
                                                                                default => '#f1f5f9'
                                                                            };
                                                                        @endphp
                                                                        <select wire:change="updateItemStatus({{ $item->id }}, $event.target.value)" wire:loading.attr="disabled"
                                                                            style="font-size: 10px; font-weight: 800; padding: 2px 6px; border-radius: 6px; border: 1px solid {{ $iColor }}50; background: {{ $iBg }}; color: {{ $iColor }}; cursor: pointer; text-transform: uppercase;">
                                                                            <option value="placed" {{ $iStatus === 'placed' ? 'selected' : '' }}>Waiting</option>
                                                                            <option value="preparing" {{ $iStatus === 'preparing' ? 'selected' : '' }}>Cooking</option>
                                                                            <option value="ready" {{ $iStatus === 'ready' ? 'selected' : '' }}>Ready</option>
                                                                            <option value="served" {{ $iStatus === 'served' ? 'selected' : '' }}>Served</option>
                                                                        </select>
                                                                    @endif
                                                                    <span style="font-weight: 800; font-family: monospace; color: #0f172a; {{ $isOos ? 'text-decoration: line-through; color: #94a3b8;' : '' }}">₹{{ number_format($item->unit_price * ($isOos ? $item->quantity : $displayQty), 0) }}</span>
                                                                </div>
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
                        <aside class="stitch-col-billing stitch-scroll" :class="mobileTab === 'billing' ? 'show-mobile' : ''">
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
                                    $totalItemsCount = $validOrders->sum(fn($o) => $o->items->sum(fn($i) => (int) ($i->confirmed_qty ?? $i->quantity)));
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
                                            <span style="font-weight: 500;">Orders Subtotal ({{ $totalItemsCount }} Qty)</span>
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

                                {{-- Unified Print Controls (Dine-In, Parcel, Room) --}}
                                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 8px 10px; margin-top: 10px;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; font-size: 11px;">
                                        <label style="display: flex; align-items: center; gap: 5px; cursor: pointer; color: #15803d; font-weight: 700; user-select: none;">
                                            <input wire:model.live="billIncludeQr" type="checkbox" style="border-radius: 4px; color: #15803d;">
                                            <span>Dynamic UPI QR</span>
                                        </label>
                                        <div style="display: flex; align-items: center; gap: 4px;">
                                            <span style="color: #64748b; font-size: 10px; font-weight: 700;">Format:</span>
                                            <select wire:model.live="billPaperSize" style="padding: 2px 6px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 11px; font-weight: 700; background: #ffffff; color: #0f172a; outline: none; cursor: pointer;">
                                                <option value="80mm">80mm POS</option>
                                                <option value="58mm">58mm Roll</option>
                                                <option value="a4">A4 Normal</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                {{-- Checkout & Settlement Actions --}}
                                <div style="margin-top: 10px; display: flex; flex-direction: column; gap: 8px;">
                                    @if($selectedRoomId)
                                        <button wire:click="settleRoomBill" style="width: 100%; padding: 12px 16px; background: #10b981; color: white; border: none; border-radius: 12px; font-weight: 700; font-size: 13px; cursor: pointer;">
                                            Settle Food Bill to Room
                                        </button>
                                    @else
                                        @if(!$pendingPayment && $subtotal > 0)
                                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                                <button wire:click="printPendingBill" style="padding: 10px 8px; background: #f1f5f9; border: 1.5px solid #cbd5e1; border-radius: 12px; font-weight: 700; font-size: 12px; color: #0f172a; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;">
                                                    <x-heroicon-s-printer style="width: 15px; height: 15px; color: #059669;" />
                                                    <span>Print Bill</span>
                                                </button>
                                                <button wire:click="sendBillToCustomer" style="padding: 10px 8px; background: #1e3a8a; color: white; border: none; border-radius: 12px; font-weight: 700; font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 2px 4px rgba(30, 58, 138, 0.25);">
                                                    <x-heroicon-s-paper-airplane style="width: 15px; height: 15px;" />
                                                    <span>Send to Guest</span>
                                                </button>
                                            </div>
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
                                    <x-heroicon-o-currency-rupee style="width: 40px; height: 40px; color: #cbd5e1; margin-bottom: 8px;" />
                                    <p style="font-weight: 700; color: #334155; font-size: 13px; margin: 0;">No Diner Selected</p>
                                    <p style="font-size: 11px; color: #94a3b8; margin: 4px 0 0 0;">Select a diner to view bills and settlement.</p>
                                </div>
                            @endif
                        </aside>
                    </div>
                    @endif

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
            @php
                $kotSubtotal = collect($placeOrderCart)->sum(fn($i) => $i['unit_price'] * $i['quantity']);
                $kotTotal = $kotSubtotal;
                $kotTotalItems = count($placeOrderCart);
                $kotTotalQty = collect($placeOrderCart)->sum('quantity');

                if ($isInstantBillMode) {
                    $iSubtotal = (float) $kotSubtotal;
                    $iDisc = max(0, (float) ($instantBillDiscount ?? 0));
                    $iTaxable = max(0, $iSubtotal - $iDisc);
                    $iTaxRate = max(0, (float) ($instantBillTaxPercent ?? 0));
                    $iTax = round($iTaxable * ($iTaxRate / 100), 2);
                    $iExtra = max(0, (float) ($instantBillExtraCharges ?? 0));
                    $iGrandTotal = round($iTaxable + $iTax + $iExtra, 2);
                }

                $destLabel = 'Dine In';
                if ($placeOrderServiceType === 'parcel' || $placeOrderCounterId) {
                    $cRecord = $placeOrderCounterId ? \App\Models\ParcelQrCode::find($placeOrderCounterId) : null;
                    $destLabel = $cRecord ? '🛍️ ' . $cRecord->name : '🛍️ Takeaway / Parcel';
                } elseif ($placeOrderTableId) {
                    $tRecord = \App\Models\RestaurantTable::find($placeOrderTableId);
                    $destLabel = $tRecord ? $this->formatTableNumber($tRecord->table_number) : 'Table';
                } elseif ($placeOrderRoomId) {
                    $rRecord = \App\Models\Room::find($placeOrderRoomId);
                    $destLabel = $rRecord ? 'Room ' . $rRecord->room_number : 'Room Service';
                }
            @endphp

            <div class="stitch-order-overlay" wire:click.self="closePlaceOrder" @keydown.escape.window="$wire.closePlaceOrder()" x-data="{
                mobileKotTab: 'menu'
            }">
                <main class="stitch-order-modal" role="dialog" aria-modal="true" aria-labelledby="order-modal-title">
                    
                    {{-- ── 1. MODAL HEADER (Sleek, Unbroken Toggle & Clean Layout) ── --}}
                    <header class="stitch-order-header">
                        <div style="display: flex; align-items: center; gap: 6px; min-width: 0; flex-shrink: 0;">
                            <div style="width: 28px; height: 28px; border-radius: 8px; background: {{ $isInstantBillMode ? '#f0fdf4' : '#fff7ed' }}; border: 1px solid {{ $isInstantBillMode ? '#bbf7d0' : '#fed7aa' }}; display: flex; align-items: center; justify-content: center; color: {{ $isInstantBillMode ? '#15803d' : '#a33900' }}; flex-shrink: 0;">
                                @if($isInstantBillMode)
                                    <x-heroicon-s-bolt style="width: 15px; height: 15px;" />
                                @else
                                    <x-heroicon-s-shopping-bag style="width: 15px; height: 15px;" />
                                @endif
                            </div>
                            <span style="font-size: 11px; font-weight: 800; padding: 2px 7px; border-radius: 6px; background: {{ $isInstantBillMode ? '#dcfce7' : '#ffedd5' }}; color: {{ $isInstantBillMode ? '#15803d' : '#a33900' }}; border: 1px solid {{ $isInstantBillMode ? '#86efac' : '#fed7aa' }}; white-space: nowrap;">
                                {{ $destLabel }}
                            </span>
                            <span class="hidden lg:inline" style="font-size: 13px; font-weight: 800; color: #0f172a; white-space: nowrap;">
                                {{ $isInstantBillMode ? 'Instant Billing & Tax Invoice' : 'Place KOT Order' }}
                            </span>
                        </div>

                        {{-- Mobile View Mode Switcher (Sleek Segmented Pill Control) --}}
                        <div class="lg:hidden" style="display: inline-flex; background: #e2e8f0; padding: 2px; border-radius: 8px; border: 1px solid #cbd5e1; flex-shrink: 0;">
                            <button type="button" @click="mobileKotTab = 'menu'" :style="mobileKotTab === 'menu' ? 'background: #ffffff; color: {{ $isInstantBillMode ? '#15803d' : '#9a3412' }}; font-weight: 800; box-shadow: 0 1px 3px rgba(0,0,0,0.12);' : 'background: transparent; color: #64748b; font-weight: 700;'" style="padding: 5px 10px; border-radius: 6px; border: none; font-size: 11px; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; white-space: nowrap; line-height: 1; transition: all 0.15s ease;">
                                <span>🍽️ Dishes</span>
                            </button>
                            <button type="button" @click="mobileKotTab = 'cart'" :style="mobileKotTab === 'cart' ? 'background: #ffffff; color: {{ $isInstantBillMode ? '#15803d' : '#9a3412' }}; font-weight: 800; box-shadow: 0 1px 3px rgba(0,0,0,0.12);' : 'background: transparent; color: #64748b; font-weight: 700;'" style="padding: 5px 10px; border-radius: 6px; border: none; font-size: 11px; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; white-space: nowrap; line-height: 1; transition: all 0.15s ease;">
                                <span>🛒 Cart</span>
                                @if($kotTotalItems > 0)
                                    <span style="background: {{ $isInstantBillMode ? '#15803d' : '#ea580c' }}; color: #ffffff; font-size: 10px; padding: 1px 5px; border-radius: 9999px; font-weight: 800; line-height: 1.2;">
                                        {{ $kotTotalItems }}
                                    </span>
                                @endif
                            </button>
                        </div>

                        {{-- Desktop Metadata Pills & Close Button --}}
                        <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                            <div class="hidden lg:flex" style="align-items: center; gap: 6px; padding: 5px 10px; border-radius: 8px; background: #f1f5f9; font-size: 11px; font-weight: 600; color: #475569; border: 1px solid #e2e8f0;">
                                <x-heroicon-m-user style="width: 13px; height: 13px; color: #64748b;" />
                                <span>Captain: <strong style="color: #0f172a;">{{ auth()->user()->name }}</strong></span>
                            </div>

                            <button wire:click="closePlaceOrder" style="width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; border: 1px solid #cbd5e1; background: #ffffff; color: #64748b; cursor: pointer;" title="Close (ESC)">
                                <x-heroicon-m-x-mark style="width: 18px; height: 18px;" />
                            </button>
                        </div>
                    </header>

                    {{-- ── 2. DESKTOP CONTEXT BAR (Service Type, Table, Customer Name, Phone, Payment) ── --}}
                    <div class="stitch-order-context-bar hidden lg:block">
                        <div class="stitch-order-context-grid">
                            {{-- Service Type & Table Assignment --}}
                            <div>
                                <label style="display: block; font-size: 10px; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; margin-bottom: 3px;">
                                    Service Type / Table *
                                </label>
                                <div style="display: flex; gap: 6px;">
                                    <select wire:model.live="placeOrderServiceType" style="flex: 1; min-width: 90px; box-sizing: border-box; padding: 6px 8px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; font-size: 11px; font-weight: 700; color: #0f172a; outline: none; cursor: pointer;">
                                        <option value="dine_in">🍽️ Dine In</option>
                                        <option value="parcel">🛍️ Takeaway</option>
                                    </select>
                                    @if($placeOrderServiceType === 'dine_in')
                                        <select wire:model.live="placeOrderTableId" style="flex: 1; box-sizing: border-box; padding: 6px 8px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; font-size: 11px; font-weight: 700; color: #0f172a; outline: none; cursor: pointer;">
                                            <option value="">Select Table...</option>
                                            @foreach(\App\Models\RestaurantTable::where('restaurant_id', auth()->user()->restaurant_id)->when(auth()->user()->branch_id, fn($q) => $q->where('branch_id', auth()->user()->branch_id))->orderBy('table_number')->get() as $t)
                                                <option value="{{ $t->id }}">Table {{ $t->table_number }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                </div>
                            </div>

                            {{-- Customer / Guest Name --}}
                            <div>
                                <label style="display: block; font-size: 10px; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; margin-bottom: 3px;">
                                    Customer Name *
                                </label>
                                <div style="position: relative;">
                                    <x-heroicon-m-user style="width: 14px; height: 14px; color: #94a3b8; position: absolute; left: 8px; top: 8px;" />
                                    <input wire:model.live.debounce.300ms="placeOrderCustomerName" type="text" placeholder="Guest name..." style="width: 100%; box-sizing: border-box; padding: 6px 8px 6px 28px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; font-size: 12px; font-weight: 700; color: #0f172a; outline: none;">
                                </div>
                            </div>

                            {{-- Customer Phone No --}}
                            <div>
                                <label style="display: block; font-size: 10px; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; margin-bottom: 3px;">
                                    Phone Number (Invoice)
                                </label>
                                <div style="position: relative;">
                                    <x-heroicon-m-phone style="width: 14px; height: 14px; color: #94a3b8; position: absolute; left: 8px; top: 8px;" />
                                    <input wire:model.live.debounce.300ms="placeOrderCustomerPhone" type="tel" placeholder="10-digit mobile..." style="width: 100%; box-sizing: border-box; padding: 6px 8px 6px 28px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; font-size: 12px; font-weight: 700; color: #0f172a; outline: none;">
                                </div>
                            </div>

                            {{-- Payment Method / Status --}}
                            <div>
                                @if($isInstantBillMode)
                                    <label style="display: block; font-size: 10px; font-weight: 800; text-transform: uppercase; color: #15803d; letter-spacing: 0.5px; margin-bottom: 3px;">
                                        ⚡ Payment Received As *
                                    </label>
                                    <select wire:model.live="placeOrderPaymentMethod" style="width: 100%; box-sizing: border-box; padding: 6px 8px; border-radius: 8px; border: 1.5px solid #16a34a; background: #f0fdf4; font-size: 11px; font-weight: 800; color: #14532d; outline: none; cursor: pointer;">
                                        <option value="cash">💵 Cash Counter</option>
                                        <option value="upi">⚡ UPI (GPay/PhonePe/Paytm)</option>
                                        <option value="card">💳 Card (POS EDC Swipe)</option>
                                    </select>
                                @else
                                    <label style="display: block; font-size: 10px; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; margin-bottom: 3px;">
                                        Payment Status *
                                    </label>
                                    <select wire:model.live="placeOrderPaymentStatus" style="width: 100%; box-sizing: border-box; padding: 6px 8px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; font-size: 11px; font-weight: 700; color: #0f172a; outline: none; cursor: pointer;">
                                        <option value="pending">⏳ Pay Later (At Checkout / Settle)</option>
                                        <option value="paid_upi">⚡ Paid - UPI (GPay/PhonePe)</option>
                                        <option value="paid_card">💳 Paid - Card (EDC Terminal)</option>
                                        <option value="paid_cash">💵 Paid - Cash at Counter</option>
                                    </select>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- ── 3. MAIN WORKSPACE ── --}}
                    <div class="stitch-order-workspace">
                        
                        {{-- LEFT VERTICAL CATEGORY RAIL --}}
                        <aside class="stitch-order-cat-rail stitch-scroll" x-show="window.innerWidth > 1024 || mobileKotTab === 'menu'" :style="(window.innerWidth <= 1024 && mobileKotTab !== 'menu') ? 'display: none !important;' : ''">
                            {{-- All Items Button --}}
                            <button wire:click="setOrderCategory('all')" class="stitch-cat-btn {{ $placeOrderCategory === 'all' ? 'active' : '' }}" type="button">
                                <div class="stitch-cat-avatar {{ $placeOrderCategory === 'all' ? 'active' : '' }}">
                                    <x-heroicon-s-squares-2x2 style="width: 15px; height: 15px;" />
                                </div>
                                <span class="stitch-cat-label {{ $placeOrderCategory === 'all' ? 'active' : '' }}">All</span>
                                <span class="stitch-cat-badge {{ $placeOrderCategory === 'all' ? 'active' : '' }}">
                                    {{ $totalMenuItemsCount ?? 0 }}
                                </span>
                            </button>

                            {{-- Categories from DB --}}
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

                        {{-- CENTER DISH CATALOG --}}
                        <section class="stitch-order-catalog stitch-scroll" x-show="window.innerWidth > 1024 || mobileKotTab === 'menu'" :style="(window.innerWidth <= 1024 && mobileKotTab !== 'menu') ? 'display: none !important;' : 'display: flex; flex-direction: column;'">
                            {{-- Catalog Subheader: Search & Veg Chips --}}
                            <div class="stitch-catalog-toolbar">
                                <div style="display: flex; align-items: center; gap: 6px; flex: 1;">
                                    <div style="position: relative; flex: 1;">
                                        <x-heroicon-m-magnifying-glass style="width: 14px; height: 14px; color: #94a3b8; position: absolute; left: 8px; top: 8px;" />
                                        <input wire:model.live.debounce.250ms="placeOrderSearch" type="text" placeholder="Search dish..." style="width: 100%; box-sizing: border-box; padding: 5px 8px 5px 28px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; font-size: 12px; color: #0f172a; outline: none;">
                                    </div>

                                    <div style="display: flex; align-items: center; gap: 2px; background: #ffffff; padding: 2px; border-radius: 8px; border: 1px solid #cbd5e1; flex-shrink: 0;">
                                        <button wire:click="setOrderVegFilter('all')" style="padding: 4px 7px; border-radius: 6px; font-size: 10px; font-weight: 700; border: none; cursor: pointer; background: {{ $placeOrderVegFilter === 'all' ? '#0f172a' : 'transparent' }}; color: {{ $placeOrderVegFilter === 'all' ? '#ffffff' : '#475569' }};">
                                            All
                                        </button>
                                        <button wire:click="setOrderVegFilter('veg')" style="padding: 4px 7px; border-radius: 6px; font-size: 10px; font-weight: 700; border: none; cursor: pointer; display: flex; align-items: center; gap: 3px; background: {{ $placeOrderVegFilter === 'veg' ? '#059669' : 'transparent' }}; color: {{ $placeOrderVegFilter === 'veg' ? '#ffffff' : '#059669' }};">
                                            <span class="stitch-veg-icon" style="width: 10px; height: 10px;"><span style="width: 4px; height: 4px;"></span></span>
                                            Veg
                                        </button>
                                        <button wire:click="setOrderVegFilter('non-veg')" style="padding: 4px 7px; border-radius: 6px; font-size: 10px; font-weight: 700; border: none; cursor: pointer; display: flex; align-items: center; gap: 3px; background: {{ $placeOrderVegFilter === 'non-veg' ? '#dc2626' : 'transparent' }}; color: {{ $placeOrderVegFilter === 'non-veg' ? '#ffffff' : '#dc2626' }};">
                                            <span class="stitch-nonveg-icon" style="width: 10px; height: 10px;"><span style="border-bottom-width: 4px;"></span></span>
                                            Non-Veg
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Dishes Stack / Grid --}}
                            <div style="display: flex; flex-direction: column; gap: 8px; flex: 1;">
                                @forelse($placeOrderMenuItems ?? [] as $mItem)
                                    @php
                                        $cQty = $placeOrderCart[$mItem->id]['quantity'] ?? 0;
                                        $isVeg = ($mItem->type ?? 'veg') === 'veg';
                                        $isOos = $mItem->track_stock && $mItem->stock_quantity !== null && $mItem->stock_quantity <= 0;
                                    @endphp
                                    <article class="stitch-dish-card" style="padding: 8px 12px; display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                                        <div style="flex: 1; min-width: 0;">
                                            <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 2px;">
                                                @if($isVeg)
                                                    <span class="stitch-veg-icon" title="Vegetarian"><span></span></span>
                                                @else
                                                    <span class="stitch-nonveg-icon" title="Non-Vegetarian"><span></span></span>
                                                @endif
                                                <span style="font-size: 9px; font-weight: 700; text-transform: uppercase; background: #f1f5f9; color: #475569; padding: 1px 5px; border-radius: 4px;">
                                                    {{ $mItem->category?->name ?? 'Dish' }}
                                                </span>
                                                @if($mItem->track_stock && $mItem->stock_quantity !== null)
                                                    <span style="font-size: 9px; font-weight: 700; padding: 1px 5px; border-radius: 4px; border: 1px solid {{ $isOos ? '#fecdd3' : '#a7f3d0' }}; background: {{ $isOos ? '#fff1f2' : '#ecfdf5' }}; color: {{ $isOos ? '#e11d48' : '#059669' }};">
                                                        {{ $isOos ? 'Sold Out' : "Stock: {$mItem->stock_quantity}" }}
                                                    </span>
                                                @endif
                                            </div>

                                            <h3 style="font-size: 13px; font-weight: 800; color: #0f172a; margin: 0 0 1px 0; line-height: 1.2;">
                                                {{ $mItem->name }}
                                            </h3>

                                            <div style="display: flex; align-items: baseline; gap: 6px;">
                                                <span style="font-size: 13px; font-weight: 800; color: #0f172a; font-family: monospace;">₹{{ number_format($mItem->price, 0) }}</span>
                                                @if($mItem->description)
                                                    <span style="font-size: 10px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 220px;">
                                                        • {{ $mItem->description }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Add / Stepper Controls --}}
                                        <div style="display: flex; align-items: center; flex-shrink: 0;">
                                            @if($isOos)
                                                <span style="display: inline-flex; align-items: center; background: #fff1f2; border: 1px solid #fecdd3; color: #e11d48; border-radius: 6px; padding: 3px 8px; font-size: 9px; font-weight: 800;">
                                                    SOLD OUT
                                                </span>
                                            @elseif($cQty > 0)
                                                <div style="display: inline-flex; align-items: center; background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.04); padding: 2px 4px;">
                                                    <button wire:click="decreaseCart({{ $mItem->id }})" style="width: 22px; height: 22px; border-radius: 5px; display: flex; align-items: center; justify-content: center; border: none; background: #f1f5f9; color: #475569; font-weight: 800; font-size: 13px; cursor: pointer;">−</button>
                                                    <span style="padding: 0 8px; font-size: 12px; font-weight: 800; color: #0f172a; font-family: monospace;">{{ $cQty }}</span>
                                                    <button wire:click="addToCart({{ $mItem->id }})" style="width: 22px; height: 22px; border-radius: 5px; display: flex; align-items: center; justify-content: center; border: none; background: #15803d; color: #ffffff; font-weight: 800; font-size: 13px; cursor: pointer;">+</button>
                                                </div>
                                            @else
                                                <button wire:click="addToCart({{ $mItem->id }})" style="display: inline-flex; align-items: center; gap: 3px; background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.04); padding: 5px 12px; font-size: 11px; font-weight: 800; color: #1e293b; cursor: pointer;">
                                                    <span style="color: #15803d; font-size: 13px; font-weight: 900;">+</span> ADD
                                                </button>
                                            @endif
                                        </div>
                                    </article>
                                @empty
                                    <div style="text-align: center; padding: 32px 14px; background: #ffffff; border: 1px dashed #cbd5e1; border-radius: 12px;">
                                        <p style="font-size: 12px; font-weight: 700; color: #64748b; margin: 0;">No dishes found matching your search.</p>
                                    </div>
                                @endforelse
                            </div>

                            {{-- Floating Sticky Cart Pill on Mobile --}}
                            @if($kotTotalItems > 0)
                                <div class="lg:hidden" style="position: sticky; bottom: 0; padding: 8px 10px; background: rgba(255,255,255,0.96); backdrop-filter: blur(8px); border-top: 1px solid #fed7aa; z-index: 50; margin: 8px -10px -8px -10px;">
                                    <button type="button" @click="mobileKotTab = 'cart'" style="width: 100%; padding: 11px 16px; border-radius: 10px; border: none; background: {{ $isInstantBillMode ? '#15803d' : '#a33900' }}; color: white; font-weight: 800; font-size: 13px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 12px rgba(0,0,0,0.18);">
                                        <span style="display: flex; align-items: center; gap: 8px;">
                                            <x-heroicon-s-shopping-bag style="width: 16px; height: 16px;" />
                                            <span>View Cart ({{ $kotTotalItems }} {{ \Illuminate\Support\Str::plural('item', $kotTotalItems) }})</span>
                                        </span>
                                        <span style="font-family: monospace; font-size: 14px;">₹{{ number_format($isInstantBillMode ? $iGrandTotal : $kotTotal, 2) }} →</span>
                                    </button>
                                </div>
                            @endif
                        </section>

                        {{-- RIGHT ORDER TICKET TRAY & SUMMARY --}}
                        <aside class="stitch-order-tray stitch-scroll" x-show="window.innerWidth > 1024 || mobileKotTab === 'cart'" :style="(window.innerWidth <= 1024 && mobileKotTab !== 'cart') ? 'display: none !important;' : ''">
                            
                            {{-- Mobile Back to Dishes Button --}}
                            <div class="lg:hidden" style="padding: 8px 12px; background: #ffffff; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;">
                                <button type="button" @click="mobileKotTab = 'menu'" style="border: none; background: none; color: #1e3a8a; font-size: 12px; font-weight: 800; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                                    <span>← Add More Dishes</span>
                                </button>
                                <span style="font-size: 11px; font-weight: 700; color: #64748b;">
                                    {{ $kotTotalItems }} Items Selected
                                </span>
                            </div>

                            {{-- Mobile Context Form (Shown inside Cart view on mobile for easy review) --}}
                            <div class="lg:hidden" style="padding: 10px 12px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; flex-shrink: 0;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
                                    <div>
                                        <label style="display: block; font-size: 9px; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 2px;">Table / Service</label>
                                        <div style="display: flex; gap: 4px;">
                                            <select wire:model.live="placeOrderServiceType" style="flex: 1; padding: 4px 6px; border-radius: 6px; border: 1px solid #cbd5e1; background: #ffffff; font-size: 10px; font-weight: 700; outline: none;">
                                                <option value="dine_in">🍽️ Dine In</option>
                                                <option value="parcel">🛍️ Takeaway</option>
                                            </select>
                                            @if($placeOrderServiceType === 'dine_in')
                                                <select wire:model.live="placeOrderTableId" style="flex: 1; padding: 4px 6px; border-radius: 6px; border: 1px solid #cbd5e1; background: #ffffff; font-size: 10px; font-weight: 700; outline: none;">
                                                    <option value="">Table...</option>
                                                    @foreach(\App\Models\RestaurantTable::where('restaurant_id', auth()->user()->restaurant_id)->when(auth()->user()->branch_id, fn($q) => $q->where('branch_id', auth()->user()->branch_id))->orderBy('table_number')->get() as $t)
                                                        <option value="{{ $t->id }}">T-{{ $t->table_number }}</option>
                                                    @endforeach
                                                </select>
                                            @endif
                                        </div>
                                    </div>
                                    <div>
                                        <label style="display: block; font-size: 9px; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 2px;">Guest Name</label>
                                        <input wire:model.live.debounce.300ms="placeOrderCustomerName" type="text" placeholder="Name..." style="width: 100%; box-sizing: border-box; padding: 4px 6px; border-radius: 6px; border: 1px solid #cbd5e1; background: #ffffff; font-size: 10px; font-weight: 700; outline: none;">
                                    </div>
                                    <div>
                                        <label style="display: block; font-size: 9px; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 2px;">Phone</label>
                                        <input wire:model.live.debounce.300ms="placeOrderCustomerPhone" type="tel" placeholder="Mobile..." style="width: 100%; box-sizing: border-box; padding: 4px 6px; border-radius: 6px; border: 1px solid #cbd5e1; background: #ffffff; font-size: 10px; font-weight: 700; outline: none;">
                                    </div>
                                    <div>
                                        @if($isInstantBillMode)
                                            <label style="display: block; font-size: 9px; font-weight: 800; text-transform: uppercase; color: #15803d; margin-bottom: 2px;">Payment</label>
                                            <select wire:model.live="placeOrderPaymentMethod" style="width: 100%; box-sizing: border-box; padding: 4px 6px; border-radius: 6px; border: 1px solid #16a34a; background: #f0fdf4; font-size: 10px; font-weight: 800; color: #14532d; outline: none;">
                                                <option value="cash">💵 Cash</option>
                                                <option value="upi">⚡ UPI</option>
                                                <option value="card">💳 Card</option>
                                            </select>
                                        @else
                                            <label style="display: block; font-size: 9px; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 2px;">Payment Status</label>
                                            <select wire:model.live="placeOrderPaymentStatus" style="width: 100%; box-sizing: border-box; padding: 4px 6px; border-radius: 6px; border: 1px solid #cbd5e1; background: #ffffff; font-size: 10px; font-weight: 700; outline: none;">
                                                <option value="pending">⏳ Pay Later</option>
                                                <option value="paid_upi">⚡ Paid UPI</option>
                                                <option value="paid_card">💳 Paid Card</option>
                                                <option value="paid_cash">💵 Paid Cash</option>
                                            </select>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Tray Top Header --}}
                            <div style="padding: 10px 14px; border-bottom: 1px solid #e2e8f0; background: #ffffff; flex-shrink: 0;">
                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <h3 style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #0f172a; letter-spacing: 0.5px; margin: 0; display: flex; align-items: center; gap: 6px;">
                                        <span>{{ $isInstantBillMode ? 'Bill Items' : 'KOT Items' }}</span>
                                        <span style="font-size: 10px; font-weight: 800; padding: 1px 6px; border-radius: 9999px; background: {{ $isInstantBillMode ? '#dcfce7' : '#ffedd5' }}; color: {{ $isInstantBillMode ? '#15803d' : '#a33900' }};">
                                            {{ $kotTotalItems }} ({{ $kotTotalQty }} Qty)
                                        </span>
                                    </h3>
                                    @if($kotTotalItems > 0)
                                        <button wire:click="clearCart" style="font-size: 10px; font-weight: 700; color: #e11d48; border: none; background: transparent; cursor: pointer; text-decoration: underline;">
                                            Clear All
                                        </button>
                                    @endif
                                </div>
                            </div>

                            {{-- Tray Selected Items List --}}
                            <div style="padding: 10px; display: flex; flex-direction: column; gap: 8px; flex: 1; overflow-y: auto;">
                                @forelse($placeOrderCart as $cId => $cItem)
                                    <div style="padding: 8px 10px; border-radius: 10px; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                                            <div style="display: flex; align-items: center; gap: 5px;">
                                                @if(($cItem['type'] ?? 'veg') === 'veg')
                                                    <span class="stitch-veg-icon" style="width: 11px; height: 11px;"><span style="width: 4px; height: 4px;"></span></span>
                                                @else
                                                    <span class="stitch-nonveg-icon" style="width: 11px; height: 11px;"><span style="width: 4px; height: 4px;"></span></span>
                                                @endif
                                                <span style="font-size: 11px; font-weight: 800; color: #0f172a;">{{ $cItem['name'] }}</span>
                                            </div>
                                            <span style="font-size: 12px; font-weight: 800; font-family: monospace; color: #0f172a;">₹{{ number_format($cItem['unit_price'] * $cItem['quantity'], 2) }}</span>
                                        </div>

                                        <div style="display: flex; align-items: center; justify-content: space-between; font-size: 10px; color: #64748b; padding-bottom: 4px; border-bottom: 1px dashed #f1f5f9;">
                                            <span>{{ $cItem['quantity'] }} × ₹{{ number_format($cItem['unit_price'], 0) }}</span>
                                            <div style="display: flex; align-items: center; gap: 6px;">
                                                <div style="display: inline-flex; align-items: center; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 1px 3px;">
                                                    <button wire:click="decreaseCart({{ $cId }})" style="width: 18px; height: 18px; border-radius: 4px; display: flex; align-items: center; justify-content: center; border: none; background: #ffffff; color: #475569; font-weight: 800; font-size: 11px; cursor: pointer;">−</button>
                                                    <span style="padding: 0 6px; font-size: 11px; font-weight: 800; color: #0f172a; font-family: monospace;">{{ $cItem['quantity'] }}</span>
                                                    <button wire:click="addToCart({{ $cId }})" style="width: 18px; height: 18px; border-radius: 4px; display: flex; align-items: center; justify-content: center; border: none; background: #15803d; color: #ffffff; font-weight: 800; font-size: 11px; cursor: pointer;">+</button>
                                                </div>
                                                <button wire:click="removeFromCart({{ $cId }})" style="border: none; background: transparent; color: #94a3b8; cursor: pointer; padding: 2px;" title="Remove dish">
                                                    <x-heroicon-m-trash style="width: 13px; height: 13px;" />
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Line Note --}}
                                        <div style="margin-top: 4px;">
                                            <input wire:model.live.debounce.300ms="placeOrderCart.{{ $cId }}.notes" type="text" placeholder="Cooking note..." style="width: 100%; box-sizing: border-box; font-size: 10px; font-style: italic; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 5px; padding: 3px 6px; color: #334155; outline: none;">
                                        </div>
                                    </div>
                                @empty
                                    <div style="text-align: center; padding: 28px 12px; background: #ffffff; border-radius: 10px; border: 1px dashed #cbd5e1;">
                                        <x-heroicon-o-shopping-bag style="width: 28px; height: 28px; color: #cbd5e1; margin: 0 auto 4px auto;" />
                                        <p style="font-size: 11px; font-weight: 700; color: #475569; margin: 0;">No items selected</p>
                                        <p style="font-size: 10px; color: #94a3b8; margin: 2px 0 0 0;">Click + ADD on any dish to add.</p>
                                    </div>
                                @endforelse

                                @if(!$isInstantBillMode)
                                    {{-- KOT Priority Options --}}
                                    <div style="padding-top: 8px;">
                                        <span style="display: block; font-size: 9px; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; margin-bottom: 4px;">
                                            KOT Priority
                                        </span>
                                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px;">
                                            <label style="cursor: pointer; text-align: center;">
                                                <input wire:model.live="placeOrderPriority" type="radio" value="normal" style="display: none;">
                                                <span style="display: block; padding: 5px 3px; font-size: 10px; font-weight: 700; border-radius: 6px; border: 1px solid {{ $placeOrderPriority === 'normal' ? '#0f172a' : '#cbd5e1' }}; background: {{ $placeOrderPriority === 'normal' ? '#0f172a' : '#ffffff' }}; color: {{ $placeOrderPriority === 'normal' ? '#ffffff' : '#475569' }};">
                                                    Standard
                                                </span>
                                            </label>
                                            <label style="cursor: pointer; text-align: center;">
                                                <input wire:model.live="placeOrderPriority" type="radio" value="urgent" style="display: none;">
                                                <span style="display: block; padding: 5px 3px; font-size: 10px; font-weight: 700; border-radius: 6px; border: 1px solid {{ $placeOrderPriority === 'urgent' ? '#a33900' : '#cbd5e1' }}; background: {{ $placeOrderPriority === 'urgent' ? '#a33900' : '#ffffff' }}; color: {{ $placeOrderPriority === 'urgent' ? '#ffffff' : '#a33900' }};">
                                                    ⚡ Rush
                                                </span>
                                            </label>
                                            <label style="cursor: pointer; text-align: center;">
                                                <input wire:model.live="placeOrderPriority" type="radio" value="hold" style="display: none;">
                                                <span style="display: block; padding: 5px 3px; font-size: 10px; font-weight: 700; border-radius: 6px; border: 1px solid {{ $placeOrderPriority === 'hold' ? '#4f46e5' : '#cbd5e1' }}; background: {{ $placeOrderPriority === 'hold' ? '#4f46e5' : '#ffffff' }}; color: {{ $placeOrderPriority === 'hold' ? '#ffffff' : '#4f46e5' }};">
                                                    ⏸️ Hold
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Financial Computation Box --}}
                            <div style="padding: 10px 14px; background: #ffffff; border-top: 1px solid #e2e8f0; flex-shrink: 0; font-size: 11px; display: flex; flex-direction: column; gap: 4px;">
                                @if($isInstantBillMode)
                                    <div style="display: flex; justify-content: space-between; color: #64748b;">
                                        <span>Subtotal</span>
                                        <span style="font-weight: 700; color: #0f172a; font-family: monospace;">₹{{ number_format($iSubtotal, 2) }}</span>
                                    </div>

                                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 6px;">
                                        <span style="color: #64748b; font-size: 10px;">Discount (₹)</span>
                                        <input wire:model.live.debounce.250ms="instantBillDiscount" type="number" step="any" min="0" placeholder="0" style="width: 70px; text-align: right; padding: 2px 5px; font-size: 10px; font-weight: 700; border-radius: 5px; border: 1px solid #cbd5e1; outline: none; font-family: monospace;">
                                    </div>

                                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 6px;">
                                        <span style="color: #64748b; font-size: 10px;">GST Rate (%)</span>
                                        <div style="display: flex; align-items: center; gap: 4px;">
                                            <input wire:model.live.debounce.250ms="instantBillTaxPercent" type="number" step="0.01" min="0" max="100" placeholder="5" style="width: 48px; text-align: right; padding: 2px 4px; font-size: 10px; font-weight: 700; border-radius: 5px; border: 1px solid #cbd5e1; outline: none; font-family: monospace;">
                                            <span style="font-weight: 700; color: #475569; font-size: 10px;">%</span>
                                            <span style="font-weight: 700; color: #475569; font-family: monospace; font-size: 10px; min-width: 46px; text-align: right;">+₹{{ number_format($iTax, 2) }}</span>
                                        </div>
                                    </div>

                                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 6px;">
                                        <span style="color: #64748b; font-size: 10px;">Extra / Pkg (₹)</span>
                                        <input wire:model.live.debounce.250ms="instantBillExtraCharges" type="number" step="any" min="0" placeholder="0" style="width: 70px; text-align: right; padding: 2px 5px; font-size: 10px; font-weight: 700; border-radius: 5px; border: 1px solid #cbd5e1; outline: none; font-family: monospace;">
                                    </div>

                                    <div style="display: flex; align-items: baseline; justify-content: space-between; padding-top: 4px; margin-top: 2px; border-top: 1.5px dashed #cbd5e1;">
                                        <span style="font-size: 12px; font-weight: 800; color: #15803d;">Bill Payable</span>
                                        <span style="font-size: 18px; font-weight: 900; color: #15803d; font-family: monospace;">₹{{ number_format($iGrandTotal, 2) }}</span>
                                    </div>
                                @else
                                    <div style="display: flex; justify-content: space-between; color: #64748b;">
                                        <span>Item Subtotal</span>
                                        <span style="font-weight: 700; color: #0f172a; font-family: monospace;">₹{{ number_format($kotSubtotal, 2) }}</span>
                                    </div>
                                    <div style="display: flex; align-items: baseline; justify-content: space-between; padding-top: 6px; margin-top: 2px; border-top: 1px dashed #cbd5e1;">
                                        <span style="font-size: 12px; font-weight: 800; color: #0f172a;">KOT Total</span>
                                        <span style="font-size: 18px; font-weight: 900; color: #a33900; font-family: monospace;">₹{{ number_format($kotTotal, 2) }}</span>
                                    </div>
                                @endif
                            </div>

                            {{-- Mobile Cart Actions (Shown inside Cart view on mobile) --}}
                            <div class="lg:hidden" style="padding: 10px 14px; background: #ffffff; border-top: 1.5px solid #e2e8f0; flex-shrink: 0; display: flex; flex-direction: column; gap: 8px;">
                                @if($isInstantBillMode)
                                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 6px; font-size: 11px;">
                                        <label style="display: flex; align-items: center; gap: 4px; font-weight: 700; color: #15803d; cursor: pointer; user-select: none;">
                                            <input wire:model.live="instantBillIncludeQr" type="checkbox" style="border-radius: 4px; color: #15803d;">
                                            <span>Dynamic UPI QR</span>
                                        </label>
                                        <div style="display: flex; align-items: center; gap: 4px;">
                                            <span style="color: #64748b; font-size: 10px; font-weight: 700;">Format:</span>
                                            <select wire:model.live="instantBillPaperSize" style="padding: 2px 4px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 10px; font-weight: 700; background: #ffffff; color: #0f172a; outline: none;">
                                                <option value="80mm">80mm POS</option>
                                                <option value="58mm">58mm Roll</option>
                                                <option value="a4">A4 Normal</option>
                                            </select>
                                        </div>
                                    </div>
                                @else
                                    <label style="display: flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 600; color: #334155; cursor: pointer; user-select: none;">
                                        <input wire:model="placeOrderAutoPrint" type="checkbox" style="border-radius: 4px; color: #a33900;">
                                        <span>Auto-Print Kitchen Slip (80mm)</span>
                                    </label>
                                @endif

                                <div style="display: flex; gap: 8px;">
                                    <button wire:click="closePlaceOrder" style="flex: 1; padding: 10px; border-radius: 10px; border: 1px solid #cbd5e1; background: #ffffff; color: #475569; font-weight: 700; font-size: 12px; cursor: pointer;">
                                        Cancel
                                    </button>
                                    @if($isInstantBillMode)
                                        <button wire:click="submitInstantBill" wire:loading.attr="disabled" :disabled="$isSubmittingOrder" style="flex: 2; padding: 10px 14px; border-radius: 10px; border: none; background: #15803d; color: #ffffff; font-weight: 800; font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 4px 12px rgba(21, 128, 61, 0.3);">
                                            <x-heroicon-s-bolt style="width: 15px; height: 15px;" />
                                            <span wire:loading.remove wire:target="submitInstantBill">⚡ Print Instant Bill</span>
                                            <span wire:loading wire:target="submitInstantBill">Processing...</span>
                                        </button>
                                    @else
                                        <button wire:click="submitPlaceOrder" wire:loading.attr="disabled" :disabled="$isSubmittingOrder" style="flex: 2; padding: 10px 14px; border-radius: 10px; border: none; background: #a33900; color: #ffffff; font-weight: 800; font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 4px 12px rgba(163, 57, 0, 0.3);">
                                            <x-heroicon-s-paper-airplane style="width: 15px; height: 15px;" />
                                            <span wire:loading.remove wire:target="submitPlaceOrder">Send KOT</span>
                                            <span wire:loading wire:target="submitPlaceOrder">Sending...</span>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </aside>
                    </div>

                    {{-- ── 4. DESKTOP MODAL FOOTER ACTIONS ── --}}
                    <footer class="stitch-order-footer hidden lg:flex" style="display: none;" :style="window.innerWidth > 1024 ? 'display: flex !important;' : 'display: none !important;'">
                        {{-- Left Checkboxes & Format Selectors --}}
                        <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                            @if($isInstantBillMode)
                                <label style="display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; color: #15803d; cursor: pointer; user-select: none;">
                                    <input wire:model.live="instantBillIncludeQr" type="checkbox" style="border-radius: 4px; color: #15803d;">
                                    <span>Include Dynamic UPI QR</span>
                                </label>

                                <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #475569;">
                                    <span style="font-weight: 700;">Paper Format:</span>
                                    <select wire:model.live="instantBillPaperSize" style="padding: 4px 8px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 11px; font-weight: 700; background: #ffffff; color: #0f172a; outline: none; cursor: pointer;">
                                        <option value="80mm">80mm Thermal (Standard POS)</option>
                                        <option value="58mm">58mm / 60mm (Compact Roll)</option>
                                        <option value="a4">A4 / Normal Invoice</option>
                                    </select>
                                </div>
                            @else
                                <label style="display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; color: #334155; cursor: pointer; user-select: none;">
                                    <input wire:model="placeOrderAutoPrint" type="checkbox" style="border-radius: 4px; color: #a33900;">
                                    <span>Auto-Print Kitchen Slip (Thermal 80mm)</span>
                                </label>
                            @endif

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
                            @if($isInstantBillMode)
                                <button wire:click="submitInstantBill" wire:loading.attr="disabled" :disabled="$isSubmittingOrder" style="padding: 10px 22px; border-radius: 12px; border: none; background: #15803d; color: #ffffff; font-weight: 800; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(21, 128, 61, 0.35);">
                                    <x-heroicon-s-bolt style="width: 16px; height: 16px;" class="animate-bounce" />
                                    <span wire:loading.remove wire:target="submitInstantBill">⚡ Print Instant Bill & Tax Invoice</span>
                                    <span wire:loading wire:target="submitInstantBill">Processing Bill...</span>
                                </button>
                            @else
                                <button wire:click="submitPlaceOrder" wire:loading.attr="disabled" :disabled="$isSubmittingOrder" style="padding: 10px 22px; border-radius: 12px; border: none; background: #a33900; color: #ffffff; font-weight: 800; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(163, 57, 0, 0.35);">
                                    <svg style="width: 16px; height: 16px;" class="animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                    <span wire:loading.remove wire:target="submitPlaceOrder">Send Order to Kitchen (KOT)</span>
                                    <span wire:loading wire:target="submitPlaceOrder">Submitting...</span>
                                </button>
                            @endif
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