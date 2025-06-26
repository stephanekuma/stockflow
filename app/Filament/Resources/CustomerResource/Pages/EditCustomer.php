<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Facades\Filament;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        $customer = $this->record;
        $oldestDueSale = $customer->sales()->whereRaw('total > (select coalesce(sum(amount),0) from sale_payments where sale_id = sales.id)')->orderBy('sold_at')->first();
        $saleId = $oldestDueSale ? $oldestDueSale->id : null;
        $tenant = Filament::getTenant();
        $tenantKey = $tenant ? $tenant->getKey() : null;
        return [
            \Filament\Actions\Action::make('add_payment')
                ->label('Ajouter un paiement')
                ->icon('heroicon-o-currency-dollar')
                ->color('success')
                ->url(
                    $saleId
                        ? route('filament.admin.resources.sale-payments.create', ['tenant' => $tenantKey, 'sale_id' => $saleId, 'customer_id' => $customer->id])
                        : route('filament.admin.resources.sale-payments.create', ['tenant' => $tenantKey, 'customer_id' => $customer->id])
                )
                ->openUrlInNewTab(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }
}
