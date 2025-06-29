<?php

namespace App\Filament\Resources\StockReturnResource\Pages;

use App\Filament\Resources\StockReturnResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\StockReturn;
use App\Models\ReturnedProduct;
use Filament\Facades\Filament;

class CreateStockReturn extends CreateRecord
{
    protected static string $resource = StockReturnResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['store_id'] = Filament::getTenant()->id;

        return $data;
    }

    protected function afterCreate(): void
    {
        $stockReturn = $this->record;
        $returnedProducts = $this->data['returnedProducts'] ?? [];

        foreach ($returnedProducts as $productData) {
            $returnedProduct = new ReturnedProduct([
                'stock_return_id' => $stockReturn->id,
                'quantity' => $productData['quantity'],
                'price' => $productData['price'],
                'return_reason' => $productData['return_reason'],
                'condition' => $productData['condition'],
            ]);

            if ($productData['type'] === 'product' && isset($productData['product_unit_id'])) {
                $returnedProduct->product_unit_id = $productData['product_unit_id'];
            } elseif ($productData['type'] === 'pack' && isset($productData['pack_id'])) {
                $returnedProduct->pack_id = $productData['pack_id'];
            }

            $returnedProduct->calculateRefundAmount();
            $returnedProduct->save();
        }

        $stockReturn->calculateTotalRefund();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
