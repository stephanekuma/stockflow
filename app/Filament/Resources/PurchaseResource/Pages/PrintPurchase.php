<?php

namespace App\Filament\Resources\PurchaseResource\Pages;

use App\Models\Setting;
use App\Models\Purchase;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use App\Filament\Resources\PurchaseResource;
use Illuminate\Database\Eloquent\Collection;

class PrintPurchase extends Page
{
    protected static string $resource = PurchaseResource::class;

    protected static string $view = 'filament.resources.purchase-resource.pages.print-purchase';

    public ?Purchase $record = null;
    public ?Purchase $purchase = null;
    public ?Setting $settings = null;

    public function mount(Purchase $record): void
    {
        $this->record = $record;
        $this->purchase = Purchase::query()
            ->with(['purchasedProducts.productUnit.product', 'purchasedProducts.productUnit.unit', 'provider', 'store'])
            ->find($record->id);

        $this->settings = Setting::first();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label(__('Print'))
                ->icon('heroicon-o-printer')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading(__('Print Purchase'))
                ->modalDescription(__('Are you sure you want to print this purchase?'))
                ->modalSubmitActionLabel(__('Print'))
                ->modalCancelActionLabel(__('Cancel'))
                ->url(fn() => route('purchase.print', $this->record)),
            Action::make('back')
                ->label(__('Back'))
                ->icon('heroicon-o-arrow-left')
                ->url(fn() => PurchaseResource::getUrl('index')),
        ];
    }
}
