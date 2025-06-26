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
        $sale = $this->record->load('soldProducts.productUnit.product', 'soldProducts.productUnit.unit');

        // Charger les produits vendus existants
        $soldProducts = [];
        foreach ($sale->soldProducts as $soldProduct) {
            $soldProducts[] = [
                'product_unit_id' => $soldProduct->product_unit_id,
                'quantity' => $soldProduct->quantity,
                'price' => $soldProduct->price,
                'discount' => $soldProduct->discount,
                'total' => $soldProduct->total,
            ];
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

        // Supprimer les anciens produits vendus
        $sale->soldProducts()->delete();

        // Créer les nouveaux produits vendus
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
                }
            }
        }

        // Calculer les totaux
        $sale->calculateTotals();
    }
}
