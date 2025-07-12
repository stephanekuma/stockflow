<?php

namespace App\Filament\Resources\PayrollResource\Pages;

use App\Filament\Resources\PayrollResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Services\CashRegisterService;
use Filament\Notifications\Notification;

class CreatePayroll extends CreateRecord
{
    protected static string $resource = PayrollResource::class;

    protected function afterCreate(): void
    {
        $payroll = $this->record;

        try {
            CashRegisterService::recordTransaction(
                null, // storeId - will be auto-detected
                type: 'payroll',
                amount: -$payroll->total_amount,
                referenceId: $payroll->id,
                description: 'Paie employé #' . $payroll->employee->full_name
            );

            Notification::make()
                ->title('Paie enregistrée')
                ->body('La paie a été enregistrée et la transaction de caisse créée.')
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
}
