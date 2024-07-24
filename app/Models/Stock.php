<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stock extends Model
{
    use HasFactory;
    protected $fillable = [
        'store_id',
        'product_id',
        'quantity',
    ];
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
    public function product():BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    public function stock(): HasMany
    {
        return $this->hasMany(Stock::class);
    }
}
