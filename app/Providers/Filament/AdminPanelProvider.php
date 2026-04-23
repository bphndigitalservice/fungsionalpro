<?php

namespace App\Providers\Filament;

use App\Filament\AvatarProviders\FungsionalProAvatarProvider;
use App\Filament\Pages\Authx\EmailVerificationPrompt;
use App\Filament\Pages\Authx\Register;
use App\Filament\Pages\Dashboard;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Kenepa\Banner\BannerPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('/')
            ->authGuard('web')
            ->login()
            ->registration(Register::class)
            ->emailVerification(EmailVerificationPrompt::class)
            ->passwordReset()
            ->profile(isSimple: false)
            ->colors([
                'danger' => Color::Rose,
                'gray' => Color::Gray,
                'info' => Color::Blue,
                'primary' => Color::Indigo,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label(__('labels.nav.client_menu'))
                    ->icon('heroicon-o-users'),
                NavigationGroup::make()
                    ->label(__('labels.nav.client_management'))
                    ->icon('heroicon-o-users'),
                NavigationGroup::make()
                    ->label(__('Verifikasi'))
                    ->icon('heroicon-o-check'),
                NavigationGroup::make()
                    ->label(__('labels.nav.client_point'))
                    ->icon('heroicon-o-star'),
                NavigationGroup::make()
                    ->label(__('labels.nav.reference'))
                    ->icon('heroicon-o-arrow-up-right'),
                NavigationGroup::make()
                    ->label(__('labels.nav.system'))
                    ->icon('heroicon-o-cog'),

            ])
            ->breadcrumbs()
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
                Authenticate::class
            ])->plugins([
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
                BannerPlugin::make()->persistsBannersInDatabase()
                    ->navigationGroup(__("labels.nav.system"))
                    ->bannerManagerAccessPermission('banner-manager'),
            ])
            ->defaultThemeMode(ThemeMode::Light)
            ->databaseTransactions()
            ->unsavedChangesAlerts()
            ->databaseNotifications()
            ->defaultAvatarProvider(FungsionalProAvatarProvider::class)
            ->globalSearch(false)
            ->renderHook(PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, fn() => view('filament.components.footer'));

    }
}
