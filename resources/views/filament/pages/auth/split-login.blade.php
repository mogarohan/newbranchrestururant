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

        /* ── MOBILE FIRST (SCROLL ON) ── */
        html,
        body {
            height: auto !important;
            overflow-x: hidden !important;
            overflow-y: auto !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        /* ── DESKTOP ONLY SCROLL OFF ── */
        @media (min-width: 1024px) {

            html,
            body {
                height: 100% !important;
                overflow: hidden !important;
            }
        }

        /* ── Hide Filament wrappers ── */
        .fi-simple-layout {
            background-color: var(--orange) !important;
            height: 100vh !important;
            padding: 0.4rem 1.25rem !important;
            display: flex !important;
            align-items: stretch !important;
        }

        @media (min-width: 1024px) {
            .fi-simple-layout {
                height: 100vh !important;
                padding: 0.5rem 1rem !important;
                overflow: hidden !important;
            }
        }

        .fi-simple-main {
            background: transparent !important;
            box-shadow: none !important;
            padding: 0 !important;
            max-width: 100% !important;
            width: 100% !important;
            border-radius: 0 !important;
            display: flex !important;
            flex-direction: column !important;
        }

        .fi-simple-page {
            background: transparent !important;
            padding: 0 !important;
            width: 100% !important;
            flex: 1 !important;
            display: flex !important;
            flex-direction: column !important;
        }

        .fi-simple-header,
        nav.fi-topbar,
        .fi-topbar,
        header.fi-header,
        .fi-topbar-nav,
        body>header {
            display: none !important;
        }

        .fi-footer-custom {
            display: none !important;
        }

        /* ── Card ── */
        .card {
            position: relative;
            width: 100%;
            flex: 1;
            border-radius: 2rem;
            overflow: hidden;
            background-color: var(--cream);
            box-shadow: 0 32px 80px rgba(0, 0, 0, 0.22);
            display: flex;
            flex-direction: column;
        }

        @media (min-width: 1024px) {
            .card {
                flex-direction: row;
                height: 100%;
            }
        }

        /* ── BG texture ── */
        .card-bg {
            position: absolute;
            inset: 0;
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
            position: absolute;
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
        }

        @media (min-width: 1024px) {
            .left-panel {
                width: 50%;
                padding: 3.5rem;
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
            font-family: 'Lora', Georgia, serif;
            font-size: clamp(1.1rem, 2.5vw, 1.45rem);
            color: var(--navy);
            font-weight: 600;
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
            overflow-y: visible;
            /* MOBILE scroll */
        }

        @media (min-width: 1024px) {
            .right-panel {
                width: 50%;
                padding: 3.5rem;
                justify-content: flex-start;
                overflow-y: auto;
                /* DESKTOP scroll inside */
            }
        }

        /* ── Glass box ── */
        .glass-box {
            width: 100%;
            max-width: 26rem;
            border-radius: 1.75rem;
            border: 1.5px solid var(--glass-border);
            padding: 2.5rem 2rem;
            backdrop-filter: blur(24px);
            background: rgba(252, 236, 221, 0.6);
        }

        .glass-box h1 {
            font-family: 'Playfair Display';
            font-size: 2.2rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        /* Button */
        .fi-btn-primary {
            width: 100%;
        }
    </style>

    <div class="card">

        <div class="card-bg"></div>
        <div class="deco-circle deco-1"></div>
        <div class="deco-circle deco-2"></div>

        {{-- LEFT --}}
        <div class="left-panel">
            <img class="logo" src="{{ asset('images/ann-sathi.png') }}">
            <p class="tagline">Sathi Of Your Food Journey</p>
            <p class="powered">Powered By - Techstrota</p>
        </div>

        {{-- RIGHT --}}
        <div class="right-panel">
            <div class="glass-box">
                <h1>LOGIN</h1>

                <x-filament-panels::form wire:submit="authenticate">
                    {{ $this->form }}

                    <div style="margin-top: 1.5rem;">
                        <x-filament-panels::form.actions :actions="$this->getCachedFormActions()"
                            :full-width="$this->hasFullWidthFormActions()" />
                    </div>
                </x-filament-panels::form>

            </div>
        </div>

    </div>

    <script>
        function hideTopbar() {
            ['nav.fi-topbar', '.fi-topbar', 'header.fi-header'].forEach(sel => {
                document.querySelectorAll(sel).forEach(el => el.remove());
            });
        }
        document.addEventListener('DOMContentLoaded', hideTopbar);
        document.addEventListener('livewire:navigated', hideTopbar);
    </script>

</x-filament-panels::page.simple>