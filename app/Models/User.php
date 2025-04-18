<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'store_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
    public function saleCart()
    {
        return $this->hasMany(SaleCart::class);
    }
    public function hasPermission(string $permission): bool
    {
        $permissionsArray = [];

        foreach($this->roles as $role){
            foreach($role->permissions as $singlePermission){
                $permissionsArray[] = $singlePermission->name;
            }
        }

        return collect($permissionsArray)->unique()->contains($permission);
    }
}
