<?php

namespace App\Filament\Widgets;

use Filament\Widgets\LineChartWidget;
use Filament\Facades\Filament;
use App\Models\Sale;
use Filament\Widgets\ChartWidget;

class SalesTrendsChart extends ChartWidget
{
    protected static ?string $heading = 'Évolution des ventes (30 jours)';

    protected function getData(): array
    {
        $storeId = Filament::getTenant()->id;

        $dates = collect(range(0, 29))->map(fn($i) => now()->subDays($i)->format('Y-m-d'))->reverse();
        $sales = Sale::where('store_id', $storeId)
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->get()
            ->groupBy(fn($sale) => $sale->created_at->format('Y-m-d'));
        return [
            'datasets' => [
                [
                    'label' => 'Ventes',
                    'data' => $dates->map(fn($date) => isset($sales[$date]) ? $sales[$date]->count() : 0)->toArray(),
                ],
            ],
            'labels' => $dates->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
