<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShippingMethod extends Model
{
    use SoftDeletes;

    public const ROUTE_FROM = 'China';

    public const ROUTE_TO = 'Tajikistan';

    protected $fillable = [
        'name',
        'code',
        'volumetric_divisor',
        'minimum_charge',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'volumetric_divisor' => 'integer',
            'minimum_charge' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
    }

    public function activeRates(): HasMany
    {
        return $this->rates()->where('is_active', true)->orderBy('min_weight');
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }
}
