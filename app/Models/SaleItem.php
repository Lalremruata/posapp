<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use HasFactory;
    protected $fillable = [
        "sale_id",
        "product_id",
        "quantity",
        "cost_price",
        "selling_price",
        "discount",
        "total_price",
        "sale_date",
    ];
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
    // Calculate profit for a single item
    public function getProfitAttribute()
    {
        return $this->selling_price * $this->quantity - $this->cost_price * $this->quantity;
    }

// Calculate margin percentage
    public function getMarginAttribute()
    {
        $revenue = $this->selling_price * $this->quantity;
        return $revenue > 0 ? ($this->profit / $revenue) * 100 : 0;
    }


}
