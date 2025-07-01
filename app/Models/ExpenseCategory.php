<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    protected $fillable = ['name', 'description', 'store_id'];

    public function store()
    {
        return $this->belongsTo(\App\Models\Store::class);
    }
}
