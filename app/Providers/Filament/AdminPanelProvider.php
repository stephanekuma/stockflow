<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use App\Models\Store;
use Filament\Widgets;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use App\Filament\Pages\ProfilePage;
use App\Filament\Pages\StockHistoryPage;
use Filament\Navigation\NavigationGroup;
use Filament\Http\Middleware\Authenticate;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use App\Filament\Widgets\ExpiryAlertsWidget;
use App\Filament\Widgets\InventoryStatsWidget;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use App\Filament\Widgets\StockManagementOverview;
use Filament\Http\Middleware\AuthenticateSession;
use App\Filament\Widgets\CustomerDebtsSummaryWidget;
use App\Filament\Widgets\SupplierDebtsSummaryWidget;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Boquizo\FilamentLogViewer\FilamentLogViewerPlugin;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

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
                // StockManagementOverview::class,
                // ExpiryAlertsWidget::class,
                CustomerDebtsSummaryWidget::class,
                SupplierDebtsSummaryWidget::class,
                InventoryStatsWidget::class,
                \App\Filament\Widgets\MonthlyExpensesWidget::class,
                \App\Filament\Widgets\ExpensesByCategoryChart::class,
                \App\Filament\Widgets\RegisterShortcutWidget::class,
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
                    ->label(__('Logs'))
                    ->icon('heroicon-s-list-bullet'),
                NavigationGroup::make()
                    ->label(__('Products Management'))
                    ->icon('heroicon-s-cube'),
                NavigationGroup::make()
                    ->label(__('Stock Management'))
                    ->icon('heroicon-s-inbox-stack'),
                NavigationGroup::make()
                    ->label(__('Expense Management'))
                    ->icon('heroicon-s-banknotes'),
                NavigationGroup::make()
                    ->label(__('Finances'))
                    ->icon('heroicon-s-currency-dollar'),
                NavigationGroup::make()
                    ->label(__('Transactions'))
                    ->icon('heroicon-s-shopping-cart'),
                NavigationGroup::make()
                    ->label(__('Reports'))
                    ->icon('heroicon-s-chart-bar'),
                NavigationGroup::make()
                    ->label(__('Business Entities'))
                    ->icon('heroicon-s-briefcase'),
                NavigationGroup::make()
                    ->label(__('Store Settings'))
                    ->icon('heroicon-s-cog'),
            ])
            ->plugins([
                BreezyCore::make()
                    ->myProfile(
                        shouldRegisterUserMenu: true,
                        shouldRegisterNavigation: true,
                        navigationGroup: __('Store Settings'),
                    )
                    ->enableTwoFactorAuthentication(
                        force: true,
                    )
                    ->customMyProfilePage(ProfilePage::class),
                FilamentLogViewerPlugin::make(),
            ])
            ->sidebarCollapsibleOnDesktop()
            ->collapsedSidebarWidth('9rem');
    }
}
