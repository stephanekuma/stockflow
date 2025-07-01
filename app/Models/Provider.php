<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provider extends Model
{
    /** @use HasFactory<\Database\Factories\ProviderFactory> */
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
     * Get the provider's debts.
     *
     * @return HasMany
     */
    public function debts(): HasMany
    {
        return $this->hasMany(ProviderDebt::class);
    }

    /**
     * Get the provider's payments.
     *
     * @return HasMany
     */
    public function payments(): HasMany
    {
        return $this->hasMany(ProviderPayment::class);
    }

    /**
     * Get total debt amount.
     *
     * @return float
     */
    public function getTotalDebtAttribute(): float
    {
        return $this->debts()->sum('amount');
    }

    /**
     * Get total paid amount.
     *
     * @return float
     */
    public function getTotalPaidAttribute(): float
    {
        return $this->debts()->sum('paid');
    }

    /**
     * Get remaining debt amount.
     *
     * @return float
     */
    public function getRemainingDebtAttribute(): float
    {
        return $this->total_debt - $this->total_paid;
    }
}
