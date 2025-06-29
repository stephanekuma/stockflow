<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\SoldProduct;
use App\Models\ProductUnit;

class EditSale extends EditRecord
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $sale = $this->record->load('soldProducts.productUnit.product', 'soldProducts.productUnit.unit', 'soldProducts.pack');

        // Charger les produits vendus existants
        $soldProducts = [];
        foreach ($sale->soldProducts as $soldProduct) {
            $productData = [
                'quantity' => $soldProduct->quantity,
                'price' => $soldProduct->price,
                'discount' => $soldProduct->discount,
                'total' => $soldProduct->total,
            ];

            if ($soldProduct->pack_id) {
                $productData['type'] = 'pack';
                $productData['pack_id'] = $soldProduct->pack_id;
            } else {
                $productData['type'] = 'product';
                $productData['product_unit_id'] = $soldProduct->product_unit_id;
                $productData['product_id'] = $soldProduct->productUnit?->product_id;
                $productData['unit_id'] = $soldProduct->productUnit?->unit_id;
            }

            $soldProducts[] = $productData;
        }

        $data['soldProducts'] = $soldProducts;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (empty($data['invoice_number'])) {
            $data['invoice_number'] = \App\Models\Sale::generateInvoiceNumber();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $sale = $this->record;
        $wasPending = $sale->getOriginal('status') === \App\Models\Sale::STATUS_PENDING;
        $isNowFinalized = in_array($sale->status, [\App\Models\Sale::STATUS_COMPLETED, \App\Models\Sale::STATUS_IN_PROGRESS]);

        // Supprimer les anciens produits vendus
        $sale->soldProducts()->delete();

        // Créer les nouveaux produits vendus
        if (isset($this->data['soldProducts'])) {
            foreach ($this->data['soldProducts'] as $productData) {
                if ($productData['type'] === 'product') {
                    // Nouveau système : product_id + unit_id
                    if (isset($productData['product_id']) && isset($productData['unit_id'])) {
                        $productUnit = ProductUnit::where('product_id', $productData['product_id'])
                            ->where('unit_id', $productData['unit_id'])
                            ->where('store_id', $sale->store_id)
                            ->first();

                        if ($productUnit) {
                            SoldProduct::create([
                                'sale_id' => $sale->id,
                                'product_unit_id' => $productUnit->id,
                                'pack_id' => null,
                                'quantity' => $productData['quantity'],
                                'price' => $productData['price'],
                                'discount' => $productData['discount'] ?? 0,
                                'total' => $productData['total'],
                            ]);

                            // Déduire le stock seulement si la vente passe d'en attente à finalisée
                            if ($wasPending && $isNowFinalized) {
                                $stockService = new \App\Services\StockMovementService();
                                $stockService->removeStock(
                                    $productUnit,
                                    (int) $productData['quantity'],
                                    'vente (reprise)',
                                    'Vente reprise #' . $sale->invoice_number
                                );
                            }
                        }
                    }
                    // Ancien système : product_unit_id (pour compatibilité)
                    elseif (isset($productData['product_unit_id'])) {
                        $productUnit = ProductUnit::find($productData['product_unit_id']);

                        if ($productUnit) {
                            SoldProduct::create([
                                'sale_id' => $sale->id,
                                'product_unit_id' => $productData['product_unit_id'],
                                'pack_id' => null,
                                'quantity' => $productData['quantity'],
                                'price' => $productData['price'],
                                'discount' => $productData['discount'] ?? 0,
                                'total' => $productData['total'],
                            ]);

                            // Déduire le stock seulement si la vente passe d'en attente à finalisée
                            if ($wasPending && $isNowFinalized) {
                                $stockService = new \App\Services\StockMovementService();
                                $stockService->removeStock(
                                    $productUnit,
                                    (int) $productData['quantity'],
                                    'vente (reprise)',
                                    'Vente reprise #' . $sale->invoice_number
                                );
                            }
                        }
                    }
                } elseif ($productData['type'] === 'pack' && isset($productData['pack_id'])) {
                    $pack = \App\Models\Pack::with('packProducts.productUnit')->find($productData['pack_id']);

                    if ($pack) {
                        SoldProduct::create([
                            'sale_id' => $sale->id,
                            'product_unit_id' => null,
                            'pack_id' => $productData['pack_id'],
                            'quantity' => $productData['quantity'],
                            'price' => $productData['price'],
                            'discount' => $productData['discount'] ?? 0,
                            'total' => $productData['total'],
                        ]);

                        // Déduire le stock seulement si la vente passe d'en attente à finalisée
                        if ($wasPending && $isNowFinalized) {
                            $stockService = new \App\Services\StockMovementService();
                            foreach ($pack->packProducts as $packProduct) {
                                $productUnit = $packProduct->productUnit;
                                if ($productUnit) {
                                    $quantityToDecrement = $packProduct->quantity * $productData['quantity'];
                                    $stockService->removeStock(
                                        $productUnit,
                                        (int) $quantityToDecrement,
                                        'vente (pack reprise)',
                                        'Vente de pack reprise #' . $sale->invoice_number
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }

        // Calculer les totaux
        $sale->calculateTotals();
    }
}
