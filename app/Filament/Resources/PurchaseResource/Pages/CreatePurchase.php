<?php

namespace App\Filament\Resources\PurchaseResource\Pages;

use App\Filament\Resources\PurchaseResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Facades\Filament;
use App\Models\ProductUnit;
use App\Models\PurchasedProduct;
use App\Services\StockMovementService;
use App\Services\CashRegisterService;
use Filament\Notifications\Notification;

class CreatePurchase extends CreateRecord
{
    protected static string $resource = PurchaseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['store_id'] = Filament::getTenant()->id;

        if (empty($data['invoice_number'])) {
            $data['invoice_number'] = \App\Filament\Resources\PurchaseResource::generateInvoiceNumber();
        }

        \Illuminate\Support\Facades\Log::info('CreatePurchase - Data before processing', [
            'data_keys' => array_keys($data),
            'subtotal' => $data['subtotal'] ?? 'not_set',
            'discount' => $data['discount'] ?? 'not_set',
            'total' => $data['total'] ?? 'not_set',
            'invoice_number' => $data['invoice_number'] ?? 'not_set',
        ]);

        return $data;
    }

    protected function afterCreate(): void
    {
        $purchase = $this->record;
        $stockService = new StockMovementService();

        // Ajout cohérent du stock pour chaque produit acheté
        if (isset($this->data['purchasedProducts'])) {
            foreach ($this->data['purchasedProducts'] as $productData) {
                $productUnit = ProductUnit::find($productData['product_unit_id']);
                if ($productUnit) {
                    // Ajoute la quantité à l'existant
                    $stockService->addStock($productUnit, (int) $productData['quantity'], 'achat', 'Achat #' . $purchase->invoice_number);
                }
                // Création du PurchasedProduct (historique d'achat)
                PurchasedProduct::create([
                    'purchase_id' => $purchase->id,
                    'product_unit_id' => $productData['product_unit_id'],
                    'quantity' => $productData['quantity'],
                    'cost_price' => $productData['cost_price'],
                    'price' => $productData['price'],
                    'discount' => $productData['discount'],
                    'vat' => $productData['vat'],
                    'total' => $productData['total'],
                ]);
            }
        }

        // Enregistrer la transaction de caisse si un paiement est effectué
        $amountPaid = $this->data['amount_paid'] ?? 0;
        if ($amountPaid > 0) {
            try {
                CashRegisterService::recordTransaction(
                    null, // storeId - will be auto-detected
                    type: 'purchase',
                    amount: -$amountPaid,
                    referenceId: $purchase->id,
                    description: 'Achat #' . $purchase->invoice_number
                );

                Notification::make()
                    ->title('Achat enregistré')
                    ->body('L\'achat a été enregistré et la transaction de caisse créée.')
                    ->success()
                    ->send();
            } catch (\Exception $e) {
                Notification::make()
                    ->title('Erreur caisse')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
            }
        }

        \Illuminate\Support\Facades\Log::info('CreatePurchase - Record created', [
            'record_id' => $this->record->id,
            'subtotal' => $this->record->subtotal,
            'discount' => $this->record->discount,
            'total' => $this->record->total,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
