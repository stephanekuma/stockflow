<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class ExpiryAlertsWidget extends Widget
{
    protected static string $view = 'filament.widgets.expiry-alerts-widget';

    protected int | string | array $columnSpan = 'full';

    public function getExpiredProductsCount(): int
    {
        return Product::expired()->count();
    }

    public function getExpiringSoonProductsCount(): int
    {
        return Product::expiringSoon()->count();
    }

    public function getExpiredProducts(): \Illuminate\Database\Eloquent\Collection
    {
        return Product::expired()
            ->with(['category', 'brand'])
            ->orderBy('expiry_date', 'asc')
            ->limit(5)
            ->get();
    }

    public function getExpiringSoonProducts(): \Illuminate\Database\Eloquent\Collection
    {
        return Product::expiringSoon()
            ->with(['category', 'brand'])
            ->orderBy('expiry_date', 'asc')
            ->limit(5)
            ->get();
    }

    public function getTotalPerishableProducts(): int
    {
        return Product::perishable()->count();
    }
}
