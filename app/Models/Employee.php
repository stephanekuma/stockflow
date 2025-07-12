<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'store_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'position',
        'base_salary',
        'hire_date',
        'address',
        'documents',
        'is_active',
    ];

    protected $casts = [
        'documents' => 'array',
        'hire_date' => 'date',
        'is_active' => 'boolean',
        'base_salary' => 'decimal:2',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
