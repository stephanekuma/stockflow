<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MonthlyExpensesWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $total = Expense::whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('amount');

        return [
            Stat::make(__('Total expenses this month'), number_format($total, 0, ',', ' ') . ' XOF')
                ->description(__('All store expenses for the current month'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('red'),
        ];
    }
}
