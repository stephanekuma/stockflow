<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Resources\Pages\Page;
use App\Models\Sale;
use App\Models\Setting;

class PrintSale extends Page
{
    protected static string $resource = SaleResource::class;

    protected static string $view = 'filament.resources.sale-resource.pages.print-sale';

    public Sale $record;

    public function mount(Sale $record): void
    {
        $this->record = $record->load([
            'customer',
            'store',
            'soldProducts.productUnit.product',
            'soldProducts.productUnit.unit',
            'soldProducts.pack.packProducts.productUnit.product',
            'soldProducts.pack.packProducts.productUnit.unit'
        ]);
    }

    public function getTitle(): string
    {
        return 'Imprimer la vente #' . $this->record->invoice_number;
    }

    public function getSettings()
    {
        return Setting::first();
    }
}
