<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShippingRate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'shipping_method_id',
        'min_weight',
        'max_weight',
        'rate_per_kg',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_weight' => 'decimal:2',
            'max_weight' => 'decimal:2',
            'rate_per_kg' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function weightRangeLabel(): string
    {
        return number_format((float) $this->min_weight, 2).' KG - '.number_format((float) $this->max_weight, 2).' KG';
    }
}
