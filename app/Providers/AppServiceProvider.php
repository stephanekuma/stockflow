<?php

namespace App\Providers;

use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;
use BezhanSalleh\PanelSwitch\PanelSwitch;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        PanelSwitch::configureUsing(function (PanelSwitch $panelSwitch) {
            $panelSwitch
                ->canSwitchPanels(fn() => auth()->user()->isAdmin())
                ->modalHeading(__('Available Panels'))
                ->modalWidth('sm')
                ->slideOver()
                ->labels([
                    'admin' => __('Store'),
                    'storeManager' => __('Stores Manager')
                ])
                ->icons([
                    'admin' => 'heroicon-o-building-storefront',
                    'storeManager' => 'heroicon-o-adjustments-horizontal',
                ], $asImage = false)
                ->iconSize(20);
        });

        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch
                ->locales([
                    'en',
                    'fr'
                ]);
        });
    }
}
