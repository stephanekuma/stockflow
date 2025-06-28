<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductUnit extends Model
{
    /** @use HasFactory<\Database\Factories\ProductUnitFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'store_id',
        'product_id',
        'unit_id',
        'cost_price',
        'price',
        'quantity',
        'custom_conversion_factor',
        'custom_base_unit_id',
        'discount',
        'vat',
        'total',
        'data',
        'low_stock_threshold',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'custom_conversion_factor' => 'decimal:4',
        'quantity' => 'integer',
        'low_stock_threshold' => 'integer',
    ];

    /**
     * Get the store that owns the ProductUnit
     *
     * @return BelongsTo
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the unit that owns the product unit.
     *
     * @return BelongsTo
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the product that owns the product unit.
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the custom base unit for this product unit.
     *
     * @return BelongsTo
     */
    public function customBaseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'custom_base_unit_id');
    }

    /**
     * The packs that belong to the ProductUnit
     *
     * @return BelongsToMany
     */
    public function packs(): BelongsToMany
    {
        return $this->belongsToMany(Pack::class)
            ->withPivot('quantity');
    }

    /**
     * Get the effective conversion factor for this product unit.
     *
     * @return float
     */
    public function getEffectiveConversionFactor(): float
    {
        // Si une conversion personnalisée est définie, l'utiliser
        if ($this->custom_conversion_factor !== null) {
            return $this->custom_conversion_factor;
        }

        // Sinon, utiliser la conversion de l'unité
        return $this->unit->conversion_factor;
    }

    /**
     * Get the effective base unit for this product unit.
     *
     * @return Unit|null
     */
    public function getEffectiveBaseUnit(): ?Unit
    {
        // Si une unité de base personnalisée est définie, l'utiliser
        if ($this->custom_base_unit_id) {
            return $this->customBaseUnit;
        }

        // Sinon, utiliser l'unité de base de l'unité
        return $this->unit->baseUnit;
    }

    /**
     * Convert quantity from this product unit to base unit.
     *
     * @param float $quantity
     * @return float
     */
    public function convertToBase(float $quantity): float
    {
        $baseUnit = $this->getEffectiveBaseUnit();

        if (!$baseUnit || $this->unit->id === $baseUnit->id) {
            return $quantity;
        }

        return $quantity * $this->getEffectiveConversionFactor();
    }

    /**
     * Convert quantity from base unit to this product unit.
     *
     * @param float $quantity
     * @return float
     */
    public function convertFromBase(float $quantity): float
    {
        $baseUnit = $this->getEffectiveBaseUnit();

        if (!$baseUnit || $this->unit->id === $baseUnit->id) {
            return $quantity;
        }

        return $quantity / $this->getEffectiveConversionFactor();
    }

    /**
     * Convert quantity from this product unit to another product unit.
     *
     * @param float $quantity
     * @param ProductUnit $targetProductUnit
     * @return float
     */
    public function convertTo(float $quantity, ProductUnit $targetProductUnit): float
    {
        if ($this->id === $targetProductUnit->id) {
            return $quantity;
        }

        // Convert to base first, then to target
        $baseQuantity = $this->convertToBase($quantity);
        return $targetProductUnit->convertFromBase($baseQuantity);
    }

    /**
     * Check if this product unit can be converted to another product unit.
     *
     * @param ProductUnit $targetProductUnit
     * @return bool
     */
    public function canConvertTo(ProductUnit $targetProductUnit): bool
    {
        $thisBaseUnit = $this->getEffectiveBaseUnit();
        $targetBaseUnit = $targetProductUnit->getEffectiveBaseUnit();

        if (!$thisBaseUnit || !$targetBaseUnit) {
            return false;
        }

        return $thisBaseUnit->id === $targetBaseUnit->id;
    }

    /**
     * Get available quantity in base unit.
     *
     * @return float
     */
    public function getAvailableQuantityInBase(): float
    {
        return $this->convertToBase($this->quantity);
    }

    /**
     * Check if there's enough stock for the requested quantity.
     *
     * @param float $requestedQuantity
     * @param Unit $requestedUnit
     * @return bool
     */
    public function hasEnoughStock(float $requestedQuantity, Unit $requestedUnit): bool
    {
        // Convert requested quantity to base unit
        $requestedUnitModel = Unit::find($requestedUnit->id);
        if (!$requestedUnitModel) {
            return false;
        }

        $requestedInBase = $requestedUnitModel->convertToBase($requestedQuantity);
        $availableInBase = $this->getAvailableQuantityInBase();

        return $availableInBase >= $requestedInBase;
    }
}
