<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerDeposit extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'customer_id',
        'amount',
        'note',
        'user_id',
        'store_id',
    ];

    /**
     * Get the store lié à ce dépôt
     */
    public function store()
    {
        return $this->belongsTo(\App\Models\Store::class);
    }

    /**
     * Get the customer lié à ce dépôt
     */
    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer::class);
    }

    /**
     * Get the user ayant saisi le dépôt
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
