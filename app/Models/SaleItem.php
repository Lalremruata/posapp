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
}
