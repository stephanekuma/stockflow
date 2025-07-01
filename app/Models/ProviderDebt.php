<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderDebt extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'purchase_id',
        'amount',
        'paid',
        'due_date',
        'status',
        'notes',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function payments()
    {
        return $this->hasMany(ProviderPayment::class);
    }

    public function getRemainingAttribute()
    {
        return $this->amount - $this->paid;
    }
}
