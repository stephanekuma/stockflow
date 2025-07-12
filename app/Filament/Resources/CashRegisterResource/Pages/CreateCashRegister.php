<?php

namespace App\Filament\Resources\CashRegisterResource\Pages;

use App\Filament\Resources\CashRegisterResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\CashRegister;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;

class CreateCashRegister extends CreateRecord
{
    protected static string $resource = CashRegisterResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $storeId = Filament::getTenant()->id;
        $now = now();
        $dayName = $now->locale('fr')->dayName;
        $date = $now->format('d/m/Y');

        $data['store_id'] = $storeId;
        $data['name'] = "Caisse {$dayName} {$date}";
        $data['initial_balance'] = 10000;
        $data['current_balance'] = 10000;

        return $data;
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Caisse créée')
            ->success()
            ->body('La caisse a été créée avec un solde initial de 10,000 XOF.')
            ->send();
    }
}
