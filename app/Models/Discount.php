<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discount extends Model
{
    protected $fillable = [
        'name',
        'description',
        'type',
        'value',
        'start_date',
        'end_date',
        'is_active',
        'min_quantity',
        'priority',
        'max_uses',
        'used_count',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
        'min_quantity' => 'integer',
        'priority' => 'integer',
        'max_uses' => 'integer',
        'used_count' => 'integer',
    ];

    /**
     * Get the formatted discount value for display
     */
    public function getFormattedValueAttribute(): string
    {
        if ($this->type === 'percentage') {
            return $this->value . '%';
        }

        return '₹' . number_format($this->value, 2);
    }

    /**
     * Check if the discount is currently valid
     */
    public function isValid(): bool
    {
        // Check if active
        if (!$this->is_active) {
            return false;
        }

        // Check date range
        $now = Carbon::now();
        if ($this->start_date && $now->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && $now->gt($this->end_date)) {
            return false;
        }

        // Check max usage
        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Calculate the discount amount for a given price
     */
    public function calculateDiscount(float $price, int $quantity = 1): float
    {
        // Check if minimum quantity is met
        if ($quantity < $this->min_quantity) {
            return 0;
        }

        if ($this->type === 'percentage') {
            return round(($price * $this->value) / 100, 2);
        }

        // For fixed amount
        return min($price, $this->value); // Cannot discount more than the price
    }

    /**
     * Increment the usage count
     */
    public function incrementUsage(): void
    {
        $this->used_count++;
        $this->save();
    }

    /**
     * Get the products associated with this discount
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    /**
     * Get the categories associated with this discount
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ProductCategory::class, 'discount_category',
            'discount_id', 'product_category_id')
            ->using(DiscountCategory::class);
    }


    /**
     * Get the applied discount records
     */
    public function appliedDiscounts(): HasMany
    {
        return $this->hasMany(AppliedDiscount::class);
    }
}
