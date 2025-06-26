<?php

namespace App\Observers;

use App\Models\ProductUnit;
use App\Notifications\LowStockNotification;
use App\Models\User;
use Filament\Notifications\Notification;

class ProductUnitObserver
{
    /**
     * Handle the ProductUnit "created" event.
     */
    public function created(ProductUnit $productUnit): void
    {
        //
    }

    /**
     * Handle the ProductUnit "updated" event.
     */
    public function updated(ProductUnit $productUnit): void
    {
        if (
            $productUnit->quantity <= ($productUnit->low_stock_threshold ?? 0)
            && $productUnit->getOriginal('quantity') > ($productUnit->low_stock_threshold ?? 0)
        ) {
            // Récupérer tous les utilisateurs admin/manager (à adapter avec Spatie dans le futur)
            // $users = User::whereIn('role', ['admin', 'manager'])->get();

            $users = User::all();

            $product = $productUnit->product->name ?? 'Produit inconnu';
            $unit = $productUnit->unit->name ?? '';
            $quantity = $productUnit->quantity;
            $threshold = $productUnit->low_stock_threshold;

            foreach ($users as $user) {
                Notification::make()
                    ->title('Stock bas')
                    ->body("Le stock du produit : {$product} (Unité : {$unit}) est passé sous le seuil d'alerte. Stock restant : {$quantity} | Seuil d'alerte : {$threshold}")
                    ->toDatabase($user);
            }
        }
    }

    /**
     * Handle the ProductUnit "deleted" event.
     */
    public function deleted(ProductUnit $productUnit): void
    {
        //
    }

    /**
     * Handle the ProductUnit "restored" event.
     */
    public function restored(ProductUnit $productUnit): void
    {
        //
    }

    /**
     * Handle the ProductUnit "force deleted" event.
     */
    public function forceDeleted(ProductUnit $productUnit): void
    {
        //
    }
}
