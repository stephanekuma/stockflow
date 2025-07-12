<?php

namespace App\Filament\Resources\ProviderPaymentResource\Pages;

use App\Filament\Resources\ProviderPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Services\CashRegisterService;
use Filament\Notifications\Notification;

class CreateProviderPayment extends CreateRecord
{
    protected static string $resource = ProviderPaymentResource::class;

    protected function afterCreate(): void
    {
        $payment = $this->record;

        try {
            CashRegisterService::recordTransaction(
                null, // storeId - will be auto-detected
                type: 'provider-payment',
                amount: -$payment->amount,
                referenceId: $payment->id,
                description: 'Paiement fournisseur #' . $payment->provider->name
            );

            Notification::make()
                ->title('Paiement fournisseur enregistré')
                ->body('Le paiement fournisseur a été enregistré et la transaction de caisse créée.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur caisse')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
