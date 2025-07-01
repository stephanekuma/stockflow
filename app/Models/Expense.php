<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = ['store_id', 'expense_category_id', 'user_id', 'date', 'amount', 'description'];

    public function store()
    {
        return $this->belongsTo(\App\Models\Store::class);
    }
}
