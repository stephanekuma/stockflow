<?php

namespace App\Filament\Resources\StockLossResource\Pages;

use App\Filament\Resources\StockLossResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStockLoss extends EditRecord
{
    protected static string $resource = StockLossResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
