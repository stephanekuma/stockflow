<?php

namespace App\Services;

use App\Models\ProductUnit;
use App\Models\StockHistory;
use Illuminate\Support\Facades\Auth;

class StockMovementService
{
    /**
     * Ajouter du stock à un ProductUnit
     */
    public function addStock(ProductUnit $productUnit, int $quantity, string $type = 'achat', ?string $note = null): void
    {
        $before = $productUnit->quantity;
        $productUnit->increment('quantity', $quantity);
        $after = $productUnit->quantity;

        StockHistory::create([
            'store_id' => $productUnit->store_id,
            'product_unit_id' => $productUnit->id,
            'user_id' => Auth::id(),
            'type' => $type,
            'quantity_before' => $before,
            'quantity_after' => $after,
            'quantity_change' => $quantity,
            'note' => $note,
        ]);
    }

    /**
     * Retirer du stock à un ProductUnit
     */
    public function removeStock(ProductUnit $productUnit, int $quantity, string $type = 'vente', ?string $note = null): void
    {
        $before = $productUnit->quantity;
        $productUnit->decrement('quantity', $quantity);
        $after = $productUnit->quantity;

        StockHistory::create([
            'store_id' => $productUnit->store_id,
            'product_unit_id' => $productUnit->id,
            'user_id' => Auth::id(),
            'type' => $type,
            'quantity_before' => $before,
            'quantity_after' => $after,
            'quantity_change' => -$quantity,
            'note' => $note,
        ]);
    }

    /**
     * Correction manuelle de stock
     */
    public function adjustStock(ProductUnit $productUnit, int $newQuantity, ?string $note = null): void
    {
        $before = $productUnit->quantity;
        $change = $newQuantity - $before;
        $productUnit->quantity = $newQuantity;
        $productUnit->save();
        $after = $productUnit->quantity;

        StockHistory::create([
            'store_id' => $productUnit->store_id,
            'product_unit_id' => $productUnit->id,
            'user_id' => Auth::id(),
            'type' => 'correction',
            'quantity_before' => $before,
            'quantity_after' => $after,
            'quantity_change' => $change,
            'note' => $note,
        ]);
    }
}
