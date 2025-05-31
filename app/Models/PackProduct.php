<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackProduct extends Pivot
{
    // protected $table = 'pack_product';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var boolean
     */
    public $incrementing = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'store_id',
        'pack_id',
        'product_unit_id',
        'quantity',
    ];

    /**
     * Get the store that owns the PackProduct
     *
     * @return BelongsTo
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the productUnit that owns the PackProduct
     *
     * @return BelongsTo
     */
    public function productUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class);
    }

    /**
     * Get the pack that owns the PackProduct
     *
     * @return BelongsTo
     */
    public function pack(): BelongsTo
    {
        return $this->belongsTo(Pack::class);
    }
}
