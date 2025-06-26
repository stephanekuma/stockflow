<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Sale;
use App\Models\SoldProduct;
use App\Models\ProductUnit;
use Filament\Facades\Filament;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['invoice_number'])) {
            $data['invoice_number'] = Sale::generateInvoiceNumber();
        }

        $data['store_id'] = Filament::getTenant()->id;

        return $data;
    }

    protected function afterCreate(): void
    {
        $sale = $this->record;

        // Créer les produits vendus
        if (isset($this->data['soldProducts'])) {
            foreach ($this->data['soldProducts'] as $productData) {
                $productUnit = ProductUnit::find($productData['product_unit_id']);

                if ($productUnit) {
                    SoldProduct::create([
                        'sale_id' => $sale->id,
                        'product_unit_id' => $productData['product_unit_id'],
                        'quantity' => $productData['quantity'],
                        'price' => $productData['price'],
                        'discount' => $productData['discount'] ?? 0,
                        'total' => $productData['total'],
                    ]);

                    // Mettre à jour le stock
                    $productUnit->decrement('quantity', $productData['quantity']);
                }
            }
        }

        // Calculer les totaux
        $sale->calculateTotals();
    }
}
