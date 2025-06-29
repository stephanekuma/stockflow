<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\StockReturn;
use App\Models\StockLoss;
use App\Models\StockHistory;
use Illuminate\Support\Carbon;
use Filament\Facades\Filament;

class StockManagementOverview extends Widget
{
    protected static string $view = 'filament.widgets.stock-management-overview';

    protected int $returnsCount = 0;
    protected float $returnsTotal = 0;
    protected int $lossesCount = 0;
    protected float $lossesTotal = 0;
    protected int $historiesCount = 0;
    protected array $recentMovements = [];

    public function mount()
    {
        $storeId = Filament::getTenant()?->id;
        $from = now()->subDays(7);

        $returns = StockReturn::where('store_id', $storeId)
            ->where('returned_at', '>=', $from)
            ->get();
        $this->returnsCount = $returns->count();
        $this->returnsTotal = $returns->sum('total_refund');

        $losses = StockLoss::where('store_id', $storeId)
            ->where('lost_at', '>=', $from)
            ->get();
        $this->lossesCount = $losses->count();
        $this->lossesTotal = $losses->sum('total_loss');

        $histories = StockHistory::whereHas('productUnit', function ($q) use ($storeId) {
            $q->where('store_id', $storeId);
        })
            ->where('created_at', '>=', $from)
            ->get();
        $this->historiesCount = $histories->count();

        // Récupère les 10 derniers mouvements (retour, perte, historique)
        $recentReturns = $returns->map(function ($r) {
            return [
                'type' => 'Retour',
                'date' => $r->returned_at,
                'user' => $r->customer?->name,
                'details' => $r->return_number,
                'amount' => $r->total_refund,
            ];
        });
        $recentLosses = $losses->map(function ($l) {
            return [
                'type' => 'Perte',
                'date' => $l->lost_at,
                'user' => $l->user?->name,
                'details' => $l->loss_number,
                'amount' => $l->total_loss,
            ];
        });
        $recentHistories = $histories->map(function ($h) {
            return [
                'type' => ucfirst($h->type),
                'date' => $h->created_at,
                'user' => $h->user?->name,
                'details' => ($h->productUnit?->product?->name ?? '') . ' (' . ($h->productUnit?->unit?->name ?? '') . ')',
                'amount' => $h->quantity_change,
            ];
        });
        $all = $recentReturns->concat($recentLosses)->concat($recentHistories)->sortByDesc('date')->take(10)->values();
        $this->recentMovements = $all->toArray();
    }

    public function getReturnsCount(): int
    {
        return $this->returnsCount;
    }
    public function getReturnsTotal(): float
    {
        return $this->returnsTotal;
    }
    public function getLossesCount(): int
    {
        return $this->lossesCount;
    }
    public function getLossesTotal(): float
    {
        return $this->lossesTotal;
    }
    public function getHistoriesCount(): int
    {
        return $this->historiesCount;
    }
    public function getRecentMovements(): array
    {
        return $this->recentMovements;
    }

    public static function canView(): bool
    {
        return true;
    }
}
