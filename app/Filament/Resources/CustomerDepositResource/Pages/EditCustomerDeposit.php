<?php

namespace App\Filament\Resources\CustomerDepositResource\Pages;

use App\Filament\Resources\CustomerDepositResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomerDeposit extends EditRecord
{
    protected static string $resource = CustomerDepositResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
