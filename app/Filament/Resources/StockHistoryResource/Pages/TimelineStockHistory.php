<?php

namespace App\Filament\Resources\StockHistoryResource\Pages;

use App\Filament\Resources\StockHistoryResource;
use App\Models\StockHistory;
use Filament\Resources\Pages\Page;

class TimelineStockHistory extends Page
{
    protected static string $resource = StockHistoryResource::class;
    protected static string $view = 'filament.resources.stock-history-resource.pages.timeline-stock-history';

    public $histories;

    public function mount(): void
    {
        $this->histories = StockHistory::with(['productUnit.product', 'productUnit.unit', 'user'])
            ->orderByDesc('created_at')
            ->get();
    }
}
