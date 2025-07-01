<?php

namespace App\Filament\Resources\ProformaInvoiceResource\Pages;

use App\Filament\Resources\ProformaInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\ProformaInvoiceItem;
use App\Models\ProductUnit;

class EditProformaInvoice extends EditRecord
{
    protected static string $resource = ProformaInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $proforma = $this->record->load('items.productUnit.product', 'items.productUnit.unit', 'items.pack');

        // Charger les éléments existants
        $items = [];
        foreach ($proforma->items as $item) {
            $itemData = [
                'quantity' => $item->quantity,
                'price' => $item->price,
                'discount' => $item->discount,
                'total' => $item->total,
            ];

            if ($item->pack_id) {
                $itemData['type'] = 'pack';
                $itemData['pack_id'] = $item->pack_id;
            } else {
                $itemData['type'] = 'product';
                $itemData['product_unit_id'] = $item->product_unit_id;
                $itemData['product_id'] = $item->productUnit?->product_id;
                $itemData['unit_id'] = $item->productUnit?->unit_id;
            }

            $items[] = $itemData;
        }

        $data['items'] = $items;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (empty($data['invoice_number'])) {
            $data['invoice_number'] = \App\Models\ProformaInvoice::generateInvoiceNumber();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $proforma = $this->record;

        // Supprimer les anciens éléments
        $proforma->items()->delete();

        // Créer les nouveaux éléments
        if (isset($this->data['items'])) {
            foreach ($this->data['items'] as $itemData) {
                if ($itemData['type'] === 'product') {
                    // Nouveau système : product_id + unit_id
                    if (isset($itemData['product_id']) && isset($itemData['unit_id'])) {
                        $productUnit = ProductUnit::where('product_id', $itemData['product_id'])
                            ->where('unit_id', $itemData['unit_id'])
                            ->where('store_id', $proforma->store_id)
                            ->first();

                        if ($productUnit) {
                            ProformaInvoiceItem::create([
                                'proforma_invoice_id' => $proforma->id,
                                'product_unit_id' => $productUnit->id,
                                'pack_id' => null,
                                'quantity' => $itemData['quantity'],
                                'price' => $itemData['price'],
                                'discount' => $itemData['discount'] ?? 0,
                                'total' => ($itemData['quantity'] * $itemData['price']) - ($itemData['discount'] ?? 0),
                            ]);
                        }
                    }
                    // Ancien système : product_unit_id (pour compatibilité)
                    elseif (isset($itemData['product_unit_id'])) {
                        ProformaInvoiceItem::create([
                            'proforma_invoice_id' => $proforma->id,
                            'product_unit_id' => $itemData['product_unit_id'],
                            'pack_id' => null,
                            'quantity' => $itemData['quantity'],
                            'price' => $itemData['price'],
                            'discount' => $itemData['discount'] ?? 0,
                            'total' => ($itemData['quantity'] * $itemData['price']) - ($itemData['discount'] ?? 0),
                        ]);
                    }
                } elseif ($itemData['type'] === 'pack') {
                    ProformaInvoiceItem::create([
                        'proforma_invoice_id' => $proforma->id,
                        'product_unit_id' => null,
                        'pack_id' => $itemData['pack_id'],
                        'quantity' => $itemData['quantity'],
                        'price' => $itemData['price'],
                        'discount' => $itemData['discount'] ?? 0,
                        'total' => ($itemData['quantity'] * $itemData['price']) - ($itemData['discount'] ?? 0),
                    ]);
                }
            }
        }

        // Calculer les totaux
        $proforma->calculateTotals();
    }
}
