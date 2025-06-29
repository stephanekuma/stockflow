<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockLoss extends Model
{
    /** @use HasFactory<\Database\Factories\StockLossFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'store_id',
        'user_id',
        'loss_number',
        'lost_at',
        'type',
        'status',
        'reason',
        'notes',
        'total_loss',
        'data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'lost_at' => 'datetime',
        'total_loss' => 'decimal:2',
    ];

    /**
     * Type constants
     */
    const TYPE_DAMAGED = 'damaged';
    const TYPE_EXPIRED = 'expired';
    const TYPE_STOLEN = 'stolen';
    const TYPE_OTHER = 'other';

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
            self::TYPE_DAMAGED => 'Endommagé',
            self::TYPE_EXPIRED => 'Expiré',
            self::TYPE_STOLEN => 'Volé',
            self::TYPE_OTHER => 'Autre',
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
     * Get the store that owns the StockLoss
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the user that owns the StockLoss
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all of the lostProducts for the StockLoss
     */
    public function lostProducts(): HasMany
    {
        return $this->hasMany(LostProduct::class);
    }

    /**
     * Generate loss number for the stock loss
     */
    public static function generateLossNumber(): string
    {
        $lastLoss = self::latest()->first();
        $lastNumber = $lastLoss ? (int) str_replace('LOSS-', '', $lastLoss->loss_number ?? '0') : 0;
        $nextNumber = $lastNumber + 1;

        return 'LOSS-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate total loss for the stock loss
     */
    public function calculateTotalLoss(): void
    {
        $totalLoss = $this->lostProducts->sum('loss_amount');

        $this->update([
            'total_loss' => $totalLoss,
        ]);
    }
}
