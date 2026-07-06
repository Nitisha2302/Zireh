<?php

namespace App\Services\Shipping;

use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Repositories\Shipping\ShippingRateRepository;
use Illuminate\Validation\ValidationException;

class ShippingRateService
{
    public function __construct(
        protected ShippingRateRepository $repository,
        protected ShippingMethodService $shippingMethodService,
    ) {}

    public function create(array $data): ShippingRate
    {
        $validated = $this->validatePayload($data);
        $this->assertValidRange(
            $validated['shipping_method_id'],
            $validated['min_weight'],
            $validated['max_weight']
        );

        $rate = $this->repository->create($validated);
        $this->shippingMethodService->clearCache();

        return $rate->load('shippingMethod');
    }

    public function update(ShippingRate $rate, array $data): ShippingRate
    {
        $validated = $this->validatePayload($data, $rate->id);
        $this->assertValidRange(
            $validated['shipping_method_id'],
            $validated['min_weight'],
            $validated['max_weight'],
            $rate->id
        );

        $rate = $this->repository->update($rate, $validated);
        $this->shippingMethodService->clearCache();

        return $rate;
    }

    public function delete(ShippingRate $rate): void
    {
        $this->repository->delete($rate);
        $this->shippingMethodService->clearCache();
    }

    public function toggleStatus(ShippingRate $rate): ShippingRate
    {
        $rate = $this->repository->update($rate, [
            'is_active' => ! $rate->is_active,
        ]);
        $this->shippingMethodService->clearCache();

        return $rate;
    }

    public function assertValidRange(
        int $methodId,
        float $minWeight,
        float $maxWeight,
        ?int $ignoreId = null
    ): void {
        if ($minWeight >= $maxWeight) {
            throw ValidationException::withMessages([
                'maxWeight' => [__('admin.shipping_rate_max_weight_gt_min')],
            ]);
        }

        $existingRates = $this->repository->forMethod($methodId, $ignoreId);

        foreach ($existingRates as $rate) {
            if ($minWeight <= (float) $rate->max_weight && (float) $rate->min_weight <= $maxWeight) {
                throw ValidationException::withMessages([
                    'minWeight' => [__('admin.shipping_rate_overlap')],
                ]);
            }
        }
    }

    public function calculate(
        string $methodCode,
        float $weightKg,
        ?float $lengthCm = null,
        ?float $widthCm = null,
        ?float $heightCm = null,
    ): array {
        $method = $this->shippingMethodService->findActiveByCode($methodCode);

        $actualWeight = max(0, $weightKg);
        $volumetricWeight = null;

        if ($lengthCm !== null && $widthCm !== null && $heightCm !== null) {
            $volumetricWeight = round(
                ($lengthCm * $widthCm * $heightCm) / $method->volumetric_divisor,
                2
            );
        }

        $chargeableWeight = $volumetricWeight !== null
            ? max($actualWeight, $volumetricWeight)
            : $actualWeight;

        $rate = $this->repository->findActiveForWeight($method->id, $chargeableWeight);

        if (! $rate) {
            throw ValidationException::withMessages([
                'weight' => [__('api.shipping_rate_not_found_for_weight')],
            ]);
        }

        $calculatedCost = round($chargeableWeight * (float) $rate->rate_per_kg, 2);
        $shippingCost = max($calculatedCost, (float) $method->minimum_charge);

        return [
            'method' => [
                'id' => $method->id,
                'name' => $method->name,
                'code' => $method->code,
            ],
            'route' => [
                'from' => ShippingMethod::ROUTE_FROM,
                'to' => ShippingMethod::ROUTE_TO,
            ],
            'actual_weight_kg' => $actualWeight,
            'volumetric_weight_kg' => $volumetricWeight,
            'chargeable_weight_kg' => $chargeableWeight,
            'rate' => [
                'id' => $rate->id,
                'min_weight' => (float) $rate->min_weight,
                'max_weight' => (float) $rate->max_weight,
                'rate_per_kg' => (float) $rate->rate_per_kg,
            ],
            'minimum_charge' => (float) $method->minimum_charge,
            'calculated_cost' => $calculatedCost,
            'shipping_cost' => $shippingCost,
            'currency' => 'TJS',
        ];
    }

    protected function validatePayload(array $data, ?int $ignoreId = null): array
    {
        return validator($data, [
            'shipping_method_id' => ['required', 'integer', 'exists:shipping_methods,id'],
            'min_weight' => ['required', 'numeric', 'min:0'],
            'max_weight' => ['required', 'numeric', 'gt:min_weight'],
            'rate_per_kg' => ['required', 'numeric', 'gt:0'],
            'is_active' => ['sometimes', 'boolean'],
        ])->validate();
    }
}
