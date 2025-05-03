<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Credit extends Model
{
    protected $fillable = [
        'customer_id',
        'amount',
        'description',
        'type', // 'credit' or 'debit'
        'balance',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CreditPayment::class);
    }
}
