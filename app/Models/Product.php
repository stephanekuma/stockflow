<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::created(function (Product $product) {
            // Log for debugging: Check what data is received on creation
            Log::info('Product created event fired for: ' . $product->id);
            $formData = request()->all(); // Prend toutes les données de la requête
            Log::info('Form data on creation:', is_array($formData) ? $formData : []);
            self::syncUnits($product, $formData['units_data'] ?? []); // units_data peut être à la racine
        });

        static::updated(function (Product $product) {
            // Log for debugging: Check what data is received on update
            Log::info('Product updated event fired for: ' . $product->id);
            $formData = request()->all(); // Prend toutes les données de la requête
            Log::info('Form data on update:', is_array($formData) ? $formData : []);
            self::syncUnits($product, $formData['units_data'] ?? []); // units_data peut être à la racine
        });

        static::deleting(function (Product $product) {
            $product->units()->delete();
        });
    }

    protected static function syncUnits(Product $product, ?array $unitsData): void
    {
        \Illuminate\Support\Facades\Log::info('syncUnits unitsData', ['unitsData' => $unitsData]);

        Log::info('syncUnits called for Product ID: ' . $product->id, ['unitsData' => $unitsData]);

        // If no units data provided or it's empty, delete all existing units for this product
        if (empty($unitsData)) {
            $product->units()->delete();
            Log::info('Deleted all units for Product ID: ' . $product->id);
            return;
        }

        $existingUnitIds = $product->units->pluck('id')->toArray();
        $submittedUnitIds = [];

        foreach ($unitsData as $unitItem) {
            // Ensure unitData is an array and has required fields
            if (!is_array($unitItem) || !isset($unitItem['unit_id']) || !isset($unitItem['quantity']) || !isset($unitItem['cost_price']) || !isset($unitItem['price'])) {
                Log::warning('Skipping invalid unit data:', $unitItem);
                continue; // Skip invalid unit data
            }

            // Check if it's an existing record (has an ID that is not null)
            if (isset($unitItem['id']) && $unitItem['id'] !== null) {
                // Cast the ID to an integer for strict comparison if needed
                $unitId = (int) $unitItem['id'];

                // Find the existing unit
                $existingUnit = $product->units()->find($unitId);

                if ($existingUnit) {
                    // Update existing unit
                    $existingUnit->update([
                        'unit_id' => $unitItem['unit_id'],
                        'quantity' => $unitItem['quantity'],
                        'cost_price' => $unitItem['cost_price'],
                        'price' => $unitItem['price'],
                    ]);
                    Log::info('Updated unit ID: ' . $unitId, $unitItem);
                    $submittedUnitIds[] = $unitId; // Add to submitted IDs
                } else {
                    // This scenario shouldn't happen often if IDs are always passed correctly,
                    // but it catches cases where an ID was sent but the record doesn't exist.
                    Log::warning('Unit ID ' . $unitId . ' not found for update, attempting to create new.', $unitItem);
                    // Treat as new if the ID didn't match an existing record
                    $newUnit = $product->units()->create([
                        'store_id' => Filament::getTenant()->id,
                        'product_id' => $product->id,
                        'unit_id' => $unitItem['unit_id'],
                        'quantity' => $unitItem['quantity'],
                        'cost_price' => $unitItem['cost_price'],
                        'price' => $unitItem['price'],
                    ]);
                    Log::info('Created new unit due to ID mismatch. New ID: ' . $newUnit->id, $unitItem);
                    $submittedUnitIds[] = $newUnit->id;
                }
            } else {
                // No ID or ID is null, it's a new record to create
                $newUnit = $product->units()->create([
                    'store_id' => Filament::getTenant()->id,
                    'product_id' => $product->id,
                    'unit_id' => $unitItem['unit_id'],
                    'quantity' => $unitItem['quantity'],
                    'cost_price' => $unitItem['cost_price'],
                    'price' => $unitItem['price'],
                ]);
                Log::info('Created new unit. New ID: ' . $newUnit->id, $unitItem);
                $submittedUnitIds[] = $newUnit->id; // Add new unit's ID to submitted IDs
            }
        }

        // Delete units that were in the database but not in the submitted data (removed from repeater)
        $unitsToDelete = array_diff($existingUnitIds, $submittedUnitIds);
        if (!empty($unitsToDelete)) {
            $product->units()->whereIn('id', $unitsToDelete)->delete();
            Log::info('Deleted units: ', $unitsToDelete);
        } else {
            Log::info('No units to delete for Product ID: ' . $product->id);
        }
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'store_id',
        'category_id',
        'brand_id',
        'name',
        'sku',
        'description',
        'is_perishable',
        'expiry_date',
        'expiry_alert_days',
        'expiry_status',
        'expiry_notes',
        'image',
        'data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'is_perishable' => 'boolean',
        'expiry_date' => 'date',
        'expiry_alert_days' => 'integer',
    ];

    /**
     * Get the store that owns the product.
     *
     * @return BelongsTo
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the category that owns the product.
     *
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the brand that owns the product.
     *
     * @return BelongsTo
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get all of the product units for the Product
     *
     * @return HasMany
     */
    public function units(): HasMany
    {
        return $this->hasMany(ProductUnit::class);
    }

    public function stockHistories()
    {
        return $this->hasManyThrough(
            \App\Models\StockHistory::class,
            \App\Models\ProductUnit::class,
            'product_id', // Foreign key on ProductUnit
            'product_unit_id', // Foreign key on StockHistory
            'id', // Local key on Product
            'id' // Local key on ProductUnit
        );
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)->with('units')->firstOrFail();
    }

    /**
     * Check if the product is expired
     */
    public function isExpired(): bool
    {
        if (!$this->is_perishable || !$this->expiry_date) {
            return false;
        }

        return $this->expiry_date->isPast();
    }

    /**
     * Check if the product is expiring soon (within alert days)
     */
    public function isExpiringSoon(): bool
    {
        if (!$this->is_perishable || !$this->expiry_date) {
            return false;
        }

        $alertDate = now()->addDays($this->expiry_alert_days);
        return $this->expiry_date->lte($alertDate) && !$this->isExpired();
    }

    /**
     * Get the expiry status
     */
    public function getExpiryStatus(): string
    {
        if (!$this->is_perishable || !$this->expiry_date) {
            return 'good';
        }

        if ($this->isExpired()) {
            return 'expired';
        }

        if ($this->isExpiringSoon()) {
            return 'warning';
        }

        return 'good';
    }

    /**
     * Get days until expiry
     */
    public function getDaysUntilExpiry(): ?int
    {
        if (!$this->is_perishable || !$this->expiry_date) {
            return null;
        }

        return now()->diffInDays($this->expiry_date, false);
    }

    /**
     * Scope to get only perishable products
     */
    public function scopePerishable($query)
    {
        return $query->where('is_perishable', true);
    }

    /**
     * Scope to get expired products
     */
    public function scopeExpired($query)
    {
        return $query->where('is_perishable', true)
            ->where('expiry_date', '<', now());
    }

    /**
     * Scope to get products expiring soon
     */
    public function scopeExpiringSoon($query, $days = null)
    {
        $alertDays = $days ?? $this->expiry_alert_days ?? 30;
        $alertDate = now()->addDays($alertDays);

        return $query->where('is_perishable', true)
            ->where('expiry_date', '<=', $alertDate)
            ->where('expiry_date', '>=', now());
    }

    /**
     * Scope to get products ordered by expiry date (soonest first)
     */
    public function scopeOrderByExpiry($query)
    {
        return $query->orderBy('expiry_date', 'asc');
    }

    /**
     * Update expiry status automatically
     */
    public function updateExpiryStatus(): void
    {
        $this->expiry_status = $this->getExpiryStatus();
        $this->save();
    }
}
