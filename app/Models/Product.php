<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
