<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegister extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'name',
        'initial_balance',
        'current_balance',
        'is_closed',
    ];

    protected $casts = [
        'is_closed' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CashRegisterTransaction::class);
    }

    public function isOpen(): bool
    {
        return !$this->is_closed;
    }

    public function isClosed(): bool
    {
        return $this->is_closed;
    }

    public function close(): void
    {
        $this->update(['is_closed' => true]);
    }

    public function open(): void
    {
        $this->update(['is_closed' => false]);
    }
}
