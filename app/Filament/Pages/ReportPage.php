<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\CustomerDeposit;
use App\Models\StockHistory;
use App\Models\Purchase;
use App\Models\Customer;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Builder;

class ReportPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $view = 'filament.pages.report-page';
    protected static ?string $navigationGroup = 'Rapports';
    protected static ?string $title = 'Rapports';
    protected static ?int $navigationSort = 999;

    public $filters = [
        'date_from' => null,
        'date_to' => null,
        'customer_id' => null,
        'provider_id' => null,
        'type' => null,
    ];

    public $results = [];

    public function mount(): void
    {
        $this->applyFilters();
    }

    public function applyFilters(): void
    {
        $type = $this->filters['type'] ?? 'sales';
        $dateFrom = $this->filters['date_from'];
        $dateTo = $this->filters['date_to'];
        $customerId = $this->filters['customer_id'];
        $providerId = $this->filters['provider_id'];

        if ($type === 'sales') {
            $query = Sale::query();
            if ($dateFrom) $query->whereDate('sold_at', '>=', $dateFrom);
            if ($dateTo) $query->whereDate('sold_at', '<=', $dateTo);
            if ($customerId) $query->where('customer_id', $customerId);
            $this->results['sales'] = $query->with('customer')->get();
        } elseif ($type === 'payments') {
            $query = SalePayment::query();
            if ($dateFrom) $query->whereDate('paid_at', '>=', $dateFrom);
            if ($dateTo) $query->whereDate('paid_at', '<=', $dateTo);
            if ($customerId) $query->where('customer_id', $customerId);
            $this->results['payments'] = $query->with('customer', 'sale')->get();
        } elseif ($type === 'deposits') {
            $query = CustomerDeposit::query();
            if ($dateFrom) $query->whereDate('deposited_at', '>=', $dateFrom);
            if ($dateTo) $query->whereDate('deposited_at', '<=', $dateTo);
            if ($customerId) $query->where('customer_id', $customerId);
            $this->results['deposits'] = $query->with('customer')->get();
        } elseif ($type === 'purchases') {
            $query = Purchase::query();
            if ($dateFrom) $query->whereDate('purchased_at', '>=', $dateFrom);
            if ($dateTo) $query->whereDate('purchased_at', '<=', $dateTo);
            if ($providerId) $query->where('provider_id', $providerId);
            $this->results['purchases'] = $query->with('provider')->get();
        } elseif ($type === 'stock') {
            $query = StockHistory::query();
            if ($dateFrom) $query->whereDate('created_at', '>=', $dateFrom);
            if ($dateTo) $query->whereDate('created_at', '<=', $dateTo);
            $this->results['stock'] = $query->with('productUnit.product')->get();
        }
    }

    protected function getViewData(): array
    {
        // Statistiques produits les plus/moins vendus
        $topProducts = \App\Models\SoldProduct::selectRaw('product_unit_id, SUM(quantity) as total_quantity')
            ->groupBy('product_unit_id')
            ->orderByDesc('total_quantity')
            ->with(['productUnit.product'])
            ->limit(5)
            ->get();
        $bottomProducts = \App\Models\SoldProduct::selectRaw('product_unit_id, SUM(quantity) as total_quantity')
            ->groupBy('product_unit_id')
            ->orderBy('total_quantity')
            ->with(['productUnit.product'])
            ->limit(5)
            ->get();
        // Statistiques CA par produit (top 5)
        $topCAProducts = \App\Models\SoldProduct::selectRaw('product_unit_id, SUM(total) as total_ca')
            ->groupBy('product_unit_id')
            ->orderByDesc('total_ca')
            ->with(['productUnit.product'])
            ->limit(5)
            ->get();
        // Statistiques évolution mensuelle du CA (12 derniers mois)
        $monthlyCA = \App\Models\Sale::selectRaw('DATE_FORMAT(sold_at, "%Y-%m") as month, SUM(total) as ca')
            ->where('sold_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        $months = collect(range(0, 11))->map(function ($i) {
            return now()->subMonths(11 - $i)->format('Y-m');
        });
        $monthlyCAData = $months->map(function ($month) use ($monthlyCA) {
            $found = $monthlyCA->firstWhere('month', $month);
            return $found ? (float)$found->ca : 0;
        });
        return [
            'filters' => $this->filters,
            'results' => $this->results,
            'customers' => Customer::all(),
            'providers' => Provider::all(),
            'topProducts' => $topProducts,
            'bottomProducts' => $bottomProducts,
            'topProductsChart' => [
                'labels' => $topProducts->map(fn($row) => $row->productUnit && $row->productUnit->product ? $row->productUnit->product->name : 'Inconnu'),
                'data' => $topProducts->pluck('total_quantity'),
            ],
            'bottomProductsChart' => [
                'labels' => $bottomProducts->map(fn($row) => $row->productUnit && $row->productUnit->product ? $row->productUnit->product->name : 'Inconnu'),
                'data' => $bottomProducts->pluck('total_quantity'),
            ],
            'topCAProducts' => $topCAProducts,
            'topCAProductsChart' => [
                'labels' => $topCAProducts->map(fn($row) => $row->productUnit && $row->productUnit->product ? $row->productUnit->product->name : 'Inconnu'),
                'data' => $topCAProducts->pluck('total_ca'),
            ],
            'monthlyCAChart' => [
                'labels' => $months,
                'data' => $monthlyCAData,
            ],
        ] + parent::getViewData();
    }
}
