<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->maxContentWidth('full')
            ->brandLogo(asset('img/annsathilogo.png'))
            ->brandLogoHeight('5rem')
            ->brandName('AnnSathi')
            ->darkMode(false)
            ->colors([
                'primary' => Color::hex('#f16b3f'),
                'gray' => Color::Slate,
            ])
            ->navigationGroups([
                'Administration',
                'Access Control',
                'Menu Management',
                'Restaurant Table Setup',
                'Finance',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])

            // ── TOPBAR GREETING ──────────────────────────────────────
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn(): string => Blade::render('
                    <div id="fi-greeting-wrap" style="
                        display: flex;
                        align-items: center;
                        height: 100%;
                        padding: 0 0.5rem 0 0;
                        font-family: Poppins, sans-serif;
                        font-size: 0.95rem;
                        font-weight: 600;
                        color: #2a4795;
                        letter-spacing: 0.01em;
                        white-space: nowrap;
                    ">
                        Hello..!!&nbsp;<span style="color: #f16b3f;">{{ auth()->user()?->name ?? \'\' }}</span>&nbsp;👋
                    </div>
                    <script>
                        document.addEventListener("DOMContentLoaded", function () {
                            function fixOrder() {
                                const greeting = document.getElementById("fi-greeting-wrap");
                                const userMenu = document.querySelector(".fi-user-menu");
                                if (!greeting || !userMenu) return;

                                const parent = userMenu.parentElement;
                                if (!parent) return;

                                parent.insertBefore(greeting, userMenu);
                            }
                            fixOrder();
                            setTimeout(fixOrder, 300);
                        });
                    </script>
                ')
            )

            // ── HEAD: fonts + global styles ──────────────────────────────
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn(): string => Blade::render('
                    @vite([\'resources/js/app.js\'])
                    <link rel="preconnect" href="https://fonts.googleapis.com">
                    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
                    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&family=Lora:wght@500;600&display=swap" rel="stylesheet">
                    <style>
                        /* ── Brand Logo ── */
                        .fi-logo img,
                        .fi-sidebar-header img {
                            height: 3.5rem !important;
                            width: auto !important;
                            object-fit: contain !important;
                            max-width: 100% !important;
                        }

                        /* Force light mode always */
                        html { color-scheme: light !important; }
                        .dark, [data-theme="dark"] { display: revert !important; }

                        /* ── Global fonts ── */
                        html, body, p, span, div, input, select, textarea, button, a, table, td, th {
                            font-family: "Inter", sans-serif !important;
                        }
                        h1, h2, h3, h4, h5, h6,
                        .fi-header-heading,
                        .fi-modal-heading,
                        .fi-ta-header-heading,
                        .fi-fieldset-legend {
                            font-family: "Poppins", "Inter", sans-serif !important;
                            font-weight: 700 !important;
                        }

                        /* ── Layout backgrounds ── */
                        html, body,
                        .fi-main, .fi-sidebar,
                        .dark .fi-main, .dark .fi-sidebar,
                        .dark .fi-topbar {
                            background-color: #f8fafc !important;
                            background-image: none !important;
                            color: #1e293b !important;
                        }

                        /* ── Fix Content Cut-off behind Footer ── */
                        .fi-main {
                            /* यह पैडिंग कंटेंट को फुटर के ऊपर रखेगी ताकि कुछ कटे नहीं */
                            padding-bottom: 5rem !important; 
                        }

                        /* ── Sidebar adjustments ── */
                        .fi-sidebar-nav {
                            background-color: #ffffff !important;
                            border-right: none !important;
                            box-shadow: 2px 0 8px rgba(42,71,149,0.08) !important;
                        }

                        /* ── Topbar styling ── */
                        .fi-topbar {
                            background-color: #ffffff !important;
                            
                        }

                        /* ── Cards / sections / stats ── */
                        .fi-wi-stats-overview-stat,
                        .fi-section,
                        .fi-ta-record,
                        .fi-wi-chart {
                            background-color: #ffffff !important;
                            border: 1px solid #e2e8f0 !important;
                            box-shadow: 0 1px 3px rgba(241,107,63,0.18) !important;
                            border-radius: 0.75rem !important;
                            color: #1e293b !important;
                        }

                        /* ── Sidebar items ── */
                        .fi-sidebar-item {
                            background-color: transparent !important;
                            margin: 0.15rem 0.6rem !important;
                            border-radius: 0.5rem !important;
                            transition: all 0.2s ease !important;
                        }
                        .fi-sidebar-item:hover {
                            background-color: rgba(254,154,84,0.15) !important;
                            transform: translateX(3px) !important;
                        }
                        .fi-sidebar-item-active {
                            background: linear-gradient(135deg, #2a4795, #456aba) !important;
                            box-shadow: 0 4px 12px rgba(42,71,149,0.28) !important;
                        }
                        .fi-sidebar-item-active .fi-sidebar-item-label { color: #fe9a54 !important; }
                        .fi-sidebar-item-active .fi-sidebar-item-icon { color: #456aba !important; }
                        .fi-sidebar-item:not(.fi-sidebar-item-active) .fi-sidebar-item-icon { color: #f16b3f !important; }
                        .fi-sidebar-item:not(.fi-sidebar-item-active) .fi-sidebar-item-label { color: #2a4795 !important; }
                        
                        .fi-sidebar-item-label {
                            font-family: "Poppins", sans-serif !important;
                            font-size: 0.92rem !important;
                            font-weight: 600 !important;
                        }

                        /* ── Navigation group headings ── */
                        .fi-sidebar-group-label {
                            color: #f16b3f !important;
                            font-family: "Poppins", sans-serif !important;
                            font-weight: 700 !important;
                            font-size: 0.75rem !important;
                            letter-spacing: 0.08em !important;
                            text-transform: uppercase !important;
                        }

                        /* ── Primary buttons ── */
                        .fi-btn-primary {
                            background: linear-gradient(135deg, #f16b3f, #fe9a54) !important;
                            border: none !important;
                            box-shadow: 0 4px 12px rgba(241,107,63,0.30) !important;
                            color: #fff !important;
                        }
                        .fi-btn-primary:hover { background: linear-gradient(135deg, #d45a30, #f16b3f) !important; }

                        /* ── Badges / tags ── */
                        .fi-badge {
                            background-color: rgba(254,154,84,0.18) !important;
                            color: #f16b3f !important;
                            border: 1px solid rgba(241,107,63,0.25) !important;
                        }

                        /* ── Table header ── */
                        .fi-ta-header-cell {
                            background-color: #eef2ff !important;
                            color: #2a4795 !important;
                            font-family: "Poppins", sans-serif !important;
                            font-weight: 600 !important;
                        }

                        /* ── Inputs ── */
                        .fi-input-wrapper {
                            border-color: rgba(241,107,63,0.35) !important;
                            border-radius: 0.6rem !important;
                        }
                        .fi-input-wrapper:focus-within {
                            border-color: #f16b3f !important;
                            box-shadow: 0 0 0 3px rgba(241,107,63,0.18) !important;
                        }

                        /* ── FIXED Footer ── */
                        .fi-footer-custom {
                            position: fixed !important;
                            bottom: 0 !important;
                            left: 0 !important;
                            right: 0 !important;
                            width: 100vw !important;
                            z-index: 99999 !important;
                            display: flex !important;
                            align-items: center !important;
                            justify-content: space-between !important;
                            flex-wrap: wrap !important;
                            gap: 0.15rem !important;
                            padding: 0.6rem 2rem !important; /* Slightly more vertical padding */
                            background: linear-gradient(90deg, #2a4795 0%, #456aba 100%) !important;
                            color: #ffffff !important;
                            border-top: 2px solid #fe9a54 !important;
                            box-shadow: 0 -3px 14px rgba(42,71,149,0.22) !important;
                        }
                        .fi-footer-custom .footer-tagline {
                            font-family: "Lora", "Times New Roman", serif !important;
                            font-size: 0.88rem !important;
                            font-weight: 600 !important;
                            letter-spacing: 0.02em !important;
                            white-space: nowrap !important;
                            color: #ffffff !important;
                        }
                        .fi-footer-custom .footer-powered {
                            font-family: "Inter", sans-serif !important;
                            font-size: 0.78rem !important;
                            font-weight: 500 !important;
                            white-space: nowrap !important;
                            color: rgba(255,255,255,0.82) !important;
                        }
                        @media (max-width: 640px) {
                            .fi-footer-custom {
                                flex-direction: column !important;
                                align-items: center !important;
                                justify-content: center !important;
                                text-align: center !important;
                                padding: 0.5rem 1rem !important;
                                gap: 0.1rem !important;
                            }
                        }
                    </style>
                ')
            )

            // ── FOOTER ───────────────────────────────────────────────────
            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn(): string => Blade::render('
                    <div class="fi-footer-custom">
                        <span class="footer-tagline">&ldquo;Sathi Of Your Food Journey&rdquo; &mdash; AnnSathi.</span>
                        <span class="footer-powered">Powered By - Techstrota</span>
                    </div>
                ')
            );
    }
}