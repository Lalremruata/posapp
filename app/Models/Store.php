<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;
    protected $fillable = [
        'store_name',
        'store_type',
        'location'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }
    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }
    public function saleCart()
    {
        return $this->hasMany(SaleCart::class, 'StoreID');
    }

}
