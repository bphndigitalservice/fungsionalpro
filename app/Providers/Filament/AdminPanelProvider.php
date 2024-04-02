<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('/')
            ->login()
            ->registration()
            ->emailVerification()
            ->passwordReset()
            ->profile(isSimple: false)
            ->colors([
                'primary' => Color::Fuchsia,
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label(__('labels.nav.client_menu'))
                    ->icon('clarity-employee-line'),
                NavigationGroup::make()
                    ->label(__('labels.nav.client_management'))
                    ->icon('heroicon-o-users'),
                NavigationGroup::make()
                    ->label(__('Verifikasi'))
                    ->icon('fluentui-checkbox-arrow-right-20'),
                NavigationGroup::make()
                    ->label(__('labels.nav.client_point'))
                    ->icon('iconpark-credit'),
                NavigationGroup::make()
                    ->label(__('labels.nav.reference'))
                    ->icon('heroicon-o-arrow-up-right'),
                NavigationGroup::make()
                    ->label(__('labels.nav.system'))
                    ->icon('heroicon-o-cog'),

            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
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
            ])->plugins([
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
            ])
            ->defaultThemeMode(ThemeMode::Dark)
            ->databaseTransactions()
            ->unsavedChangesAlerts();
    }
}
