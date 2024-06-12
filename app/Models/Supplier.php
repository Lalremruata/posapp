<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;
    protected $fillable = [
        'supplier_name',
        'contact_name',
        'phone',
        'email',
        'address',
        'city',
        'state',
        'zip_code'
    ];
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
