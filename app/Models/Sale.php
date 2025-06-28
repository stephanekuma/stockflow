<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sale extends Model
{
    /** @use HasFactory<\Database\Factories\SaleFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'store_id',
        'customer_id',
        'invoice_number',
        'sold_at',
        'subtotal',
        'total',
        'discount',
        'data',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'sold_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'discount' => 'decimal:2',
    ];

    /**
     * Get the store that owns the Sale
     *
     * @return BelongsTo
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the customer that owns the Sale
     *
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get all of the soldProducts for the Sale
     *
     * @return HasMany
     */
    public function soldProducts(): HasMany
    {
        return $this->hasMany(SoldProduct::class);
    }

    /**
     * Generate invoice number for the sale
     *
     * @return string
     */
    public static function generateInvoiceNumber(): string
    {
        $lastSale = self::latest()->first();
        $lastNumber = $lastSale ? (int) str_replace('SAL-', '', $lastSale->invoice_number ?? '0') : 0;
        $nextNumber = $lastNumber + 1;

        return 'SAL-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate totals for the sale
     *
     * @return void
     */
    public function calculateTotals(): void
    {
        $subtotal = $this->soldProducts->sum(function ($soldProduct) {
            return ($soldProduct->quantity * $soldProduct->price);
        });

        $discount = $this->soldProducts->sum('discount');
        $total = $subtotal - $discount;

        $this->update([
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
        ]);
    }

    /**
     * Get all payments for this sale
     */
    public function payments()
    {
        return $this->hasMany(\App\Models\SalePayment::class);
    }

    /**
     * Montant total payé (somme des paiements)
     */
    public function getTotalPaidAttribute()
    {
        return $this->payments()->sum('amount');
    }

    /**
     * Montant restant dû
     */
    public function getAmountDueAttribute()
    {
        return max(0, $this->total - $this->total_paid);
    }

    /**
     * Gère les paiements lors d'une vente (solde, paiement immédiat, crédit)
     */
    public function handlePayments(float $amountPaid = 0): void
    {
        $customer = $this->customer;
        if ($customer) {
            $balance = $customer->balance;
            $toPay = $this->total;
            $usedBalance = min($balance, $toPay);
            if ($usedBalance > 0) {
                \App\Models\SalePayment::create([
                    'store_id' => $this->store_id,
                    'sale_id' => $this->id,
                    'customer_id' => $customer->id,
                    'amount' => $usedBalance,
                    'user_id' => \Illuminate\Support\Facades\Auth::id(),
                    'note' => 'Paiement automatique via solde client',
                ]);
            }
            if ($amountPaid > 0) {
                \App\Models\SalePayment::create([
                    'store_id' => $this->store_id,
                    'sale_id' => $this->id,
                    'customer_id' => $customer->id,
                    'amount' => $amountPaid,
                    'user_id' => \Illuminate\Support\Facades\Auth::id(),
                    'note' => 'Paiement immédiat lors de la vente',
                ]);
            }
        }
    }
}
