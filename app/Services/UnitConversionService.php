<?php

namespace App\Services;

use App\Models\ProductUnit;
use App\Models\Unit;
use App\Models\SoldProduct;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class UnitConversionService
{
    /**
     * Get the current store ID
     *
     * @return int|null
     */
    private function getCurrentStoreId(): ?int
    {
        $tenant = Filament::getTenant();

        if ($tenant) {
            return $tenant->id;
        }

        // En dernier recours, utiliser le premier store disponible
        return \App\Models\Store::first()?->id;
    }

    /**
     * Sell products with automatic unit conversion and splitting.
     *
     * @param int $productId
     * @param int $requestedUnitId
     * @param float $requestedQuantity
     * @param float $price
     * @param int $saleId
     * @return array
     */
    public function sellWithConversion(
        int $productId,
        int $requestedUnitId,
        float $requestedQuantity,
        float $price,
        int $saleId
    ): array {
        // S'assurer que les paramètres sont bien typés et arrondis à l'entier
        $requestedQuantity = (int) round($requestedQuantity);
        $price = (float) $price;

        $storeId = $this->getCurrentStoreId();
        if (!$storeId) {
            throw new \Exception("Impossible de déterminer le store actuel.");
        }

        $requestedUnit = Unit::findOrFail($requestedUnitId);
        $productUnits = ProductUnit::where('product_id', $productId)
            ->where('store_id', $storeId)
            ->with(['unit', 'customBaseUnit'])
            ->get();

        $soldProducts = [];
        $remainingQuantity = $requestedQuantity;

        // 1. D'abord, essayer d'utiliser l'unité demandée si elle est disponible
        $requestedProductUnit = $productUnits->where('unit_id', $requestedUnitId)->first();

        if ($requestedProductUnit && $requestedProductUnit->quantity >= $remainingQuantity) {
            // L'unité demandée a suffisamment de stock, l'utiliser directement
            $soldProduct = SoldProduct::create([
                'sale_id' => $saleId,
                'product_unit_id' => $requestedProductUnit->id,
                'quantity' => $remainingQuantity,
                'price' => $price,
                'total' => $remainingQuantity * $price,
                'data' => [
                    'original_requested_quantity' => $requestedQuantity,
                    'original_requested_unit' => $requestedUnit->name,
                    'unit_conversion_applied' => false, // Pas de conversion nécessaire
                ],
            ]);

            $soldProducts[] = $soldProduct;
            $requestedProductUnit->decrement('quantity', $remainingQuantity);
            $remainingQuantity = 0;
        } else {
            // 2. Si l'unité demandée n'est pas disponible ou insuffisante, faire la conversion
            // Convert requested quantity to base unit
            $requestedInBase = $requestedUnit->convertToBase($requestedQuantity);
            $remainingQuantityInBase = $requestedInBase;

            // Sort product units by conversion factor (largest first for optimal splitting)
            $sortedProductUnits = $productUnits->sortByDesc(function ($productUnit) {
                return $productUnit->getEffectiveConversionFactor();
            });

            foreach ($sortedProductUnits as $productUnit) {
                if ($remainingQuantityInBase <= 0) {
                    break;
                }

                $availableInBase = $productUnit->getAvailableQuantityInBase();
                if ($availableInBase <= 0) {
                    continue;
                }

                $quantityToUse = min($remainingQuantityInBase, $availableInBase);
                $quantityInUnit = (int) round($productUnit->convertFromBase($quantityToUse));

                // Vérifier que la quantité est positive
                if ($quantityInUnit <= 0) {
                    continue;
                }

                // Create sold product record
                $soldProduct = SoldProduct::create([
                    'sale_id' => $saleId,
                    'product_unit_id' => $productUnit->id,
                    'quantity' => $quantityInUnit,
                    'price' => $price,
                    'total' => $quantityInUnit * $price,
                    'data' => [
                        'original_requested_quantity' => $requestedQuantity,
                        'original_requested_unit' => $requestedUnit->name,
                        'converted_from_base' => $quantityToUse,
                        'unit_conversion_applied' => true,
                    ],
                ]);

                $soldProducts[] = $soldProduct;

                // Update stock
                $productUnit->decrement('quantity', $quantityInUnit);
                $remainingQuantityInBase -= $quantityToUse;
            }

            if ($remainingQuantityInBase > 0) {
                throw new \Exception("Stock insuffisant. Il manque " . (int) round($remainingQuantityInBase) . " unités de base.");
            }
        }

        return $soldProducts;
    }

    /**
     * Get optimal selling strategy for a product.
     *
     * @param int $productId
     * @param int $requestedUnitId
     * @param float $requestedQuantity
     * @return array
     */
    public function getOptimalSellingStrategy(
        int $productId,
        int $requestedUnitId,
        float $requestedQuantity
    ): array {
        // S'assurer que la quantité est bien un entier
        $requestedQuantity = (int) round($requestedQuantity);

        $storeId = $this->getCurrentStoreId();
        if (!$storeId) {
            throw new \Exception("Impossible de déterminer le store actuel.");
        }

        $requestedUnit = Unit::findOrFail($requestedUnitId);
        $productUnits = ProductUnit::where('product_id', $productId)
            ->where('store_id', $storeId)
            ->with(['unit', 'customBaseUnit'])
            ->get();

        $strategy = [];
        $canSell = false;
        $missingQuantity = 0;

        // 1. D'abord, vérifier si l'unité demandée est disponible
        $requestedProductUnit = $productUnits->where('unit_id', $requestedUnitId)->first();

        if ($requestedProductUnit && $requestedProductUnit->quantity >= $requestedQuantity) {
            // L'unité demandée a suffisamment de stock
            $strategy[] = [
                'product_unit_id' => $requestedProductUnit->id,
                'unit_name' => $requestedProductUnit->unit->name,
                'quantity' => $requestedQuantity,
                'quantity_in_base' => $requestedProductUnit->convertToBase($requestedQuantity),
                'available_stock' => $requestedProductUnit->quantity,
                'conversion_factor' => $requestedProductUnit->getEffectiveConversionFactor(),
                'direct_use' => true, // Utilisation directe, pas de conversion
            ];
            $canSell = true;
        } else {
            // 2. Si l'unité demandée n'est pas disponible ou insuffisante, calculer la conversion
            $requestedInBase = $requestedUnit->convertToBase($requestedQuantity);
            $remainingQuantityInBase = $requestedInBase;

            // Sort by conversion factor (largest first)
            $sortedProductUnits = $productUnits->sortByDesc(function ($productUnit) {
                return $productUnit->getEffectiveConversionFactor();
            });

            foreach ($sortedProductUnits as $productUnit) {
                if ($remainingQuantityInBase <= 0) {
                    break;
                }

                $availableInBase = $productUnit->getAvailableQuantityInBase();
                if ($availableInBase <= 0) {
                    continue;
                }

                $quantityToUse = min($remainingQuantityInBase, $availableInBase);
                $quantityInUnit = (int) round($productUnit->convertFromBase($quantityToUse));

                // Vérifier que la quantité est positive
                if ($quantityInUnit <= 0) {
                    continue;
                }

                $strategy[] = [
                    'product_unit_id' => $productUnit->id,
                    'unit_name' => $productUnit->unit->name,
                    'quantity' => $quantityInUnit,
                    'quantity_in_base' => $quantityToUse,
                    'available_stock' => $productUnit->quantity,
                    'conversion_factor' => $productUnit->getEffectiveConversionFactor(),
                    'direct_use' => false, // Conversion nécessaire
                ];

                $remainingQuantityInBase -= $quantityToUse;
            }

            $canSell = $remainingQuantityInBase <= 0;
            $missingQuantity = (int) round($remainingQuantityInBase);
        }

        return [
            'strategy' => $strategy,
            'can_sell' => $canSell,
            'missing_quantity' => $missingQuantity,
            'requested_in_base' => $requestedUnit->convertToBase($requestedQuantity),
        ];
    }

    /**
     * Convert quantity between units for a specific product.
     *
     * @param int $productId
     * @param float $quantity
     * @param int $fromUnitId
     * @param int $toUnitId
     * @return int
     */
    public function convertQuantity(
        int $productId,
        float $quantity,
        int $fromUnitId,
        int $toUnitId
    ): int {
        // S'assurer que la quantité est bien un entier
        $quantity = (int) round($quantity);

        $storeId = $this->getCurrentStoreId();
        if (!$storeId) {
            throw new \Exception("Impossible de déterminer le store actuel.");
        }

        $fromProductUnit = ProductUnit::where('product_id', $productId)
            ->where('unit_id', $fromUnitId)
            ->where('store_id', $storeId)
            ->firstOrFail();

        $toProductUnit = ProductUnit::where('product_id', $productId)
            ->where('unit_id', $toUnitId)
            ->where('store_id', $storeId)
            ->firstOrFail();

        return (int) round($fromProductUnit->convertTo($quantity, $toProductUnit));
    }

    /**
     * Get all available units for a product with their conversion factors.
     *
     * @param int $productId
     * @return Collection
     */
    public function getAvailableUnitsForProduct(int $productId): Collection
    {
        $storeId = $this->getCurrentStoreId();
        if (!$storeId) {
            return collect();
        }

        return ProductUnit::where('product_id', $productId)
            ->where('store_id', $storeId)
            ->where('quantity', '>', 0)
            ->with(['unit', 'customBaseUnit'])
            ->get()
            ->map(function ($productUnit) {
                return [
                    'id' => $productUnit->unit->id,
                    'name' => $productUnit->unit->name,
                    'key' => $productUnit->unit->key,
                    'available_quantity' => $productUnit->quantity,
                    'conversion_factor' => $productUnit->getEffectiveConversionFactor(),
                    'base_unit' => $productUnit->getEffectiveBaseUnit()?->name,
                    'is_custom_conversion' => $productUnit->custom_conversion_factor !== null,
                ];
            });
    }

    /**
     * Check if a product has enough stock in any unit.
     *
     * @param int $productId
     * @param float $quantity
     * @param int $unitId
     * @return bool
     */
    public function hasEnoughStock(int $productId, float $quantity, int $unitId): bool
    {
        // S'assurer que la quantité est bien un entier
        $quantity = (int) round($quantity);

        $storeId = $this->getCurrentStoreId();
        if (!$storeId) {
            return false;
        }

        $requestedUnit = Unit::findOrFail($unitId);
        $requestedInBase = $requestedUnit->convertToBase($quantity);

        $totalAvailableInBase = ProductUnit::where('product_id', $productId)
            ->where('store_id', $storeId)
            ->get()
            ->sum(function ($productUnit) {
                return $productUnit->getAvailableQuantityInBase();
            });

        return $totalAvailableInBase >= $requestedInBase;
    }
}
