<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Filament\Widgets\BarChartWidget;

class ExpensesByCategoryChart extends BarChartWidget
{
    protected static ?string $heading = 'Expenses by Category (This Month)';

    protected function getData(): array
    {
        $categories = ExpenseCategory::pluck('name', 'id');
        $data = [];
        foreach ($categories as $id => $name) {
            $data[] = Expense::where('expense_category_id', $id)
                ->whereMonth('date', now()->month)
                ->whereYear('date', now()->year)
                ->sum('amount');
        }
        return [
            'datasets' => [
                [
                    'label' => __('Expenses'),
                    'data' => $data,
                    'backgroundColor' => '#f87171',
                ],
            ],
            'labels' => $categories->values()->toArray(),
        ];
    }
}
