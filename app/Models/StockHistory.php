<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockHistory extends Model
{
    protected $fillable = [
        'store_id',
        'product_unit_id',
        'user_id',
        'provider_id',
        'type',
        'quantity_before',
        'quantity_after',
        'quantity_change',
        'note',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the product unit lié à ce mouvement de stock
     */
    public function productUnit()
    {
        return $this->belongsTo(\App\Models\ProductUnit::class);
    }

    /**
     * Get the user lié à ce mouvement de stock
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
