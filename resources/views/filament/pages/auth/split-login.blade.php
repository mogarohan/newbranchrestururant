<x-filament-panels::page.simple>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;700&family=DM+Sans:wght@400;500;600&family=Lora:wght@400;500;600&display=swap');

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --orange: #da6a22;
            --orange-dark: #b85517;
            --navy: #204080;
            --cream: #FCECDD;
            --glass-border: rgba(218, 106, 34, 0.28);
        }

        /* ── BODY STYLES ── */
        html,
        body {
            height: 100vh !important;
            width: 100vw !important;
            overflow: hidden !important;
            font-family: 'DM Sans', sans-serif !important;
            padding: 0 !important;
            margin: 0 !important;
            background-color: var(--orange) !important;
        }

        .fi-simple-header,
        nav.fi-topbar,
        .fi-topbar,
        header.fi-header,
        .fi-topbar-nav,
        body>header,
        .fi-footer-custom {
            display: none !important;
        }

        /* ── CUSTOM PAGE WRAPPER ── */
        .page-wrap {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            height: 100vh !important;
            width: 100vw !important;
            background-color: var(--orange) !important;
            display: flex !important;
            align-items: stretch !important;
            padding: 1.25rem !important;
            z-index: 99999 !important;
            box-sizing: border-box !important;
        }

        @media (min-width: 1024px) {
            .page-wrap {
                padding: 2rem !important;
            }
        }

        /* ── Card (Cream Box) ── */
        .card {
            position: relative;
            /* Essential to keep background inside */
            flex: 1 !important;
            width: 100%;
            height: 100%;
            border-radius: 2rem;
            background-color: var(--cream);
            box-shadow: 0 32px 80px rgba(0, 0, 0, 0.22);
            /* 🔥 FIX: Card ko hidden rakha taaki background andar hi rahe aur edges round rahein */
            overflow: hidden !important;
            display: flex;
            flex-direction: column;
        }

        /* 🔥 BG TEXTURE (Card ke andar lock kiya hai, absolute position se) ── */
        .card-bg {
            position: absolute !important;
            inset: 0 !important;
            /* Top, left, right, bottom 0 = fill cream box */
            z-index: 0;
            pointer-events: none;
            background-image: url('{{ asset("images/bg.png") }}');
            background-size: cover;
            background-position: center;
            opacity: 0.20;
            mix-blend-mode: multiply;
        }

        /* ── Decorative circles ── */
        .deco-circle {
            position: absolute !important;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }

        .deco-1 {
            width: 280px;
            height: 280px;
            top: -80px;
            right: -60px;
            background: radial-gradient(circle, rgba(218, 106, 34, 0.12) 0%, transparent 70%);
        }

        .deco-2 {
            width: 200px;
            height: 200px;
            bottom: -60px;
            left: -40px;
            background: radial-gradient(circle, rgba(32, 64, 128, 0.10) 0%, transparent 70%);
        }

        /* 🔥 CONTENT WRAPPER (Sirf ye scroll hoga mobile par) */
        .card-content {
            position: relative;
            z-index: 1;
            /* Background ke upar */
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            overflow-y: auto !important;
            /* Mobile scroll enabled */
            overflow-x: hidden !important;
        }

        @media (min-width: 1024px) {
            .card-content {
                flex-direction: row;
                overflow-y: hidden !important;
                /* Desktop par scroll band */
            }
        }

        /* ── Left panel ── */
        .left-panel {
            position: relative;
            z-index: 1;
            width: 100%;
            padding: 3rem 2rem 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            gap: 1rem;
            flex-shrink: 0;
        }

        @media (min-width: 1024px) {
            .left-panel {
                width: 50%;
                padding: 3.5rem;
                flex-shrink: 1;
            }
        }

        .left-panel img.logo {
            width: min(220px, 70%);
            filter: drop-shadow(0 12px 28px rgba(32, 64, 128, 0.18));
            animation: floatLogo 5s ease-in-out infinite;
        }

        @keyframes floatLogo {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .tagline {
            font-family: 'Lora', 'Times New Roman', Georgia, serif;
            font-size: clamp(1.1rem, 2.5vw, 1.45rem);
            color: var(--navy);
            font-style: normal;
            font-weight: 600;
            letter-spacing: 0.03em;
        }

        .powered {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--navy);
            opacity: 0.75;
        }

        /* ── Right panel ── */
        .right-panel {
            position: relative;
            z-index: 1;
            width: 100%;
            padding: 2rem 2rem 3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        @media (min-width: 1024px) {
            .right-panel {
                width: 50%;
                padding: 3.5rem;
                justify-content: flex-start;
                flex-shrink: 1;
            }
        }

        /* ── Glass box ── */
        .glass-box {
            width: 100%;
            max-width: 26rem;
            border-radius: 1.75rem;
            border: 1.5px solid var(--glass-border);
            padding: 2.5rem 2rem;
            backdrop-filter: blur(24px) saturate(160%);
            -webkit-backdrop-filter: blur(24px) saturate(160%);
            background: linear-gradient(135deg, rgba(252, 236, 221, 0.52) 0%, rgba(252, 236, 221, 0.28) 60%, rgba(218, 106, 34, 0.08) 100%);
            box-shadow: 0 24px 60px rgba(32, 64, 128, 0.14), inset 0 1px 0 rgba(255, 255, 255, 0.55), inset 0 -1px 0 rgba(218, 106, 34, 0.12);
        }

        @media (min-width: 640px) {
            .glass-box {
                padding: 3rem 2.5rem;
            }
        }

        .glass-box h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2rem, 4vw, 2.6rem);
            font-weight: 700;
            color: var(--navy);
            letter-spacing: 0.04em;
            margin-bottom: 2rem;
            text-align: center;
        }

        @media (min-width: 1024px) {
            .glass-box h1 {
                text-align: left;
            }
        }

        /* --- FILAMENT FORM STYLING --- */
        .fi-fo-component-ctn {
            gap: 1.2rem !important;
        }

        .fi-fo-field-wrp-label span {
            color: #1a1a2e !important;
            font-weight: 600 !important;
            font-size: 0.95rem !important;
        }

        .fi-fo-field-wrp-label sup {
            color: #dc2626 !important;
        }

        .fi-input-wrapper {
            background: rgba(252, 236, 221, 0.35) !important;
            border: 1.5px solid rgba(218, 106, 34, 0.38) !important;
            border-radius: 0.9rem !important;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.4), 0 6px 16px rgba(218, 106, 34, 0.07) !important;
            transition: all 0.3s ease;
        }

        .fi-input-wrapper:focus-within {
            border-color: #da6a22 !important;
            background: rgba(252, 236, 221, 0.55) !important;
            box-shadow: 0 0 0 3px rgba(218, 106, 34, 0.18), inset 0 1px 0 rgba(255, 255, 255, 0.4) !important;
        }

        .fi-input-wrapper input {
            background: transparent !important;
            color: #111827 !important;
            font-size: 1rem !important;
            padding: 0.85rem 0 !important;
        }

        .fi-checkbox-input {
            border: 1.5px solid rgba(218, 106, 34, 0.5) !important;
            background: rgba(252, 236, 221, 0.28) !important;
            border-radius: 0.35rem !important;
            accent-color: var(--orange) !important;
        }

        .fi-btn-primary {
            background: linear-gradient(135deg, var(--orange) 0%, #e8832e 50%, var(--orange-dark) 100%) !important;
            color: #fff !important;
            border-radius: 0.75rem !important;
            padding: 0.9rem 1.5rem !important;
            font-weight: 600 !important;
            font-size: 1.1rem !important;
            border: none !important;
            box-shadow: 0 10px 28px rgba(218, 106, 34, 0.35), inset 0 1px 0 rgba(255, 255, 255, 0.2) !important;
            width: 100% !important;
            margin-top: 1.5rem !important;
            transition: all 0.3s ease !important;
        }

        .fi-btn-primary:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 16px 36px rgba(218, 106, 34, 0.42) !important;
        }

        .fi-fo-field-wrp-error-message {
            color: #dc2626 !important;
            font-weight: 600 !important;
        }
    </style>

    <div class="page-wrap">
        <div class="card">

            <div class="card-bg"></div>
            <div class="deco-circle deco-1"></div>
            <div class="deco-circle deco-2"></div>

            <div class="card-content">
                {{-- LEFT --}}
                <div class="left-panel">
                    <img class="logo" src="{{ asset('images/ann-sathi.png') }}">
                    <p class="tagline">"Sathi Of Your Food Journey"</p>
                    <p class="powered">Powered By - Techstrota</p>
                </div>

                {{-- RIGHT --}}
                <div class="right-panel">
                    <div class="glass-box">
                        <h1>LOGIN</h1>

                        <div class="w-full">
                            <x-filament-panels::form wire:submit="authenticate">
                                {{ $this->form }}
                                <div>
                                    <x-filament-panels::form.actions :actions="$this->getCachedFormActions()"
                                        :full-width="$this->hasFullWidthFormActions()" />
                                </div>
                            </x-filament-panels::form>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>

</x-filament-panels::page.simple>