<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Resources\Pages\Page;
use App\Models\Sale;
use App\Models\Setting;
use Filament\Actions\Action;
use Torgodly\Html2Media\Actions\Html2MediaAction;

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

    // public function getTitle(): string
    // {
    //     return 'Imprimer la vente #' . $this->record->invoice_number;
    // }

    public function getSettings()
    {
        return Setting::first();
    }

    protected function getHeaderActions(): array
    {
        return [
            // Action::make('print')
            //     ->label(__('Print'))
            //     ->icon('heroicon-o-printer')
            //     ->color('info')
            //     ->requiresConfirmation()
            //     ->modalHeading(__('Print Sale'))
            //     ->modalDescription(__('Are you sure you want to print this sale?'))
            //     ->modalSubmitActionLabel(__('Print'))
            //     ->modalCancelActionLabel(__('Cancel')),
            // ->url(fn() => route('sale.print', $this->record)),
            Html2MediaAction::make('print')
                // ->scale(2)
                ->print() // Enable print option
                ->preview() // Enable preview option
                ->filename('invoice') // Custom file name
                ->savePdf() // Enable save as PDF option
                ->requiresConfirmation() // Show confirmation modal
                ->pagebreak('section', ['css', 'legacy'])
                ->orientation('portrait') // Portrait orientation
                ->format('a5', 'mm') // A4 format with mm units
                ->enableLinks(false) // Enable links in PDF
                // ->margin([0, 50, 0, 50]) // Set custom margins
                ->content(fn() => view('sales.print', ['sale' => $this->record, 'settings' => Setting::first()])), // Set content
            Action::make('back')
                ->label(__('Back'))
                ->icon('heroicon-o-arrow-left')
                ->url(fn() => SaleResource::getUrl('index')),
        ];
    }
}
