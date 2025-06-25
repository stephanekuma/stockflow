<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function afterCreate(): void
    {
        $data = $this->form->getState();
        Log::info('form data afterCreate', $data);
        Log::info('units_data afterCreate', ['units_data' => $data['units_data'] ?? null]);
        if (empty($data['units_data'])) {
            Log::warning('Aucune unité saisie dans le repeater lors de la création du produit !');
        }
        $this->record::syncUnits($this->record, $data['units_data'] ?? []);
    }
}
