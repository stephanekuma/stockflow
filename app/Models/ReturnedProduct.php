<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReturnedProduct extends Model
{
    /** @use HasFactory<\Database\Factories\ReturnedProductFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'stock_return_id',
        'product_unit_id',
        'pack_id',
        'quantity',
        'price',
        'refund_amount',
        'return_reason',
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
        'refund_amount' => 'decimal:2',
    ];

    /**
     * Condition constants
     */
    const CONDITION_GOOD = 'good';
    const CONDITION_DAMAGED = 'damaged';
    const CONDITION_EXPIRED = 'expired';

    /**
     * Get condition options
     */
    public static function getConditionOptions(): array
    {
        return [
            self::CONDITION_GOOD => 'Bon état',
            self::CONDITION_DAMAGED => 'Endommagé',
            self::CONDITION_EXPIRED => 'Expiré',
        ];
    }

    /**
     * Get the stockReturn that owns the ReturnedProduct
     */
    public function stockReturn(): BelongsTo
    {
        return $this->belongsTo(StockReturn::class);
    }

    /**
     * Get the productUnit that owns the ReturnedProduct
     */
    public function productUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class);
    }

    /**
     * Get the pack that owns the ReturnedProduct
     */
    public function pack(): BelongsTo
    {
        return $this->belongsTo(Pack::class);
    }

    /**
     * Calculate refund amount for the returned product
     */
    public function calculateRefundAmount(): void
    {
        $refundPercentage = match($this->condition) {
            self::CONDITION_GOOD => 1.0, // 100% remboursement
            self::CONDITION_DAMAGED => 0.5, // 50% remboursement
            self::CONDITION_EXPIRED => 0.0, // 0% remboursement
            default => 0.0,
        };

        $this->refund_amount = $this->price * $this->quantity * $refundPercentage;
        $this->save();
    }
}
