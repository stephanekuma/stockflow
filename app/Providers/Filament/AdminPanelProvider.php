<?php

namespace App\Providers\Filament;

use App\Filament\Pages\ProfilePage;
use App\Filament\Pages\StockHistoryPage;
use App\Models\Store;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
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
use Jeffgreco13\FilamentBreezy\BreezyCore;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('store')
            ->login()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
                \App\Filament\Widgets\StockManagementOverview::class,
                \App\Filament\Widgets\ExpiryAlertsWidget::class,
            ])
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
            ->tenant(Store::class)
            ->databaseNotifications()
            ->navigationGroups([
                NavigationGroup::make()
                    ->label(__('Products Management'))
                    ->icon('heroicon-s-cube'),
                NavigationGroup::make()
                    ->label(__('Transactions'))
                    ->icon('heroicon-s-shopping-cart'),
                NavigationGroup::make()
                    ->label(__('Business Entities'))
                    ->icon('heroicon-s-briefcase'),
                NavigationGroup::make()
                    ->label(__('Settings'))
                    ->icon('heroicon-s-cog'),
            ])
            ->plugins([
                BreezyCore::make()
                    ->myProfile(
                        shouldRegisterUserMenu: true,
                        shouldRegisterNavigation: true,
                        navigationGroup: __('Settings'),
                    )
                    ->enableTwoFactorAuthentication(
                        force: true,
                    )
                    ->customMyProfilePage(ProfilePage::class),
            ])
            ->sidebarCollapsibleOnDesktop()
            ->collapsedSidebarWidth('9rem');
    }
}
