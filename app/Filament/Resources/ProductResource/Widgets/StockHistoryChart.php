<?php

namespace App\Filament\Resources\ProductResource\Widgets;

use Filament\Widgets\Widget;
use App\Models\Product;
use App\Models\StockHistory;
use Filament\Widgets\Concerns\CanPoll;

class StockHistoryChart extends Widget
{
    use CanPoll;

    protected static string $view = 'filament.resources.product-resource.widgets.stock-history-chart';
    public ?Product $record = null;

    public function getStockHistoryData(): array
    {
        if (!$this->record) {
            return [];
        }
        $histories = StockHistory::whereHas('productUnit', function ($q) {
                $q->where('product_id', $this->record->id);
            })
            ->orderBy('created_at')
            ->get();

        $data = [];
        $current = null;
        foreach ($histories as $history) {
            $current = $history->quantity_after;
            $data[] = [
                'date' => $history->created_at->format('Y-m-d H:i'),
                'stock' => $current,
            ];
        }
        return $data;
    }

    protected function getViewData(): array
    {
        return [
            'data' => $this->getStockHistoryData(),
        ];
    }
}
