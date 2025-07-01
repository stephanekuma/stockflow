<?php

namespace App\Filament\Resources\ProviderPaymentResource\Pages;

use App\Filament\Resources\ProviderPaymentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProviderPayment extends CreateRecord
{
    protected static string $resource = ProviderPaymentResource::class;

    protected function afterCreate(): void
    {
        // Mettre à jour le montant payé de la dette
        $payment = $this->record;
        if ($payment->provider_debt_id) {
            $debt = \App\Models\ProviderDebt::find($payment->provider_debt_id);
            if ($debt) {
                $debt->paid += $payment->amount;

                // Mettre à jour le statut de la dette
                if ($debt->paid >= $debt->amount) {
                    $debt->status = 'paid';
                } elseif ($debt->paid > 0) {
                    $debt->status = 'partial';
                } else {
                    $debt->status = 'unpaid';
                }

                $debt->save();
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
