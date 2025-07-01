<?php

namespace App\Filament\Widgets;

use App\Models\CustomerDebt;
use Filament\Widgets\Widget;
use Filament\Facades\Filament;

class CustomerDebtsSummaryWidget extends Widget
{
    protected static string $view = 'filament.widgets.customer-debts-summary-widget';
    protected static ?int $sort = 3;
    protected static ?string $pollingInterval = null;

    public $totalDebt = 0;
    public $totalPaid = 0;
    public $totalRemaining = 0;
    public $overdueDebts = 0;

    public function mount(): void
    {
        $storeId = Filament::getTenant()->id;

        $this->totalDebt = CustomerDebt::where('store_id', $storeId)->sum('amount');
        $this->totalPaid = CustomerDebt::where('store_id', $storeId)->sum('paid');
        $this->totalRemaining = $this->totalDebt - $this->totalPaid;
        $this->overdueDebts = CustomerDebt::where('store_id', $storeId)
            ->where('due_date', '<', now())
            ->where('status', '!=', 'paid')
            ->count();
    }

    public static function canView(): bool
    {
        return true;
    }
}
