<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Pack extends Model
{
    /** @use HasFactory<\Database\Factories\PackFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'store_id',
        'product_unit_id',
        'name',
        'price',
        'data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
    ];

    /**
     * Get the store that owns the Pack
     *
     * @return BelongsTo
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    // /**
    //  * Get all of the products for the Pack
    //  *
    //  * @return BelongsToMany
    //  */
    // public function products()
    // {
    //     return $this->belongsToMany(Product::class);
    // }

    /**
     * The productUnits that belong to the Pack
     *
     * @return BelongsToMany
     */
    public function productUnits(): BelongsToMany
    {
        return $this->belongsToMany(ProductUnit::class);
    }

    /**
     * Get all of the packProducts for the Pack
     *
     * @return HasMany
     */
    public function packProducts(): HasMany
    {
        return $this->hasMany(PackProduct::class);
    }
}
