<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppliedDiscount extends Model
{
    protected $fillable = [
        'sale_id',
        'sale_item_id',
        'discount_id',
        'original_price',
        'discount_amount',
        'final_price',
    ];

    /**
     * Get the sale that this discount was applied to
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the sale item that this discount was applied to
     */
    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    /**
     * Get the discount that was applied
     */
    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }
}
