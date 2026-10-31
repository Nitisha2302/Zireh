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
            $validated['max_weight'],
            null,
            $validated['warehouse_id'] ?? null,
            $validated['mode'],
        );

        $rate = $this->repository->create($validated);
        $this->shippingMethodService->clearCache();

        return $rate->load(['shippingMethod', 'warehouse']);
    }

    public function update(ShippingRate $rate, array $data): ShippingRate
    {
        $validated = $this->validatePayload($data, $rate->id);
        $this->assertValidRange(
            $validated['shipping_method_id'],
            $validated['min_weight'],
            $validated['max_weight'],
            $rate->id,
            $validated['warehouse_id'] ?? null,
            $validated['mode'],
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
        ?int $ignoreId = null,
        ?int $warehouseId = null,
        string $mode = ShippingRate::MODE_WEIGHT,
    ): void {
        if ($minWeight >= $maxWeight) {
            throw ValidationException::withMessages([
                'maxWeight' => [__('admin.shipping_rate_max_weight_gt_min')],
            ]);
        }

        $existingRates = $this->repository->forMethod($methodId, $ignoreId, $warehouseId, $mode);

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
        ?float $weightKg,
        ?float $lengthCm = null,
        ?float $widthCm = null,
        ?float $heightCm = null,
        ?int $warehouseId = null,
        string $mode = 'auto',
    ): array {
        $method = $this->shippingMethodService->findActiveByCode($methodCode);

        $hasDimensions = $lengthCm !== null && $widthCm !== null && $heightCm !== null;
        $actualWeight = $weightKg !== null ? max(0, $weightKg) : null;
        $volumeM3 = $hasDimensions
            ? round(($lengthCm * $widthCm * $heightCm) / 1_000_000, 6)
            : null;
        $volumetricWeight = $hasDimensions
            ? round(($lengthCm * $widthCm * $heightCm) / $method->volumetric_divisor, 2)
            : null;

        if ($mode === 'both') {
            if ($actualWeight === null || ! $hasDimensions || $volumetricWeight === null) {
                throw ValidationException::withMessages([
                    'weight' => [__('api.shipping_rate_not_found_for_weight')],
                ]);
            }

            $weightCost = $this->costForChargeableWeight($method, $actualWeight, $warehouseId, ShippingRate::MODE_WEIGHT);
            $volumeCost = $this->costForChargeableWeight($method, $volumetricWeight, $warehouseId, ShippingRate::MODE_CM);
            $calculatedCost = round($weightCost['calculated_cost'] + $volumeCost['calculated_cost'], 2);
            $shippingCost = round($weightCost['shipping_cost'] + $volumeCost['shipping_cost'], 2);
            $actualWeightGrams = round($actualWeight * 1000, 2);

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
                'mode' => 'both',
                'package' => [
                    'length_cm' => $lengthCm,
                    'width_cm' => $widthCm,
                    'height_cm' => $heightCm,
                    'weight_kg' => $actualWeight,
                    'weight_g' => $actualWeightGrams,
                    'volume_m3' => $volumeM3,
                    'volumetric_weight_kg' => $volumetricWeight,
                ],
                'actual_weight_kg' => $actualWeight,
                'actual_weight_g' => $actualWeightGrams,
                'volumetric_weight_kg' => $volumetricWeight,
                'chargeable_weight_kg' => round($actualWeight + $volumetricWeight, 2),
                'rate' => $weightCost['rate'],
                'weight_cost_tjs' => $weightCost['shipping_cost'],
                'volume_cost_tjs' => $volumeCost['shipping_cost'],
                'weight_breakdown' => $weightCost,
                'volume_breakdown' => $volumeCost,
                'minimum_charge' => (float) $method->minimum_charge,
                'calculated_cost' => $calculatedCost,
                'shipping_cost' => $shippingCost,
                'currency' => 'TJS',
            ];
        }

        if ($mode === ShippingRate::MODE_WEIGHT) {
            $actualWeight ??= 0;
            $lengthCm = null;
            $widthCm = null;
            $heightCm = null;
            $volumeM3 = null;
            $volumetricWeight = null;
            $chargeableWeight = $actualWeight;
            $ladderMode = ShippingRate::MODE_WEIGHT;
        } elseif ($mode === ShippingRate::MODE_CM) {
            $actualWeight = null;
            $chargeableWeight = $volumetricWeight ?? 0;
            $ladderMode = ShippingRate::MODE_CM;
        } else {
            $actualWeight ??= 0;
            $chargeableWeight = $volumetricWeight !== null
                ? max($actualWeight, $volumetricWeight)
                : $actualWeight;
            $ladderMode = $volumetricWeight !== null && $volumetricWeight > $actualWeight
                ? ShippingRate::MODE_CM
                : ShippingRate::MODE_WEIGHT;
        }

        $rate = $this->repository->findActiveForWeight($method->id, $chargeableWeight, $warehouseId, $ladderMode);

        if (! $rate) {
            throw ValidationException::withMessages([
                'weight' => [__('api.shipping_rate_not_found_for_weight')],
            ]);
        }

        $calculatedCost = round($chargeableWeight * (float) $rate->rate_per_kg, 2);
        $shippingCost = max($calculatedCost, (float) $method->minimum_charge);
        $ratePayload = $rate->toCalculationRatePayload();
        $breakdown = [
            'chargeable_weight_kg' => $chargeableWeight,
            'rate' => $ratePayload,
            'minimum_charge' => (float) $method->minimum_charge,
            'calculated_cost' => $calculatedCost,
            'shipping_cost' => $shippingCost,
        ];
        $isCmCharge = $ladderMode === ShippingRate::MODE_CM;
        $actualWeightGrams = $actualWeight !== null ? round($actualWeight * 1000, 2) : null;

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
            'mode' => $mode === 'cm' ? 'cm' : ($mode === 'weight' ? 'weight' : 'auto'),
            'package' => [
                'length_cm' => $lengthCm,
                'width_cm' => $widthCm,
                'height_cm' => $heightCm,
                'weight_kg' => $actualWeight,
                'weight_g' => $actualWeightGrams,
                'volume_m3' => $volumeM3,
                'volumetric_weight_kg' => $volumetricWeight,
            ],
            'actual_weight_kg' => $actualWeight,
            'actual_weight_g' => $actualWeightGrams,
            'volumetric_weight_kg' => $volumetricWeight,
            'chargeable_weight_kg' => $chargeableWeight,
            'rate' => $ratePayload,
            'weight_cost_tjs' => $isCmCharge ? 0.0 : $shippingCost,
            'volume_cost_tjs' => $isCmCharge ? $shippingCost : 0.0,
            'weight_breakdown' => $isCmCharge ? null : $breakdown,
            'volume_breakdown' => $isCmCharge ? $breakdown : null,
            'minimum_charge' => (float) $method->minimum_charge,
            'calculated_cost' => $calculatedCost,
            'shipping_cost' => $shippingCost,
            'currency' => 'TJS',
        ];
    }

    public function calculatePickupShipping(
        ShippingMethod $method,
        ?float $weightKg,
        ?float $lengthCm = null,
        ?float $widthCm = null,
        ?float $heightCm = null,
        ?int $warehouseId = null,
        string $mode = 'auto',
    ): array {
        $hasDimensions = $lengthCm !== null && $widthCm !== null && $heightCm !== null;
        $actualWeight = $weightKg !== null ? max(0, $weightKg) : null;
        $volumeM3 = $hasDimensions
            ? round(($lengthCm * $widthCm * $heightCm) / 1_000_000, 6)
            : null;
        $volumetricWeight = $hasDimensions
            ? round(($lengthCm * $widthCm * $heightCm) / $method->volumetric_divisor, 2)
            : null;

        if ($mode === 'weight') {
            if ($actualWeight === null) {
                throw ValidationException::withMessages([
                    'weight' => [__('api.shipping_rate_not_found_for_weight')],
                ]);
            }

            $weightCost = $this->costForChargeableWeight($method, $actualWeight, $warehouseId, ShippingRate::MODE_WEIGHT);

            return $this->pickupShippingResult(
                $method,
                lengthCm: null,
                widthCm: null,
                heightCm: null,
                actualWeight: $actualWeight,
                volumeM3: null,
                volumetricWeight: null,
                weightCost: $weightCost,
                volumeCost: $this->emptyPickupCost(),
                appliedMethod: 'weight',
                shippingCost: $weightCost['shipping_cost'],
            );
        }

        if ($mode === 'cm') {
            if (! $hasDimensions || $volumetricWeight === null) {
                throw ValidationException::withMessages([
                    'weight' => [__('api.shipping_rate_not_found_for_weight')],
                ]);
            }

            $volumeCost = $this->costForChargeableWeight($method, $volumetricWeight, $warehouseId, ShippingRate::MODE_CM);

            return $this->pickupShippingResult(
                $method,
                lengthCm: $lengthCm,
                widthCm: $widthCm,
                heightCm: $heightCm,
                actualWeight: null,
                volumeM3: $volumeM3,
                volumetricWeight: $volumetricWeight,
                weightCost: $this->emptyPickupCost(),
                volumeCost: $volumeCost,
                appliedMethod: 'volume',
                shippingCost: $volumeCost['shipping_cost'],
            );
        }

        if ($mode === 'both') {
            if ($actualWeight === null || ! $hasDimensions || $volumetricWeight === null) {
                throw ValidationException::withMessages([
                    'weight' => [__('api.shipping_rate_not_found_for_weight')],
                ]);
            }

            $weightCost = $this->costForChargeableWeight($method, $actualWeight, $warehouseId, ShippingRate::MODE_WEIGHT);
            $volumeCost = $this->costForChargeableWeight($method, $volumetricWeight, $warehouseId, ShippingRate::MODE_CM);

            return $this->pickupShippingResult(
                $method,
                lengthCm: $lengthCm,
                widthCm: $widthCm,
                heightCm: $heightCm,
                actualWeight: $actualWeight,
                volumeM3: $volumeM3,
                volumetricWeight: $volumetricWeight,
                weightCost: $weightCost,
                volumeCost: $volumeCost,
                appliedMethod: 'both',
                shippingCost: round($weightCost['shipping_cost'] + $volumeCost['shipping_cost'], 2),
            );
        }

        $weightCost = $this->costForChargeableWeight($method, $actualWeight ?? 0, $warehouseId, ShippingRate::MODE_WEIGHT);
        $volumeCost = $this->costForChargeableWeight($method, $volumetricWeight ?? 0, $warehouseId, ShippingRate::MODE_CM);
        $appliedMethod = $volumeCost['shipping_cost'] > $weightCost['shipping_cost']
            ? 'volume'
            : 'weight';

        return $this->pickupShippingResult(
            $method,
            lengthCm: $lengthCm,
            widthCm: $widthCm,
            heightCm: $heightCm,
            actualWeight: $actualWeight ?? 0,
            volumeM3: $volumeM3 ?? 0,
            volumetricWeight: $volumetricWeight,
            weightCost: $weightCost,
            volumeCost: $volumeCost,
            appliedMethod: $appliedMethod,
            shippingCost: max($weightCost['shipping_cost'], $volumeCost['shipping_cost']),
        );
    }

    /**
     * @param  array<string, mixed>  $weightCost
     * @param  array<string, mixed>  $volumeCost
     * @return array<string, mixed>
     */
    protected function pickupShippingResult(
        ShippingMethod $method,
        ?float $lengthCm,
        ?float $widthCm,
        ?float $heightCm,
        ?float $actualWeight,
        ?float $volumeM3,
        ?float $volumetricWeight,
        array $weightCost,
        array $volumeCost,
        string $appliedMethod,
        float $shippingCost,
    ): array {
        return [
            'method' => [
                'id' => $method->id,
                'name' => $method->name,
                'code' => $method->code,
            ],
            'package' => [
                'length_cm' => $lengthCm,
                'width_cm' => $widthCm,
                'height_cm' => $heightCm,
                'weight_kg' => $actualWeight,
                'volume_m3' => $volumeM3,
                'volumetric_weight_kg' => $volumetricWeight,
            ],
            'weight_cost_tjs' => $weightCost['shipping_cost'],
            'volume_cost_tjs' => $volumeCost['shipping_cost'],
            'applied_method' => $appliedMethod,
            'shipping_cost' => $shippingCost,
            'currency' => 'TJS',
            'weight_breakdown' => $weightCost,
            'volume_breakdown' => $volumeCost,
        ];
    }

    /**
     * @return array{chargeable_weight_kg: float, rate: null, minimum_charge: float, calculated_cost: float, shipping_cost: float}
     */
    protected function emptyPickupCost(): array
    {
        return [
            'chargeable_weight_kg' => 0.0,
            'rate' => null,
            'minimum_charge' => 0.0,
            'calculated_cost' => 0.0,
            'shipping_cost' => 0.0,
        ];
    }

    protected function costForChargeableWeight(
        ShippingMethod $method,
        float $chargeableWeight,
        ?int $warehouseId = null,
        string $mode = ShippingRate::MODE_WEIGHT,
    ): array {
        $rate = $this->repository->findActiveForWeight($method->id, $chargeableWeight, $warehouseId, $mode);

        if (! $rate) {
            throw ValidationException::withMessages([
                'weight' => [__('api.shipping_rate_not_found_for_weight')],
            ]);
        }

        $calculatedCost = round($chargeableWeight * (float) $rate->rate_per_kg, 2);
        $shippingCost = max($calculatedCost, (float) $method->minimum_charge);

        return [
            'chargeable_weight_kg' => $chargeableWeight,
            'rate' => $rate->toCalculationRatePayload(),
            'minimum_charge' => (float) $method->minimum_charge,
            'calculated_cost' => $calculatedCost,
            'shipping_cost' => $shippingCost,
        ];
    }

    protected function validatePayload(array $data, ?int $ignoreId = null): array
    {
        return validator($data, [
            'shipping_method_id' => ['required', 'integer', 'exists:shipping_methods,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'mode' => ['required', 'in:'.ShippingRate::MODE_WEIGHT.','.ShippingRate::MODE_CM],
            'min_weight' => ['required', 'numeric', 'min:0'],
            'max_weight' => ['required', 'numeric', 'gt:min_weight'],
            'rate_per_kg' => ['required', 'numeric', 'gt:0'],
            'is_active' => ['sometimes', 'boolean'],
        ])->validate();
    }
}
