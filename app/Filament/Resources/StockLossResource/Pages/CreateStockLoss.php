<?php

namespace App\Filament\Resources\StockLossResource\Pages;

use App\Filament\Resources\StockLossResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\StockLoss;
use App\Models\LostProduct;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;

class CreateStockLoss extends CreateRecord
{
    protected static string $resource = StockLossResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['store_id'] = Filament::getTenant()->id;
        $data['user_id'] = Auth::id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $stockLoss = $this->record;
        $lostProducts = $this->data['lostProducts'] ?? [];

        foreach ($lostProducts as $productData) {
            $lostProduct = new LostProduct([
                'stock_loss_id' => $stockLoss->id,
                'quantity' => $productData['quantity'],
                'price' => $productData['price'],
                'loss_reason' => $productData['loss_reason'],
                'condition' => $productData['condition'],
            ]);

            if ($productData['type'] === 'product' && isset($productData['product_unit_id'])) {
                $lostProduct->product_unit_id = $productData['product_unit_id'];
            } elseif ($productData['type'] === 'pack' && isset($productData['pack_id'])) {
                $lostProduct->pack_id = $productData['pack_id'];
            }

            $lostProduct->calculateLossAmount();
            $lostProduct->save();
        }

        $stockLoss->calculateTotalLoss();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
