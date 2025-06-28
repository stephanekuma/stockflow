<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SoldProduct extends Model
{
    /** @use HasFactory<\Database\Factories\SoldProductFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sale_id',
        'product_unit_id',
        'pack_id',
        'quantity',
        'price',
        'total',
        'discount',
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
        'total' => 'decimal:2',
        'discount' => 'decimal:2',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($soldProduct) {
            // Validation: au moins product_unit_id ou pack_id doit être rempli
            if (empty($soldProduct->product_unit_id) && empty($soldProduct->pack_id)) {
                throw new \InvalidArgumentException('Either product_unit_id or pack_id must be provided.');
            }

            // Validation: les deux ne peuvent pas être remplis en même temps
            if (!empty($soldProduct->product_unit_id) && !empty($soldProduct->pack_id)) {
                throw new \InvalidArgumentException('Cannot have both product_unit_id and pack_id.');
            }
        });
    }

    /**
     * Get the sale that owns the SoldProduct
     *
     * @return BelongsTo
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the productUnit that owns the SoldProduct
     *
     * @return BelongsTo
     */
    public function productUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class);
    }

    /**
     * Get the pack that owns the SoldProduct
     *
     * @return BelongsTo
     */
    public function pack(): BelongsTo
    {
        return $this->belongsTo(Pack::class);
    }

    /**
     * Calculate total for the sold product
     *
     * @return void
     */
    public function calculateTotal(): void
    {
        $this->total = ($this->quantity * $this->price) - $this->discount;
        $this->save();
    }

    /**
     * Check if this is a pack sale
     *
     * @return bool
     */
    public function isPack(): bool
    {
        return !empty($this->pack_id);
    }

    /**
     * Check if this is a product sale
     *
     * @return bool
     */
    public function isProduct(): bool
    {
        return !empty($this->product_unit_id);
    }

    /**
     * Get the name of the sold item (product or pack)
     *
     * @return string
     */
    public function getItemName(): string
    {
        if ($this->isPack()) {
            return $this->pack->name ?? 'N/A';
        }

        return $this->productUnit->product->name ?? 'N/A';
    }
}
