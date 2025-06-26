<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Sale;
use App\Models\SoldProduct;
use App\Models\ProductUnit;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;
use App\Models\StockHistory;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

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
                if ($productData['type'] === 'product' && isset($productData['product_unit_id'])) {
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
                        $before = $productUnit->quantity;
                        $productUnit->decrement('quantity', $productData['quantity']);
                        $after = $productUnit->quantity;

                        // Historique de stock
                        StockHistory::create([
                            'product_unit_id' => $productUnit->id,
                            'user_id' => Auth::id() ?? null,
                            'type' => 'vente',
                            'quantity_before' => $before,
                            'quantity_after' => $after,
                            'quantity_change' => -$productData['quantity'],
                            'note' => 'Vente enregistrée via CreateSale',
                        ]);

                        // Vérifier le seuil de stock
                        if ($productUnit->quantity <= ($productUnit->low_stock_threshold ?? 0)) {
                            Log::warning('Stock bas pour le produit: ' . ($productUnit->product->name ?? 'N/A') . ' (Unité: ' . ($productUnit->unit->name ?? '') . '). Stock restant: ' . $productUnit->quantity);
                            // Ici, on pourrait déclencher une notification Filament ou Laravel
                        }
                    }
                } elseif ($productData['type'] === 'pack' && isset($productData['pack_id'])) {
                    $pack = \App\Models\Pack::with('packProducts.productUnit')->find($productData['pack_id']);

                    if ($pack) {
                        // Créer une entrée SoldProduct pour le pack
                        SoldProduct::create([
                            'sale_id' => $sale->id,
                            'product_unit_id' => null, // Pas de product_unit_id pour les packs
                            'pack_id' => $productData['pack_id'], // Nouveau champ à ajouter
                            'quantity' => $productData['quantity'],
                            'price' => $productData['price'],
                            'discount' => $productData['discount'] ?? 0,
                            'total' => $productData['total'],
                        ]);

                        // Mettre à jour le stock des produits composants du pack
                        foreach ($pack->packProducts as $packProduct) {
                            $productUnit = $packProduct->productUnit;
                            if ($productUnit) {
                                $quantityToDecrement = $packProduct->quantity * $productData['quantity'];
                                $before = $productUnit->quantity;
                                $productUnit->decrement('quantity', $quantityToDecrement);
                                $after = $productUnit->quantity;

                                // Historique de stock pour chaque unité du pack
                                StockHistory::create([
                                    'product_unit_id' => $productUnit->id,
                                    'user_id' => Auth::id() ?? null,
                                    'type' => 'vente (pack)',
                                    'quantity_before' => $before,
                                    'quantity_after' => $after,
                                    'quantity_change' => -$quantityToDecrement,
                                    'note' => 'Vente de pack via CreateSale',
                                ]);

                                // Vérifier le seuil de stock pour chaque unité du pack
                                if ($productUnit->quantity <= ($productUnit->low_stock_threshold ?? 0)) {
                                    Log::warning('Stock bas pour le produit (pack): ' . ($productUnit->product->name ?? 'N/A') . ' (Unité: ' . ($productUnit->unit->name ?? '') . '). Stock restant: ' . $productUnit->quantity);

                                    foreach (User::all() as $user) {
                                        Notification::make()
                                            ->title('Stock bas')
                                            ->body('Le stock du produit ' . ($productUnit->product->name ?? 'N/A') . ' (Unité: ' . ($productUnit->unit->name ?? '') . ') est passé sous le seuil d\'alerte. Stock restant: ' . $productUnit->quantity)
                                            ->toDatabase($user);
                                    }
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
