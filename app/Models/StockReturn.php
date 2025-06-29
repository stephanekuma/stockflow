<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockReturn extends Model
{
    /** @use HasFactory<\Database\Factories\StockReturnFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'store_id',
        'customer_id',
        'sale_id',
        'return_number',
        'returned_at',
        'type',
        'status',
        'reason',
        'notes',
        'total_refund',
        'data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'returned_at' => 'datetime',
        'total_refund' => 'decimal:2',
    ];

    /**
     * Type constants
     */
    const TYPE_RETURN = 'return';
    const TYPE_EXCHANGE = 'exchange';
    const TYPE_REFUND = 'refund';

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_COMPLETED = 'completed';

    /**
     * Get type options
     */
    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_RETURN => 'Retour',
            self::TYPE_EXCHANGE => 'Échange',
            self::TYPE_REFUND => 'Remboursement',
        ];
    }

    /**
     * Get status options
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'En attente',
            self::STATUS_APPROVED => 'Approuvé',
            self::STATUS_REJECTED => 'Rejeté',
            self::STATUS_COMPLETED => 'Terminé',
        ];
    }

    /**
     * Get the store that owns the StockReturn
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the customer that owns the StockReturn
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the sale that owns the StockReturn
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get all of the returnedProducts for the StockReturn
     */
    public function returnedProducts(): HasMany
    {
        return $this->hasMany(ReturnedProduct::class);
    }

    /**
     * Generate return number for the stock return
     */
    public static function generateReturnNumber(): string
    {
        $lastReturn = self::latest()->first();
        $lastNumber = $lastReturn ? (int) str_replace('RET-', '', $lastReturn->return_number ?? '0') : 0;
        $nextNumber = $lastNumber + 1;

        return 'RET-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate total refund for the return
     */
    public function calculateTotalRefund(): void
    {
        $totalRefund = $this->returnedProducts->sum('refund_amount');

        $this->update([
            'total_refund' => $totalRefund,
        ]);
    }
}
