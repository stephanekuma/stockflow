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
use App\Services\UnitConversionService;
use App\Services\StockMovementService;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\SaleResource\Widgets\PendingSalesWidget::class,
        ];
    }

    public function mount(): void
    {
        // Définir les valeurs par défaut
        $this->form->fill([
            'sold_at' => now(),
            'status' => Sale::STATUS_IN_PROGRESS,
            'invoice_number' => Sale::generateInvoiceNumber(),
        ]);

        // Vérifier si on reprend une vente en attente
        $resumeSaleId = request()->get('resume_sale_id');

        if ($resumeSaleId) {
            $sale = Sale::with(['soldProducts.productUnit.product', 'soldProducts.productUnit.unit', 'soldProducts.pack'])
                ->where('id', $resumeSaleId)
                ->where('status', Sale::STATUS_PENDING)
                ->where('store_id', Filament::getTenant()->id)
                ->first();

            if ($sale) {
                // Remplir le formulaire avec les données de la vente en attente
                $this->data = [
                    'customer_id' => $sale->customer_id,
                    'invoice_number' => $sale->invoice_number,
                    'sold_at' => $sale->sold_at,
                    'status' => Sale::STATUS_IN_PROGRESS,
                    'notes' => $sale->notes,
                ];

                // Remplir les produits vendus
                $soldProducts = [];
                foreach ($sale->soldProducts as $soldProduct) {
                    $productData = [
                        'quantity' => $soldProduct->quantity,
                        'price' => $soldProduct->price,
                        'discount' => $soldProduct->discount ?? 0,
                        'total' => $soldProduct->total,
                        'discount_type' => 'amount', // Valeur par défaut
                    ];

                    if ($soldProduct->pack_id) {
                        $productData['type'] = 'pack';
                        $productData['pack_id'] = $soldProduct->pack_id;
                    } else {
                        $productData['type'] = 'product';
                        $productData['product_unit_id'] = $soldProduct->product_unit_id;

                        // Récupérer product_id et unit_id depuis le ProductUnit
                        if ($soldProduct->productUnit) {
                            $productData['product_id'] = $soldProduct->productUnit->product_id;
                            $productData['unit_id'] = $soldProduct->productUnit->unit_id;
                        }
                    }

                    $soldProducts[] = $productData;
                }

                $this->data['soldProducts'] = $soldProducts;

                // Forcer la mise à jour du formulaire pour les champs Select
                $this->form->fill($this->data);

                // Supprimer la vente en attente
                $sale->delete();

                // Forcer à nouveau le remplissage du formulaire après la suppression
                $this->form->fill($this->data);

                Notification::make()
                    ->title('Vente reprise')
                    ->body('La vente en attente a été reprise avec succès. Produits: ' . count($soldProducts))
                    ->success()
                    ->send();
            }
        }
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Si on a des données de reprise dans $this->data, les utiliser
        if (!empty($this->data)) {
            $data = array_merge($data, $this->data);
        }

        return $data;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Récupérer les données du formulaire
        $formData = $this->form->getState();

        // Fusionner les données du formulaire avec les données passées
        $data = array_merge($formData, $data);

        // Si on a des données de reprise dans $this->data, les utiliser aussi
        if (!empty($this->data)) {
            $data = array_merge($this->data, $data);
        }

        // S'assurer que les valeurs par défaut sont définies
        $data['store_id'] = Filament::getTenant()->id;

        // Définir la date par défaut si elle n'est pas fournie
        if (empty($data['sold_at'])) {
            $data['sold_at'] = now();
        }

        // Définir le statut par défaut si il n'est pas fourni
        if (empty($data['status'])) {
            $data['status'] = Sale::STATUS_IN_PROGRESS;
        }

        // Générer le numéro de facture si il n'est pas fourni
        if (empty($data['invoice_number'])) {
            $data['invoice_number'] = Sale::generateInvoiceNumber();
        }

        // S'assurer que customer_id est présent (obligatoire)
        if (empty($data['customer_id'])) {
            throw new \Exception('Le client est obligatoire.');
        }

        return $data;
    }

    protected function beforeValidate(): void
    {
        // S'assurer que les valeurs par défaut sont définies avant la validation
        $data = $this->form->getState();

        if (empty($data['sold_at'])) {
            $this->form->fill(['sold_at' => now()]);
        }

        if (empty($data['status'])) {
            $this->form->fill(['status' => Sale::STATUS_IN_PROGRESS]);
        }

        if (empty($data['invoice_number'])) {
            $this->form->fill(['invoice_number' => Sale::generateInvoiceNumber()]);
        }
    }

    protected function beforeCreate(): void
    {
        // Aperçu/validation avant enregistrement
        $data = $this->data;
        $products = $data['soldProducts'] ?? [];
        $payments = $data['payments'] ?? [];
        $total = $data['total'] ?? 0;
        $totalPaid = array_sum(array_column($payments, 'amount'));
        $amountDue = $total - $totalPaid;

        $summary = view('filament.resources.sale-resource.pages.sale-summary', [
            'products' => $products,
            'payments' => $payments,
            'total' => $total,
            'totalPaid' => $totalPaid,
            'amountDue' => $amountDue,
        ])->render();

        Notification::make()
            ->title('Aperçu de la vente')
            ->body($summary)
            ->persistent()
            ->success()
            ->send();

        // Ici, on pourrait stopper le process et attendre une validation utilisateur (à implémenter avec un vrai modal si besoin)
    }

    protected function afterCreate(): void
    {
        $sale = $this->record;
        $stockService = new StockMovementService();
        $payments = $this->data['payments'] ?? [];
        $customer = \App\Models\Customer::find($sale->customer_id);

        // Créer les produits vendus avec conversion automatique
        if (isset($this->data['soldProducts'])) {
            $unitConversionService = new UnitConversionService();

            foreach ($this->data['soldProducts'] as $productData) {
                if ($productData['type'] === 'product') {
                    // Nouveau système : product_id + unit_id
                    if (isset($productData['product_id']) && isset($productData['unit_id'])) {
                        try {
                            $soldProducts = $unitConversionService->sellWithConversion(
                                $productData['product_id'],
                                $productData['unit_id'],
                                $productData['quantity'],
                                $productData['price'],
                                $sale->id
                            );
                            // Pour chaque soldProduct, décrémenter le stock via le service
                            foreach ($soldProducts as $soldProduct) {
                                $productUnit = $soldProduct->productUnit;
                                if ($productUnit) {
                                    $stockService->removeStock(
                                        $productUnit,
                                        (int) $soldProduct->quantity,
                                        'vente',
                                        'Vente #' . $sale->invoice_number
                                    );
                                }
                            }
                        } catch (\Exception $e) {
                            $productUnit = ProductUnit::where('product_id', $productData['product_id'])
                                ->where('unit_id', $productData['unit_id'])
                                ->where('store_id', $sale->store_id)
                                ->first();
                            if ($productUnit) {
                                SoldProduct::create([
                                    'sale_id' => $sale->id,
                                    'product_unit_id' => $productUnit->id,
                                    'quantity' => $productData['quantity'],
                                    'price' => $productData['price'],
                                    'discount' => $productData['discount'] ?? 0,
                                    'total' => $productData['total'],
                                    'data' => [
                                        'error' => $e->getMessage(),
                                        'conversion_failed' => true,
                                    ],
                                ]);
                                $stockService->removeStock(
                                    $productUnit,
                                    (int) $productData['quantity'],
                                    'vente',
                                    'Vente #' . $sale->invoice_number . ' (conversion échouée)'
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
                                'quantity' => $productData['quantity'],
                                'price' => $productData['price'],
                                'discount' => $productData['discount'] ?? 0,
                                'total' => $productData['total'],
                            ]);
                            $stockService->removeStock(
                                $productUnit,
                                (int) $productData['quantity'],
                                'vente',
                                'Vente #' . $sale->invoice_number
                            );

                            // Vérifier le seuil de stock
                            if ($productUnit->low_stock_threshold && $productUnit->quantity <= $productUnit->low_stock_threshold) {
                                // Envoyer notification de stock faible
                                $productUnit->product->notify(new \App\Notifications\LowStockNotification($productUnit));
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

                        // Mettre à jour le stock pour chaque composant du pack
                        foreach ($pack->packProducts as $packProduct) {
                            $productUnit = $packProduct->productUnit;
                            if ($productUnit) {
                                $quantityToDecrement = $packProduct->quantity * $productData['quantity'];
                                $stockService->removeStock(
                                    $productUnit,
                                    (int) $quantityToDecrement,
                                    'vente (pack)',
                                    'Vente de pack #' . $sale->invoice_number
                                );

                                // Vérifier le seuil de stock pour chaque unité du pack
                                if ($productUnit->low_stock_threshold && $productUnit->quantity <= $productUnit->low_stock_threshold) {
                                    $productUnit->product->notify(new \App\Notifications\LowStockNotification($productUnit));
                                }
                            }
                        }
                    }
                }
            }
        }

        // Calculer les totaux
        $sale->calculateTotals();

        // Gérer les paiements
        foreach ($payments as $payment) {
            $type = $payment['type'];
            $amount = (float) $payment['amount'];
            $note = $payment['note'] ?? null;
            if ($type === 'deposit' && $customer) {
                // Déduire du solde client
                $customer->balance -= $amount;
                $customer->save();
            }
            \App\Models\SalePayment::create([
                'store_id' => \Filament\Facades\Filament::getTenant()->id,
                'sale_id' => $sale->id,
                'customer_id' => $customer ? $customer->id : null,
                'amount' => $amount,
                'user_id' => \Illuminate\Support\Facades\Auth::id(),
                'note' => $note,
                'type' => $type,
            ]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('put_on_hold')
                ->label('Mettre en attente')
                ->icon('heroicon-o-pause')
                ->color('warning')
                ->action(function () {
                    // Récupérer toutes les données du formulaire
                    $formData = $this->form->getState();

                    // Sauvegarder la vente en statut "en attente"
                    $formData['status'] = Sale::STATUS_PENDING;
                    $formData['store_id'] = Filament::getTenant()->id;

                    if (empty($formData['invoice_number'])) {
                        $formData['invoice_number'] = Sale::generateInvoiceNumber();
                    }

                    if (empty($formData['sold_at'])) {
                        $formData['sold_at'] = now();
                    }

                    // Créer la vente en attente
                    $sale = Sale::create($formData);

                    // Créer les produits vendus
                    if (isset($formData['soldProducts'])) {
                        foreach ($formData['soldProducts'] as $productData) {
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
                                    }
                                }
                                // Ancien système : product_unit_id (pour compatibilité)
                                elseif (isset($productData['product_unit_id'])) {
                                    SoldProduct::create([
                                        'sale_id' => $sale->id,
                                        'product_unit_id' => $productData['product_unit_id'],
                                        'pack_id' => null,
                                        'quantity' => $productData['quantity'],
                                        'price' => $productData['price'],
                                        'discount' => $productData['discount'] ?? 0,
                                        'total' => $productData['total'],
                                    ]);
                                }
                            } elseif ($productData['type'] === 'pack' && isset($productData['pack_id'])) {
                                SoldProduct::create([
                                    'sale_id' => $sale->id,
                                    'product_unit_id' => null,
                                    'pack_id' => $productData['pack_id'],
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

                    Notification::make()
                        ->title('Vente mise en attente')
                        ->body('La vente a été sauvegardée en attente avec tous les produits choisis. Vous pourrez la reprendre plus tard.')
                        ->success()
                        ->send();

                    // Reste sur la page de création en rechargeant le formulaire
                    return redirect()->route('filament.admin.resources.sales.create', [
                        'tenant' => Filament::getTenant()
                    ]);
                })
                ->requiresConfirmation()
                ->modalHeading('Mettre la vente en attente')
                ->modalDescription('Cette vente sera sauvegardée en attente et pourra être reprise plus tard.')
                ->modalSubmitActionLabel('Mettre en attente'),
        ];
    }
}
