<?php

namespace App\Filament\Resources\StockLossResource\Pages;

use App\Filament\Resources\StockLossResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStockLosses extends ListRecords
{
    protected static string $resource = StockLossResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
