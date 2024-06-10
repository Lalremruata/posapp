<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory;
    protected $fillable=[
        'product_name',
        'product_description',
        'category_id',
        'selling_price',
        'cost_price',
        'quantity_in_stock',
        'barcode',
        'supplier_id'
    ];
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ProductCategory::class);
    }
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
