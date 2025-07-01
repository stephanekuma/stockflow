<?php

namespace App\Filament\Resources\ProviderPaymentResource\Pages;

use App\Filament\Resources\ProviderPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProviderPayment extends EditRecord
{
    protected static string $resource = ProviderPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        // Recalculer le montant payé de la dette
        $payment = $this->record;
        if ($payment->provider_debt_id) {
            $debt = \App\Models\ProviderDebt::find($payment->provider_debt_id);
            if ($debt) {
                // Recalculer le total payé pour cette dette
                $totalPaid = \App\Models\ProviderPayment::where('provider_debt_id', $debt->id)
                    ->sum('amount');

                $debt->paid = $totalPaid;

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
