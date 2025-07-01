<?php

namespace App\Filament\Resources\ProformaInvoiceResource\Pages;

use App\Filament\Resources\ProformaInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\ProformaInvoice;
use App\Models\ProformaInvoiceItem;
use App\Models\ProductUnit;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;
use App\Models\Product;
use App\Models\Unit;

class CreateProformaInvoice extends CreateRecord
{
    protected static string $resource = ProformaInvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['store_id'] = Filament::getTenant()->id;

        if (empty($data['invoice_number'])) {
            $data['invoice_number'] = ProformaInvoice::generateInvoiceNumber();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $proforma = $this->record;

        // Créer les éléments de la facture proforma
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
