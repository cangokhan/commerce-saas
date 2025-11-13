<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'domain',
        'database',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the users for the tenant.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the stores for the tenant.
     */
    public function stores(): HasMany
    {
        return $this->hasMany(\App\Modules\Store\Models\Store::class);
    }

    /**
     * Get the categories for the tenant.
     */
    public function categories(): HasMany
    {
        return $this->hasMany(\App\Modules\Product\Models\Category::class);
    }

    /**
     * Get the products for the tenant.
     */
    public function products(): HasMany
    {
        return $this->hasMany(\App\Modules\Product\Models\Product::class);
    }
}
