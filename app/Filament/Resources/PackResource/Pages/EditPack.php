<?php

namespace App\Filament\Resources\PackResource\Pages;

use App\Filament\Resources\PackResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPack extends EditRecord
{
    protected static string $resource = PackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Use the beforeSave method from the resource
        return static::getResource()::beforeSave($data);
    }

    protected function afterSave(): void
    {
        // Use the afterSave method from the resource
        static::getResource()::afterSave([], $this->record);
    }
}
