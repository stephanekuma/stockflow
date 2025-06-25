<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $data = $this->form->getState();
        Log::info('form data afterSave', $data);
        Log::info('units_data afterSave', ['units_data' => $data['units_data'] ?? null]);
        if (empty($data['units_data'])) {
            Log::warning('Aucune unité saisie dans le repeater lors de l\'édition du produit !');
        }
        $this->record::syncUnits($this->record, $data['units_data'] ?? []);
        $this->fillForm();
    }
}
