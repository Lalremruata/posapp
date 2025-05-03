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
//        'selling_price',
//        'cost_price',
//        'quantity_in_stock',
        'barcode',
        'supplier_id'
    ];
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id', 'id');
    }
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }
    public function saleCart()
    {
        return $this->hasMany(SaleCart::class);
    }
    public function discountProducts()
    {
        return $this->hasMany(DiscountProduct::class);
    }
    public function getProductInfoAttribute()
    {
        return $this->product_name . ' - ' . $this->product_description;
    }

}
