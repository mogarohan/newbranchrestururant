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
            ->brandLogo(asset('img/TsLogo.png'))
            ->brandLogoHeight('10.5rem')
            ->colors([
                'primary' => '#F47D20', // Orange as primary
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
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn(): string => Blade::render('
                    @vite([\'resources/js/app.js\'])
                    <link rel="preconnect" href="https://fonts.googleapis.com">
                    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
                    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
                    <style>
                        /* --- GLOBAL FONTS --- */
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

                        /* ==========================================================
                           ☀️ LIGHT MODE (CLEAN FULL LIGHT BACKGROUND)
                           ========================================================== */
                        
                        .fi-main, .fi-sidebar { 
                            background-color: #f8fafc !important; 
                            background-image: none !important;
                        }

                        .fi-topbar {
                            background-color: #ffffff !important;
                            border-bottom: 1px solid #e2e8f0 !important;
                        }

                        .fi-wi-stats-overview-stat, .fi-section, .fi-ta-record, .fi-wi-chart {
                            background-color: #ffffff !important;
                            border: 1px solid #e2e8f0 !important;
                            box-shadow: 0 1px 3px #F47D20 !important;
                            border-radius: 0.75rem !important;
                        }

                        /* ==========================================================
                           🚀 SIDEBAR LABELS (CLEAN BRANDING)
                           ========================================================== */
                        
                        .fi-sidebar-item {
                            background-color: transparent !important;
                            margin-bottom: 0.25rem !important;
                            margin-left: 0.75rem !important;
                            margin-right: 0.75rem !important;
                            border-radius: 0.5rem !important;
                            transition: all 0.2s ease !important;
                        }

                        /* Hover Effect - Light Orange tint */
                        .fi-sidebar-item:hover {
                            background-color: rgba(32, 127, 244, 0.1) !important; /* Orange tint on hover */
                            transform: translateX(3px) !important;
                        }

                        /* ACTIVE ITEM - Fully Orange #F47D20 */
                        .fi-sidebar-item-active {
                            background-color: #F47D20 !important;
                            box-shadow: 0 4px 10px rgba(244, 125, 32, 0.3) !important;
                        }

                        .fi-sidebar-item-active .fi-sidebar-item-label,
                        .fi-sidebar-item-active .fi-sidebar-item-icon {
                            color: #3B82F6 !important; /* White text on active orange */
                        }

                        /* Non-active Icon Color - Orange Branding */
                        .fi-sidebar-item:not(.fi-sidebar-item-active) .fi-sidebar-item-icon {
                            color: #F47D20 !important;
                        }

                        .fi-sidebar-item-label {
                            font-family: "Poppins", sans-serif !important;
                            font-size: 0.95rem !important;
                            font-weight: 600 !important;
                        }

                        /* ==========================================================
                           🌙 DARK MODE (EXACT MATCH FOR dark:bg-gray-900)
                           ========================================================== */
                        .dark .fi-main, .dark .fi-sidebar, .dark .fi-topbar { 
                            background-color: #111827 !important; /* Tailwind gray-900 color */
                            background-image: none !important;
                        }

                        .dark .fi-wi-stats-overview-stat, .dark .fi-section, .dark .fi-ta-record {
                            background-color: rgba(255, 255, 255, 0.03) !important;
                            border: 1px solid rgba(255, 255, 255, 0.05) !important;
                            backdrop-filter: blur(10px);
                        }

                        .dark .fi-sidebar-item-active {
                            background-color: #F47D20 !important;
                            border: none !important;
                        }
                        
                        .dark .fi-sidebar-item-active .fi-sidebar-item-label {
                            color: #3B82F6 !important;
                        }
                    </style>
                ')
            );
    }
}