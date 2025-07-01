<?php

namespace App\Filament\Resources\ProformaInvoiceResource\Pages;

use App\Filament\Resources\ProformaInvoiceResource;
use Filament\Resources\Pages\Page;
use App\Models\ProformaInvoice;
use App\Models\Setting;

class PrintProformaInvoice extends Page
{
    protected static string $resource = ProformaInvoiceResource::class;

    protected static string $view = 'filament.resources.proforma-invoice-resource.pages.print-proforma-invoice';

    public ProformaInvoice $record;

    public function mount(ProformaInvoice $record): void
    {
        $this->record = $record->load([
            'customer',
            'store',
            'items.productUnit.product',
            'items.productUnit.unit',
            'items.pack'
        ]);
    }

    public function getTitle(): string
    {
        return __('Print Proforma Invoice') . ' - ' . $this->record->invoice_number;
    }

    public function getSubheading(): ?string
    {
        return $this->record->customer->name;
    }
}
