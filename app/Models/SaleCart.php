<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleCart extends Model
{
    use HasFactory;
    protected $fillable = [
        "user_id",
        "store_id",
        "stock_id",
        "product_id",
        "quantity",
        "cost_price",
        "selling_price",
        "total_price",
        "discount",
    ];
    public function product(){
        return $this->belongsTo(Product::class);
    }
    public function user(){
        return $this->belongsTo(User::class);
    }
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }
}
