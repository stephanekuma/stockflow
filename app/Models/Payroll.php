<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    protected $fillable = [
        'store_id',
        'employee_id',
        'base_amount',
        'bonus',
        'deductions',
        'total_amount',
        'payment_date',
        'payment_method',
        'notes',
        'status',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'base_amount' => 'decimal:2',
        'bonus' => 'decimal:2',
        'deductions' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
