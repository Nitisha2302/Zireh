<?php

namespace App\Livewire\Admin\ShippingRate\Concerns;

use App\Models\ShippingRate;

trait ManagesShippingRateForm
{
    public string $mode = ShippingRate::MODE_WEIGHT;

    public function updatedMode(): void
    {
        $this->minWeight = '';
        $this->maxWeight = '';
        $this->resetErrorBag(['minWeight', 'maxWeight']);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0: float, 1: float}
     */
    protected function storedBoundsFromForm(array $validated): array
    {
        $min = (float) $validated['minWeight'];
        $max = (float) $validated['maxWeight'];

        if (($validated['mode'] ?? ShippingRate::MODE_WEIGHT) === ShippingRate::MODE_WEIGHT) {
            return [ShippingRate::gramsToKg($min), ShippingRate::gramsToKg($max)];
        }

        return [$min, $max];
    }

    /**
     * @return array<string, mixed>
     */
    protected function shippingRateRules(): array
    {
        return [
            'shippingMethodId' => ['required', 'integer', 'exists:shipping_methods,id'],
            'warehouseId' => ['required', 'integer', 'exists:warehouses,id'],
            'mode' => ['required', 'in:'.ShippingRate::MODE_WEIGHT.','.ShippingRate::MODE_CM],
            'minWeight' => ['required', 'numeric', 'min:0'],
            'maxWeight' => ['required', 'numeric', 'gt:minWeight'],
            'ratePerKg' => ['required', 'numeric', 'gt:0'],
            'isActive' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function shippingRateValidationAttributes(): array
    {
        $isCm = $this->mode === ShippingRate::MODE_CM;

        return [
            'shippingMethodId' => __('admin.shipping_method'),
            'warehouseId' => __('admin.warehouse'),
            'mode' => __('admin.pickup_measurement_mode'),
            'minWeight' => $isCm ? __('admin.shipping_min_volumetric_weight') : __('admin.shipping_min_weight_g'),
            'maxWeight' => $isCm ? __('admin.shipping_max_volumetric_weight') : __('admin.shipping_max_weight_g'),
            'ratePerKg' => __('admin.shipping_rate_per_kg'),
        ];
    }
}
