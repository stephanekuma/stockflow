<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProformaInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'customer_id',
        'invoice_number',
        'issued_at',
        'valid_until',
        'subtotal',
        'total',
        'discount',
        'data',
        'notes',
        'status',
        'converted_to_sale_id',
    ];

    protected $casts = [
        'data' => 'array',
        'issued_at' => 'datetime',
        'valid_until' => 'datetime',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'discount' => 'decimal:2',
    ];

    /**
     * Status constants
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CONVERTED = 'converted';

    /**
     * Get status options
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_SENT => 'Envoyée',
            self::STATUS_ACCEPTED => 'Acceptée',
            self::STATUS_REJECTED => 'Rejetée',
            self::STATUS_EXPIRED => 'Expirée',
            self::STATUS_CONVERTED => 'Convertie en vente',
        ];
    }

    /**
     * Get the store that owns the ProformaInvoice
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the customer that owns the ProformaInvoice
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get all of the items for the ProformaInvoice
     */
    public function items(): HasMany
    {
        return $this->hasMany(ProformaInvoiceItem::class);
    }

    /**
     * Get the sale this proforma was converted to
     */
    public function convertedToSale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'converted_to_sale_id');
    }

    /**
     * Generate invoice number for the proforma
     */
    public static function generateInvoiceNumber(): string
    {
        $lastProforma = self::latest()->first();
        $lastNumber = $lastProforma ? (int) str_replace('PRO-', '', $lastProforma->invoice_number ?? '0') : 0;
        $nextNumber = $lastNumber + 1;

        return 'PRO-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate totals for the proforma
     */
    public function calculateTotals(): void
    {
        $subtotal = $this->items->sum(function ($item) {
            return ($item->quantity * $item->price);
        });

        $discount = $this->items->sum('discount');
        $total = $subtotal - $discount;

        $this->update([
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
        ]);
    }

    /**
     * Convert proforma to sale
     */
    public function convertToSale(): Sale
    {
        // Create the sale
        $sale = Sale::create([
            'store_id' => $this->store_id,
            'customer_id' => $this->customer_id,
            'invoice_number' => Sale::generateInvoiceNumber(),
            'sold_at' => now(),
            'subtotal' => $this->subtotal,
            'total' => $this->total,
            'discount' => $this->discount,
            'notes' => "Convertie depuis la facture proforma {$this->invoice_number}",
            'status' => Sale::STATUS_PENDING,
        ]);

        // Copy items to sold products
        foreach ($this->items as $item) {
            SoldProduct::create([
                'sale_id' => $sale->id,
                'product_unit_id' => $item->product_unit_id,
                'pack_id' => $item->pack_id,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'discount' => $item->discount,
                'total' => $item->total,
            ]);
        }

        // Update proforma status
        $this->update([
            'status' => self::STATUS_CONVERTED,
            'converted_to_sale_id' => $sale->id,
        ]);

        return $sale;
    }

    /**
     * Check if proforma is expired
     */
    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }

    /**
     * Check if proforma can be converted
     */
    public function canBeConverted(): bool
    {
        return in_array($this->status, [self::STATUS_ACCEPTED, self::STATUS_SENT]) && !$this->isExpired();
    }
}
