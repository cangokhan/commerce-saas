<?php

namespace App\Modules\Store\Models;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Store extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'logo',
        'is_active',
        'tenant_id',
        'vendor_id',
        'xml_import_url',
        'xml_import_enabled',
        'xml_import_schedule',
        'xml_import_last_run',
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
            'xml_import_enabled' => 'boolean',
            'xml_import_last_run' => 'datetime',
        ];
    }

    /**
     * Get the tenant that owns the store.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the vendor (user) that owns the store.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /**
     * Get the products for the store.
     */
    public function products()
    {
        return $this->hasMany(\App\Modules\Product\Models\Product::class);
    }
}
