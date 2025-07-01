<?php

namespace App\Filament\Resources\CustomerDebtResource\Pages;

use App\Filament\Resources\CustomerDebtResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerDebt extends CreateRecord
{
    protected static string $resource = CustomerDebtResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
