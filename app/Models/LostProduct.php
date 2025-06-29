<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LostProduct extends Model
{
    /** @use HasFactory<\Database\Factories\LostProductFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'stock_loss_id',
        'product_unit_id',
        'pack_id',
        'quantity',
        'price',
        'loss_amount',
        'loss_reason',
        'condition',
        'data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'loss_amount' => 'decimal:2',
    ];

    /**
     * Condition constants
     */
    const CONDITION_DAMAGED = 'damaged';
    const CONDITION_EXPIRED = 'expired';
    const CONDITION_STOLEN = 'stolen';
    const CONDITION_OTHER = 'other';

    /**
     * Get condition options
     */
    public static function getConditionOptions(): array
    {
        return [
            self::CONDITION_DAMAGED => 'Endommagé',
            self::CONDITION_EXPIRED => 'Expiré',
            self::CONDITION_STOLEN => 'Volé',
            self::CONDITION_OTHER => 'Autre',
        ];
    }

    /**
     * Get the stockLoss that owns the LostProduct
     */
    public function stockLoss(): BelongsTo
    {
        return $this->belongsTo(StockLoss::class);
    }

    /**
     * Get the productUnit that owns the LostProduct
     */
    public function productUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class);
    }

    /**
     * Get the pack that owns the LostProduct
     */
    public function pack(): BelongsTo
    {
        return $this->belongsTo(Pack::class);
    }

    /**
     * Calculate loss amount for the lost product
     */
    public function calculateLossAmount(): void
    {
        $this->loss_amount = $this->price * $this->quantity;
        $this->save();
    }
}
