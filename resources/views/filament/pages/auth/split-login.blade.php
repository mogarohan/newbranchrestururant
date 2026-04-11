<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Ann Sathi</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;700&family=DM+Sans:wght@400;500;600&family=Lora:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --orange:       #da6a22;
            --orange-dark:  #b85517;
            --orange-light: rgba(218,106,34,0.18);
            --navy:         #204080;
            --cream:        #FCECDD;
            --cream-soft:   rgba(252,236,221,0.38);
            --glass-bg:     rgba(252,236,221,0.22);
            --glass-border: rgba(218,106,34,0.28);
        }

        html, body {
            height: 100%;
            overflow: hidden;
            font-family: 'DM Sans', sans-serif;
        }

        /* Hide Filament's injected white topbar */
        nav.fi-topbar,
        .fi-topbar,
        header.fi-header,
        .fi-topbar-nav,
        body > header {
            display: none !important;
        }

        /* ── Full-screen wrapper ── */
        .page-wrap {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            height: 100vh;
            width: 100vw;
            overflow: hidden;
            background-color: var(--orange);
            display: flex;
            align-items: stretch;
            padding: 1.25rem;
            z-index: 99999;
        }

        @media (min-width: 1024px) { .page-wrap { padding: 2rem; } }

        /* ── Card ── */
        .card {
            position: relative;
            width: 100%;
            border-radius: 2rem;
            overflow: hidden;
            background-color: var(--cream);
            box-shadow: 0 32px 80px rgba(0,0,0,0.22);
            display: flex;
            flex-direction: column;
        }

        @media (min-width: 1024px) { .card { flex-direction: row; } }

        /* ── BG texture overlay ── */
        .card-bg {
            position: absolute;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            background-image: url('{{ asset("images/QR-BG.png") }}');
            background-size: cover;
            background-position: center;
            opacity: 0.60;
            mix-blend-mode: multiply;
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
            filter: drop-shadow(0 12px 28px rgba(32,64,128,0.18));
            animation: floatLogo 5s ease-in-out infinite;
        }

        @keyframes floatLogo {
            0%, 100% { transform: translateY(0); }
            50%       { transform: translateY(-10px); }
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
        }

        @media (min-width: 1024px) {
            .right-panel {
                width: 50%;
                padding: 3.5rem;
                justify-content: flex-start;
            }
        }

        /* ── Glass login box ── */
        .glass-box {
            width: 100%;
            max-width: 26rem;
            border-radius: 1.75rem;
            border: 1.5px solid var(--glass-border);
            padding: 2.5rem 2rem;
            backdrop-filter: blur(24px) saturate(160%);
            -webkit-backdrop-filter: blur(24px) saturate(160%);
            background: linear-gradient(
                135deg,
                rgba(252,236,221,0.52) 0%,
                rgba(252,236,221,0.28) 60%,
                rgba(218,106,34,0.08) 100%
            );
            box-shadow:
                0 24px 60px rgba(32,64,128,0.14),
                inset 0 1px 0 rgba(255,255,255,0.55),
                inset 0 -1px 0 rgba(218,106,34,0.12);
        }

        @media (min-width: 640px) { .glass-box { padding: 3rem 2.5rem; } }

        .glass-box h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2rem, 4vw, 2.6rem);
            font-weight: 700;
            color: var(--navy);
            letter-spacing: 0.04em;
            margin-bottom: 2rem;
            text-align: center;
        }

        @media (min-width: 1024px) { .glass-box h1 { text-align: left; } }

        /* ── Form fields ── */
        .field { margin-bottom: 1.4rem; }

        .field label {
            display: block;
            font-size: 0.95rem;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 0.4rem;
        }

        .field label span.req { color: #dc2626; margin-left: 2px; }

        .input-wrap {
            display: flex;
            align-items: center;
            background: rgba(252,236,221,0.35);
            border: 1.5px solid rgba(218,106,34,0.38);
            border-radius: 0.9rem;
            padding: 0 1rem;
            transition: all 0.3s ease;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.4), 0 6px 16px rgba(218,106,34,0.07);
            backdrop-filter: blur(10px);
        }

        .input-wrap:focus-within {
            border-color: var(--orange);
            background: rgba(252,236,221,0.55);
            box-shadow: 0 0 0 3px rgba(218,106,34,0.18), inset 0 1px 0 rgba(255,255,255,0.4);
        }

        .input-wrap input {
            flex: 1;
            background: transparent;
            border: none;
            outline: none;
            color: #111827;
            font-size: 1rem;
            font-family: 'DM Sans', sans-serif;
            padding: 0.85rem 0;
        }

        .input-wrap input::placeholder { color: rgba(32,64,128,0.38); }

        .input-wrap .eye-btn {
            background: none;
            border: none;
            cursor: pointer;
            color: rgba(32,64,128,0.5);
            padding: 0;
            display: flex;
            align-items: center;
            transition: color 0.2s;
        }

        .input-wrap .eye-btn:hover { color: var(--orange); }

        /* ── Remember me ── */
        .remember {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.6rem;
        }

        .remember input[type=checkbox] {
            width: 1.1rem;
            height: 1.1rem;
            accent-color: var(--orange);
            border-radius: 0.35rem;
            cursor: pointer;
        }

        .remember label {
            font-size: 0.95rem;
            color: #374151;
            cursor: pointer;
            user-select: none;
        }

        /* ── Sign in button ── */
        .btn-signin {
            width: 100%;
            padding: 0.9rem 1.5rem;
            background: linear-gradient(135deg, var(--orange) 0%, #e8832e 50%, var(--orange-dark) 100%);
            color: #fff;
            font-family: 'DM Sans', sans-serif;
            font-size: 1.1rem;
            font-weight: 600;
            border: none;
            border-radius: 0.75rem;
            cursor: pointer;
            letter-spacing: 0.03em;
            box-shadow:
                0 10px 28px rgba(218,106,34,0.35),
                inset 0 1px 0 rgba(255,255,255,0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-signin::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.15) 0%, transparent 60%);
            border-radius: inherit;
        }

        .btn-signin:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 36px rgba(218,106,34,0.42);
            background: linear-gradient(135deg, #e07728 0%, var(--orange-dark) 100%);
        }

        .btn-signin:active { transform: translateY(0); }

        /* ── Decorative circles ── */
        .deco-circle {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }

        .deco-1 {
            width: 280px; height: 280px;
            top: -80px; right: -60px;
            background: radial-gradient(circle, rgba(218,106,34,0.12) 0%, transparent 70%);
        }

        .deco-2 {
            width: 200px; height: 200px;
            bottom: -60px; left: -40px;
            background: radial-gradient(circle, rgba(32,64,128,0.10) 0%, transparent 70%);
        }
    </style>
