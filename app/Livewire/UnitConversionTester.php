<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\Unit;
use App\Services\UnitConversionService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class UnitConversionTester extends Component
{
    public $selectedProductId;
    public $selectedUnitId;
    public $quantity = 1;
    public $availableUnits = [];
    public $conversionResult = null;
    public $sellingStrategy = null;
    public $products = [];
    public $units = [];

    protected UnitConversionService $unitConversionService;

    public function boot(UnitConversionService $unitConversionService)
    {
        $this->unitConversionService = $unitConversionService;
    }

    public function mount()
    {
        $this->products = Product::where('store_id', Auth::user()->current_store_id)->get();
        $this->units = Unit::where('store_id', Auth::user()->current_store_id)->get();
    }

    public function updatedSelectedProductId()
    {
        if ($this->selectedProductId) {
            $this->availableUnits = $this->unitConversionService->getAvailableUnitsForProduct($this->selectedProductId);
        } else {
            $this->availableUnits = [];
        }
        $this->resetResults();
    }

    public function updatedSelectedUnitId()
    {
        $this->resetResults();
    }

    public function updatedQuantity()
    {
        $this->resetResults();
    }

    public function getSellingStrategy()
    {
        if (!$this->selectedProductId || !$this->selectedUnitId || $this->quantity <= 0) {
            return;
        }

        try {
            $this->sellingStrategy = $this->unitConversionService->getOptimalSellingStrategy(
                $this->selectedProductId,
                $this->selectedUnitId,
                $this->quantity
            );
        } catch (\Exception $e) {
            $this->addError('strategy', $e->getMessage());
        }
    }

    public function convertQuantity($fromUnitId, $toUnitId)
    {
        if (!$this->selectedProductId || $this->quantity <= 0) {
            return;
        }

        try {
            $convertedQuantity = $this->unitConversionService->convertQuantity(
                $this->selectedProductId,
                $this->quantity,
                $fromUnitId,
                $toUnitId
            );

            $fromUnit = Unit::find($fromUnitId);
            $toUnit = Unit::find($toUnitId);

            $this->conversionResult = [
                'from' => $this->quantity . ' ' . $fromUnit->name,
                'to' => $convertedQuantity . ' ' . $toUnit->name,
                'converted_quantity' => $convertedQuantity,
            ];
        } catch (\Exception $e) {
            $this->addError('conversion', $e->getMessage());
        }
    }

    public function checkStock()
    {
        if (!$this->selectedProductId || !$this->selectedUnitId || $this->quantity <= 0) {
            return;
        }

        try {
            $hasStock = $this->unitConversionService->hasEnoughStock(
                $this->selectedProductId,
                $this->quantity,
                $this->selectedUnitId
            );

            $this->conversionResult = [
                'stock_check' => $hasStock ? 'Stock suffisant' : 'Stock insuffisant',
                'has_stock' => $hasStock,
            ];
        } catch (\Exception $e) {
            $this->addError('stock', $e->getMessage());
        }
    }

    private function resetResults()
    {
        $this->conversionResult = null;
        $this->sellingStrategy = null;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.unit-conversion-tester');
    }
}
