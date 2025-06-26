<?php

namespace App\Filament\Resources\PurchaseResource\Pages;

use App\Filament\Resources\PurchaseResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Facades\Filament;

class CreatePurchase extends CreateRecord
{
    protected static string $resource = PurchaseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['store_id'] = Filament::getTenant()->id;

        \Illuminate\Support\Facades\Log::info('CreatePurchase - Data before processing', [
            'data_keys' => array_keys($data),
            'subtotal' => $data['subtotal'] ?? 'not_set',
            'discount' => $data['discount'] ?? 'not_set',
            'total' => $data['total'] ?? 'not_set',
        ]);

        return $data;
    }

    protected function afterCreate(): void
    {
        \Illuminate\Support\Facades\Log::info('CreatePurchase - Record created', [
            'record_id' => $this->record->id,
            'subtotal' => $this->record->subtotal,
            'discount' => $this->record->discount,
            'total' => $this->record->total,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
