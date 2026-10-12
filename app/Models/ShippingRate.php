<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShippingRate extends Model
{
    use SoftDeletes;

    public const MODE_WEIGHT = 'weight';

    public const MODE_CM = 'cm';

    protected $fillable = [
        'shipping_method_id',
        'warehouse_id',
        'mode',
        'min_weight',
        'max_weight',
        'rate_per_kg',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_weight' => 'decimal:3',
            'max_weight' => 'decimal:3',
            'rate_per_kg' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function billingMode(): string
    {
        return $this->mode === self::MODE_CM ? self::MODE_CM : self::MODE_WEIGHT;
    }

    public function isWeightMode(): bool
    {
        return $this->billingMode() === self::MODE_WEIGHT;
    }

    public function isCmMode(): bool
    {
        return $this->billingMode() === self::MODE_CM;
    }

    public static function gramsToKg(float $grams): float
    {
        return round($grams / 1000, 3);
    }

    public static function kgToGrams(float $kg): float
    {
        return round($kg * 1000, 3);
    }

    public function minWeightGrams(): float
    {
        return self::kgToGrams((float) $this->min_weight);
    }

    public function maxWeightGrams(): float
    {
        return self::kgToGrams((float) $this->max_weight);
    }

    public function weightRangeLabel(): string
    {
        if ($this->isWeightMode()) {
            return number_format($this->minWeightGrams(), 0).' g - '.number_format($this->maxWeightGrams(), 0).' g';
        }

        return number_format((float) $this->min_weight, 3).' KG - '.number_format((float) $this->max_weight, 3).' KG';
    }

    /**
     * @return array<string, mixed>
     */
    public function toMethodRatePayload(): array
    {
        return [
            'id' => $this->id,
            'mode' => $this->billingMode(),
            'min_weight' => (float) $this->min_weight,
            'max_weight' => (float) $this->max_weight,
            'min_weight_g' => $this->isWeightMode() ? $this->minWeightGrams() : null,
            'max_weight_g' => $this->isWeightMode() ? $this->maxWeightGrams() : null,
            'rate_per_kg' => (float) $this->rate_per_kg,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toCalculationRatePayload(): array
    {
        return array_merge($this->toMethodRatePayload(), [
            'warehouse_id' => $this->warehouse_id,
        ]);
    }
}
