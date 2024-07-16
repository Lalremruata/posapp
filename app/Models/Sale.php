<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasFactory;
    protected $fillable = [
        'store_id','user_id', 'payment_method', 'sale_date', 'customer_id', 'total_amount'
    ];
    public function user(): BelongsTo{
        return $this->belongsTo(User::class);
    }
    public function customer(): BelongsTo{
        return $this->belongsTo(Customer::class);
    }
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
