<?php

namespace App\Filament\Resources\PurchaseResource\Pages;

use App\Filament\Resources\PurchaseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPurchase extends EditRecord
{
    protected static string $resource = PurchaseResource::class;

    protected array $purchasedProductsData = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load purchased products data for the form
        if (isset($data['id'])) {
            $purchase = \App\Models\Purchase::with('purchasedProducts.productUnit')->find($data['id']);
            if ($purchase) {
                $this->purchasedProductsData = $purchase->purchasedProducts->map(function ($purchasedProduct) {
                    return [
                        'product_unit_id' => $purchasedProduct->product_unit_id,
                        'quantity' => $purchasedProduct->quantity,
                        'cost_price' => $purchasedProduct->cost_price,
                        'price' => $purchasedProduct->price,
                        'discount' => $purchasedProduct->discount,
                        'vat' => $purchasedProduct->vat,
                        'total' => $purchasedProduct->total,
                    ];
                })->toArray();

                $data['purchasedProducts'] = $this->purchasedProductsData;
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Store purchasedProducts for later use
        $this->purchasedProductsData = $data['purchasedProducts'] ?? [];

        // Remove purchasedProducts from data to avoid issues
        unset($data['purchasedProducts']);

        // Calculate totals
        if (isset($this->purchasedProductsData) && is_array($this->purchasedProductsData) && count($this->purchasedProductsData) > 0) {
            $subtotal = 0.0;
            $totalDiscount = 0.0;

            foreach ($this->purchasedProductsData as $product) {
                $quantity = (float) ($product['quantity'] ?? 0);
                $price = (float) ($product['price'] ?? 0);
                $discount = (float) ($product['discount'] ?? 0);

                $productSubtotal = $quantity * $price;
                $discountAmount = $productSubtotal * ($discount / 100);

                $subtotal += $productSubtotal;
                $totalDiscount += $discountAmount;
            }

            $total = $subtotal - $totalDiscount;

            $data['subtotal'] = $subtotal;
            $data['discount'] = $totalDiscount;
            $data['total'] = $total;
        }

        // Use the beforeSave method from the resource
        return static::getResource()::beforeSave($data);
    }

    protected function afterSave(): void
    {
        // Delete existing purchased products
        $this->record->purchasedProducts()->delete();

        // Create new PurchasedProduct records
        if (isset($this->purchasedProductsData) && is_array($this->purchasedProductsData)) {
            foreach ($this->purchasedProductsData as $productData) {
                $purchasedProduct = new \App\Models\PurchasedProduct([
                    'purchase_id' => $this->record->id,
                    'product_unit_id' => $productData['product_unit_id'],
                    'quantity' => $productData['quantity'],
                    'cost_price' => $productData['cost_price'],
                    'price' => $productData['price'],
                    'discount' => $productData['discount'],
                    'vat' => $productData['vat'],
                    'total' => $productData['total'],
                ]);

                $purchasedProduct->save();
            }
        }

        // Use the afterSave method from the resource
        static::getResource()::afterSave(['purchasedProducts' => $this->purchasedProductsData ?? []], $this->record);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
