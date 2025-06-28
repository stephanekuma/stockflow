<?php

namespace App\Services;

use App\Models\ProductUnit;
use App\Models\Unit;
use App\Models\SoldProduct;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class UnitConversionService
{
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
        $requestedUnit = Unit::findOrFail($requestedUnitId);
        $productUnits = ProductUnit::where('product_id', $productId)
            ->where('store_id', Auth::user()->current_store_id)
            ->with(['unit', 'customBaseUnit'])
            ->get();

        // Convert requested quantity to base unit
        $requestedInBase = $requestedUnit->convertToBase($requestedQuantity);

        $soldProducts = [];
        $remainingQuantity = $requestedInBase;

        // Sort product units by conversion factor (largest first for optimal splitting)
        $sortedProductUnits = $productUnits->sortByDesc(function ($productUnit) {
            return $productUnit->getEffectiveConversionFactor();
        });

        foreach ($sortedProductUnits as $productUnit) {
            if ($remainingQuantity <= 0) {
                break;
            }

            $availableInBase = $productUnit->getAvailableQuantityInBase();
            if ($availableInBase <= 0) {
                continue;
            }

            $quantityToUse = min($remainingQuantity, $availableInBase);
            $quantityInUnit = $productUnit->convertFromBase($quantityToUse);

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
            $remainingQuantity -= $quantityToUse;
        }

        if ($remainingQuantity > 0) {
            throw new \Exception("Stock insuffisant. Il manque " . $remainingQuantity . " unités de base.");
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
        $requestedUnit = Unit::findOrFail($requestedUnitId);
        $productUnits = ProductUnit::where('product_id', $productId)
            ->where('store_id', Auth::user()->current_store_id)
            ->with(['unit', 'customBaseUnit'])
            ->get();

        $requestedInBase = $requestedUnit->convertToBase($requestedQuantity);
        $strategy = [];
        $remainingQuantity = $requestedInBase;

        // Sort by conversion factor (largest first)
        $sortedProductUnits = $productUnits->sortByDesc(function ($productUnit) {
            return $productUnit->getEffectiveConversionFactor();
        });

        foreach ($sortedProductUnits as $productUnit) {
            if ($remainingQuantity <= 0) {
                break;
            }

            $availableInBase = $productUnit->getAvailableQuantityInBase();
            if ($availableInBase <= 0) {
                continue;
            }

            $quantityToUse = min($remainingQuantity, $availableInBase);
            $quantityInUnit = $productUnit->convertFromBase($quantityToUse);

            $strategy[] = [
                'product_unit_id' => $productUnit->id,
                'unit_name' => $productUnit->unit->name,
                'quantity' => $quantityInUnit,
                'quantity_in_base' => $quantityToUse,
                'available_stock' => $productUnit->quantity,
                'conversion_factor' => $productUnit->getEffectiveConversionFactor(),
            ];

            $remainingQuantity -= $quantityToUse;
        }

        return [
            'strategy' => $strategy,
            'can_sell' => $remainingQuantity <= 0,
            'missing_quantity' => $remainingQuantity,
            'requested_in_base' => $requestedInBase,
        ];
    }

    /**
     * Convert quantity between units for a specific product.
     *
     * @param int $productId
     * @param float $quantity
     * @param int $fromUnitId
     * @param int $toUnitId
     * @return float
     */
    public function convertQuantity(
        int $productId,
        float $quantity,
        int $fromUnitId,
        int $toUnitId
    ): float {
        $fromProductUnit = ProductUnit::where('product_id', $productId)
            ->where('unit_id', $fromUnitId)
            ->where('store_id', Auth::user()->current_store_id)
            ->firstOrFail();

        $toProductUnit = ProductUnit::where('product_id', $productId)
            ->where('unit_id', $toUnitId)
            ->where('store_id', Auth::user()->current_store_id)
            ->firstOrFail();

        return $fromProductUnit->convertTo($quantity, $toProductUnit);
    }

    /**
     * Get all available units for a product with their conversion factors.
     *
     * @param int $productId
     * @return Collection
     */
    public function getAvailableUnitsForProduct(int $productId): Collection
    {
        return ProductUnit::where('product_id', $productId)
            ->where('store_id', Auth::user()->current_store_id)
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
        $requestedUnit = Unit::findOrFail($unitId);
        $requestedInBase = $requestedUnit->convertToBase($quantity);

        $totalAvailableInBase = ProductUnit::where('product_id', $productId)
            ->where('store_id', Auth::user()->current_store_id)
            ->get()
            ->sum(function ($productUnit) {
                return $productUnit->getAvailableQuantityInBase();
            });

        return $totalAvailableInBase >= $requestedInBase;
    }
}
