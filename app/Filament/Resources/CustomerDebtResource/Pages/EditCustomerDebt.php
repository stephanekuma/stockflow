<?php

namespace App\Filament\Resources\CustomerDebtResource\Pages;

use App\Filament\Resources\CustomerDebtResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomerDebt extends EditRecord
{
    protected static string $resource = CustomerDebtResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
