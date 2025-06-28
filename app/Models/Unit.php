<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    /** @use HasFactory<\Database\Factories\UnitFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'store_id',
        'name',
        'key',
        'is_base_unit',
        'conversion_factor',
        'base_unit_id',
        'data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'is_base_unit' => 'boolean',
        'conversion_factor' => 'decimal:4',
    ];

    /**
     * Get the store that owns the unit.
     *
     * @return BelongsTo
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the base unit for this unit.
     *
     * @return BelongsTo
     */
    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    /**
     * Get all units that use this unit as base.
     *
     * @return HasMany
     */
    public function derivedUnits(): HasMany
    {
        return $this->hasMany(Unit::class, 'base_unit_id');
    }

    /**
     * Convert quantity from this unit to base unit.
     *
     * @param float $quantity
     * @return float
     */
    public function convertToBase(float $quantity): float
    {
        if ($this->is_base_unit) {
            return $quantity;
        }

        return $quantity * $this->conversion_factor;
    }

    /**
     * Convert quantity from base unit to this unit.
     *
     * @param float $quantity
     * @return float
     */
    public function convertFromBase(float $quantity): float
    {
        if ($this->is_base_unit) {
            return $quantity;
        }

        return $quantity / $this->conversion_factor;
    }

    /**
     * Convert quantity from this unit to another unit.
     *
     * @param float $quantity
     * @param Unit $targetUnit
     * @return float
     */
    public function convertTo(float $quantity, Unit $targetUnit): float
    {
        if ($this->id === $targetUnit->id) {
            return $quantity;
        }

        // Convert to base first, then to target
        $baseQuantity = $this->convertToBase($quantity);
        return $targetUnit->convertFromBase($baseQuantity);
    }

    /**
     * Get the effective conversion factor to another unit.
     *
     * @param Unit $targetUnit
     * @return float
     */
    public function getConversionFactorTo(Unit $targetUnit): float
    {
        if ($this->id === $targetUnit->id) {
            return 1;
        }

        return $this->conversion_factor / $targetUnit->conversion_factor;
    }

    /**
     * Check if this unit can be converted to another unit.
     *
     * @param Unit $targetUnit
     * @return bool
     */
    public function canConvertTo(Unit $targetUnit): bool
    {
        // Both units must have the same base unit or one must be the base of the other
        if ($this->is_base_unit && $targetUnit->base_unit_id === $this->id) {
            return true;
        }

        if ($targetUnit->is_base_unit && $this->base_unit_id === $targetUnit->id) {
            return true;
        }

        return $this->base_unit_id === $targetUnit->base_unit_id;
    }
}
