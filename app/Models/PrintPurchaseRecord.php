<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintPurchaseRecord extends Model
{
    /** @use HasFactory<\Database\Factories\PrintPurchaseRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'user_id',
        'data',
    ];

    /**
     * Get the user that owns the PrintPurchaseRecord
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the purchase that owns the PrintPurchaseRecord
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    protected $casts = [
        'data' => 'array',
    ];
}
