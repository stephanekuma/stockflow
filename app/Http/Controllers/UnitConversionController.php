<?php

namespace App\Http\Controllers;

use App\Services\UnitConversionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UnitConversionController extends Controller
{
    protected UnitConversionService $unitConversionService;

    public function __construct(UnitConversionService $unitConversionService)
    {
        $this->unitConversionService = $unitConversionService;
    }

    /**
     * Get optimal selling strategy for a product.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSellingStrategy(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'unit_id' => 'required|integer|exists:units,id',
            'quantity' => 'required|numeric|min:0.01',
        ]);

        try {
            $strategy = $this->unitConversionService->getOptimalSellingStrategy(
                $request->product_id,
                $request->unit_id,
                $request->quantity
            );

            return response()->json([
                'success' => true,
                'data' => $strategy,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Convert quantity between units.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function convertQuantity(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|numeric|min:0.01',
            'from_unit_id' => 'required|integer|exists:units,id',
            'to_unit_id' => 'required|integer|exists:units,id',
        ]);

        try {
            $convertedQuantity = $this->unitConversionService->convertQuantity(
                $request->product_id,
                $request->quantity,
                $request->from_unit_id,
                $request->to_unit_id
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'converted_quantity' => $convertedQuantity,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get available units for a product.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAvailableUnits(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
        ]);

        try {
            $units = $this->unitConversionService->getAvailableUnitsForProduct($request->product_id);

            return response()->json([
                'success' => true,
                'data' => $units,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Check if product has enough stock.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkStock(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit_id' => 'required|integer|exists:units,id',
        ]);

        try {
            $hasStock = $this->unitConversionService->hasEnoughStock(
                $request->product_id,
                $request->quantity,
                $request->unit_id
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'has_enough_stock' => $hasStock,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
