<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Filament\Resources\ExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Services\CashRegisterService;
use Filament\Notifications\Notification;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function afterCreate(): void
    {
        $expense = $this->record;

        try {
            CashRegisterService::recordTransaction(
                null, // storeId - will be auto-detected
                type: 'expense',
                amount: -$expense->amount,
                referenceId: $expense->id,
                description: $expense->description
            );

            Notification::make()
                ->title('Dépense enregistrée')
                ->body('La dépense a été enregistrée et la transaction de caisse créée.')
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
