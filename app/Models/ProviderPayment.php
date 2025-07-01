<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'provider_id',
        'provider_debt_id',
        'amount',
        'payment_date',
        'method',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function debt()
    {
        return $this->belongsTo(ProviderDebt::class, 'provider_debt_id');
    }
}
