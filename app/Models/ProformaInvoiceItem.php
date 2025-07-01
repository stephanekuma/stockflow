<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProformaInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'proforma_invoice_id',
        'product_unit_id',
        'pack_id',
        'quantity',
        'price',
        'total',
        'discount',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
        'quantity' => 'decimal:2',
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

        static::saving(function ($item) {
            // Validation: au moins product_unit_id ou pack_id doit être rempli
            if (empty($item->product_unit_id) && empty($item->pack_id)) {
                throw new \InvalidArgumentException('Either product_unit_id or pack_id must be provided.');
            }

            // Validation: les deux ne peuvent pas être remplis en même temps
            if (!empty($item->product_unit_id) && !empty($item->pack_id)) {
                throw new \InvalidArgumentException('Cannot have both product_unit_id and pack_id.');
            }
        });
    }

    /**
     * Get the proforma invoice that owns the item
     */
    public function proformaInvoice(): BelongsTo
    {
        return $this->belongsTo(ProformaInvoice::class);
    }

    /**
     * Get the product unit that owns the item
     */
    public function productUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class);
    }

    /**
     * Get the pack that owns the item
     */
    public function pack(): BelongsTo
    {
        return $this->belongsTo(Pack::class);
    }

    /**
     * Calculate total for the item
     */
    public function calculateTotal(): void
    {
        $this->total = ($this->quantity * $this->price) - $this->discount;
        $this->save();
    }

    /**
     * Check if this is a pack item
     */
    public function isPack(): bool
    {
        return !empty($this->pack_id);
    }

    /**
     * Check if this is a product item
     */
    public function isProduct(): bool
    {
        return !empty($this->product_unit_id);
    }

    /**
     * Get the name of the item (product or pack)
     */
    public function getItemName(): string
    {
        if ($this->isPack()) {
            return $this->pack->name ?? 'N/A';
        }

        return $this->productUnit->product->name ?? 'N/A';
    }
}
