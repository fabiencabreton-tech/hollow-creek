<?php

namespace App\Providers\Filament;

use App\Filament\Support\InitialsAvatarProvider;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureStaffHasTwoFactor;
use App\Http\Middleware\SecurityHeaders;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Panneau d'administration (/admin), réservé à l'équipe.
 * La connexion se fait sur la page de connexion du site (avec double authentification) :
 * l'administration n'a pas d'entrée séparée qui permettrait de la contourner.
 */
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('Tsukimori · Administration')
            ->favicon(asset('favicon.svg'))
            ->darkMode(true, true)
            ->colors([
                // Vermillon du sceau (shu) et gris ardoise du lac dans la brume, comme le site.
                'primary' => [
                    50 => 'oklch(0.97 0.012 35)',
                    100 => 'oklch(0.94 0.026 35)',
                    200 => 'oklch(0.89 0.05 35)',
                    300 => 'oklch(0.81 0.08 35)',
                    400 => 'oklch(0.72 0.1 35)',
                    500 => 'oklch(0.62 0.12 35)',
                    600 => 'oklch(0.53 0.125 35)',
                    700 => 'oklch(0.45 0.115 35)',
                    800 => 'oklch(0.38 0.1 35)',
                    900 => 'oklch(0.32 0.085 35)',
                    950 => 'oklch(0.22 0.06 35)',
                ],
                'gray' => [
                    50 => 'oklch(0.985 0.003 230)',
                    100 => 'oklch(0.965 0.004 230)',
                    200 => 'oklch(0.92 0.006 230)',
                    300 => 'oklch(0.87 0.008 230)',
                    400 => 'oklch(0.72 0.012 230)',
                    500 => 'oklch(0.56 0.014 230)',
                    600 => 'oklch(0.45 0.014 230)',
                    700 => 'oklch(0.37 0.014 230)',
                    800 => 'oklch(0.28 0.013 230)',
                    900 => 'oklch(0.215 0.012 230)',
                    950 => 'oklch(0.17 0.01 230)',
                ],
                'warning' => Color::Amber,
            ])
            ->defaultAvatarProvider(InitialsAvatarProvider::class)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->navigationGroups([
                'Validation',
                'Jeu',
                'Personnages',
                'Communauté',
                'Univers',
                'Gazette',
                'Immersion',
                'Contenus',
                'Site',
            ])
            ->navigationItems([
                NavigationItem::make('Retour au site')
                    ->url(fn (): string => route('home'))
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->sort(100),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SecurityHeaders::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureAccountIsActive::class,
                EnsureStaffHasTwoFactor::class,
            ]);
    }
}
