<?php

namespace App\Filament\Widgets;

use App\Models\ProviderDebt;
use Filament\Widgets\Widget;

class SupplierDebtsSummaryWidget extends Widget
{
    protected static string $view = 'filament.widgets.supplier-debts-summary-widget';
    protected static ?int $sort = 2;
    protected static ?string $pollingInterval = null;

    public $totalDebt = 0;
    public $totalPaid = 0;
    public $totalRemaining = 0;

    public function mount(): void
    {
        $this->totalDebt = ProviderDebt::sum('amount');
        $this->totalPaid = ProviderDebt::sum('paid');
        $this->totalRemaining = $this->totalDebt - $this->totalPaid;
    }

    public static function canView(): bool
    {
        return true;
    }
}
