<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'store_id',
        'name',
        'email',
        'phone',
        'address',
        'data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    /**
     * Get the store that owns the customer.
     *
     * @return BelongsTo
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get all deposits for the customer
     */
    public function deposits(): HasMany
    {
        return $this->hasMany(\App\Models\CustomerDeposit::class);
    }

    /**
     * Get all sales for the customer
     */
    public function sales()
    {
        return $this->hasMany(\App\Models\Sale::class);
    }

    /**
     * Get all sale payments for the customer
     */
    public function salePayments()
    {
        return $this->hasMany(\App\Models\SalePayment::class);
    }

    /**
     * Solde disponible du client (dépôts - achats réglés)
     */
    public function getBalanceAttribute()
    {
        $deposits = $this->deposits()->sum('amount');
        $salesPaid = $this->sales()->get()->sum(function ($sale) {
            return min($sale->total, $sale->payments()->sum('amount') + $this->deposits()->sum('amount'));
        });
        return $deposits - $salesPaid;
    }
}
