<?php

namespace App\Filament\Widgets;

use App\Models\ProductUnit;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InventoryStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalStock = ProductUnit::sum('quantity');
        $totalPurchaseValue = ProductUnit::selectRaw('SUM(quantity * cost_price) as total')->value('total');
        $totalSaleValue = ProductUnit::selectRaw('SUM(quantity * price) as total')->value('total');
        // $totalPurchased = ...; // À adapter selon ta structure d'achats

        return [
            Stat::make(__('Total stock'), $totalStock)
                ->description(__('Total produits en stock'))
                ->descriptionIcon('heroicon-m-cube')
                ->color('blue'),
            Stat::make(__('Valeur achat'), number_format($totalPurchaseValue ?? 0, 0, ',', ' ') . ' XOF')
                ->description(__('Valeur d\'achat totale'))
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('green'),
            Stat::make(__('Valeur vente'), number_format($totalSaleValue ?? 0, 0, ',', ' ') . ' XOF')
                ->description(__('Valeur de vente totale'))
                ->descriptionIcon('heroicon-m-tag')
                ->color('yellow'),
            Stat::make(__('Total achetés'), 0)
                ->description(__('Total produits achetés'))
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('purple'),
        ];
    }
}
