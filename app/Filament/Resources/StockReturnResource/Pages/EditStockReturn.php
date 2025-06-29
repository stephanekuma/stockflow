<?php

namespace App\Filament\Resources\StockReturnResource\Pages;

use App\Filament\Resources\StockReturnResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\ReturnedProduct;
use Filament\Forms\Get;
use Filament\Forms\Set;

class EditStockReturn extends EditRecord
{
    protected static string $resource = StockReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $stockReturn = $this->record;
        $returnedProducts = [];

        foreach ($stockReturn->returnedProducts as $returnedProduct) {
            $productData = [
                'quantity' => $returnedProduct->quantity,
                'price' => $returnedProduct->price,
                'return_reason' => $returnedProduct->return_reason,
                'condition' => $returnedProduct->condition,
                'refund_amount' => $returnedProduct->refund_amount,
            ];

            if ($returnedProduct->product_unit_id) {
                $productUnit = $returnedProduct->productUnit;
                $productData['type'] = 'product';
                $productData['product_id'] = $productUnit->product_id;
                $productData['unit_id'] = $productUnit->unit_id;
                $productData['product_unit_id'] = $returnedProduct->product_unit_id;
            } elseif ($returnedProduct->pack_id) {
                $productData['type'] = 'pack';
                $productData['pack_id'] = $returnedProduct->pack_id;
            }

            $returnedProducts[] = $productData;
        }

        $data['returnedProducts'] = $returnedProducts;

        return $data;
    }

    protected function afterSave(): void
    {
        $stockReturn = $this->record;
        $returnedProducts = $this->data['returnedProducts'] ?? [];

        // Supprimer les anciens produits retournés
        $stockReturn->returnedProducts()->delete();

        // Créer les nouveaux produits retournés
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
