<?php

namespace App\Filament\Resources\PackResource\Pages;

use App\Filament\Resources\PackResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Facades\Filament;

class CreatePack extends CreateRecord
{
    protected static string $resource = PackResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['store_id'] = Filament::getTenant()->id;

        // Use the beforeSave method from the resource
        return static::getResource()::beforeSave($data);
    }

    protected function afterCreate(): void
    {
        // Use the afterSave method from the resource
        static::getResource()::afterSave([], $this->record);
    }
}
