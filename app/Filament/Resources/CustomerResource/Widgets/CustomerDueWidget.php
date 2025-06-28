<?php

namespace App\Filament\Resources\CustomerResource\Widgets;

use Filament\Widgets\Widget;
use App\Models\Customer;

class CustomerDueWidget extends Widget
{
    protected static string $view = 'filament.resources.customer-resource.widgets.customer-due-widget';
    public Customer $record;

    public function getDueAmount(): float
    {
        // Somme des ventes non soldées
        return $this->record->sales()->get()->sum(fn($sale) => $sale->amount_due);
    }

    protected function getViewData(): array
    {
        return [
            'due' => $this->getDueAmount(),
        ];
    }
}