</head>
<body>

<div class="page-wrap">
    <div class="card">

        <!-- BG texture -->
        <div class="card-bg"></div>

        <!-- Decorative blobs -->
        <div class="deco-circle deco-1"></div>
        <div class="deco-circle deco-2"></div>

        <!-- ── LEFT: Branding ── -->
        <div class="left-panel">
            <img class="logo" src="{{ asset('images/ann-sathi.png') }}" alt="Ann Sathi">
            <p class="tagline">"Sathi Of Your Food Journey"</p>
            <p class="powered">Powerd By - Techstrota</p>
        </div>

        <!-- ── RIGHT: Login form ── -->
        <div class="right-panel">
            <div class="glass-box">
                <h1>LOGIN</h1>

                {{-- ─ Filament form injection point ─ --}}
                <style>
                    .fi-simple-main, .fi-simple-page {
                        background: transparent !important;
                        box-shadow: none !important;
                        padding: 0 !important;
                        max-width: 100% !important;
                    }
                    .fi-fo-component-ctn { gap: 1.2rem !important; }
                    .fi-fo-field-wrp-label span {
                        color: #1a1a2e !important;
                        font-weight: 600 !important;
                        font-size: 0.95rem !important;
                    }
                    .fi-fo-field-wrp-label sup { color: #dc2626 !important; }
                    .fi-input-wrapper {
                        background: rgba(252,236,221,0.35) !important;
                        border: 1.5px solid rgba(218,106,34,0.38) !important;
                        border-radius: 0.9rem !important;
                        box-shadow: inset 0 1px 0 rgba(255,255,255,0.4), 0 6px 16px rgba(218,106,34,0.07) !important;
                        backdrop-filter: blur(10px);
                        -webkit-backdrop-filter: blur(10px);
                        transition: all 0.3s ease;
                    }
                    .fi-input-wrapper:focus-within {
                        border-color: #da6a22 !important;
                        background: rgba(252,236,221,0.55) !important;
                        box-shadow: 0 0 0 3px rgba(218,106,34,0.18), inset 0 1px 0 rgba(255,255,255,0.4) !important;
                    }
                    .fi-input-wrapper input {
                        background: transparent !important;
                        color: #111827 !important;
                        font-size: 1rem !important;
                    }
                    .fi-checkbox-input {
                        border: 1.5px solid rgba(218,106,34,0.5) !important;
                        background: rgba(252,236,221,0.28) !important;
                        border-radius: 0.4rem !important;
                        accent-color: #da6a22 !important;
                    }
                    .fi-btn-primary {
                        background: linear-gradient(135deg, #da6a22, #b85517) !important;
                        color: #fff !important;
                        border-radius: 0.75rem !important;
                        padding: 0.9rem 1.5rem !important;
                        font-weight: 600 !important;
                        font-size: 1.1rem !important;
                        border: none !important;
                        box-shadow: 0 10px 28px rgba(218,106,34,0.35) !important;
                        width: 100% !important;
                        margin-top: 1rem !important;
                        transition: all 0.3s ease !important;
                    }
                    .fi-btn-primary:hover {
                        transform: translateY(-2px) !important;
                        box-shadow: 0 16px 36px rgba(218,106,34,0.42) !important;
                    }
                    .fi-simple-header { display: none !important; }
                    nav.fi-topbar,
                    .fi-topbar,
                    header.fi-header,
                    .fi-topbar-nav { display: none !important; }
                    body { padding-top: 0 !important; margin-top: 0 !important; }
                    /* Hide footer on login page */
                    .fi-footer-custom { display: none !important; }
                </style>

                <div class="w-full">
                    <x-filament-panels::form wire:submit="authenticate">
                        {{ $this->form }}
                        <div class="mt-8">
                            <x-filament-panels::form.actions
                                :actions="$this->getCachedFormActions()"
                                :full-width="$this->hasFullWidthFormActions()" />
                        </div>
                    </x-filament-panels::form>
                </div>

            </div>
        </div>

    </div>
</div>

<script>
    // Remove any element that creates the white bar at the top
    function removeWhiteBar() {
        const selectors = [
            'nav.fi-topbar',
            '.fi-topbar',
            'header.fi-header',
            '.fi-topbar-nav',
            'body > header',
            'body > nav',
            '.fi-simple-layout > header',
            '.fi-simple-layout > nav',
        ];
        selectors.forEach(sel => {
            document.querySelectorAll(sel).forEach(el => el.remove());
        });

        // Also find any element that is white/light colored and sits at very top of body
        document.querySelectorAll('body > *').forEach(el => {
            if (el.classList.contains('page-wrap')) return;
            const rect = el.getBoundingClientRect();
            const bg = window.getComputedStyle(el).backgroundColor;
            if (rect.top <= 0 && rect.height > 0 && rect.height < 100) {
                el.style.display = 'none';
            }
        });
    }

    // Run immediately and after Livewire/Alpine loads
    document.addEventListener('DOMContentLoaded', removeWhiteBar);
    setTimeout(removeWhiteBar, 100);
    setTimeout(removeWhiteBar, 500);
    document.addEventListener('livewire:load', removeWhiteBar);
</script>
</body>
</html>