<?php

namespace App\Services\Shipping;

use App\Models\ShippingMethod;
use App\Repositories\Shipping\ShippingMethodRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class ShippingMethodService
{
    private const CACHE_KEY = 'api:shipping:methods';

    private const CACHE_TTL_SECONDS = 3600;

    public function __construct(
        protected ShippingMethodRepository $repository,
    ) {}

    public function create(array $data): ShippingMethod
    {
        $method = $this->repository->create($this->normalize($data));
        $this->clearCache();

        return $method;
    }

    public function update(ShippingMethod $method, array $data): ShippingMethod
    {
        $method = $this->repository->update($method, $this->normalize($data));
        $this->clearCache();

        return $method;
    }

    public function delete(ShippingMethod $method): void
    {
        $this->repository->delete($method);
        $this->clearCache();
    }

    public function toggleStatus(ShippingMethod $method): ShippingMethod
    {
        $method = $this->repository->update($method, [
            'is_active' => ! $method->is_active,
        ]);
        $this->clearCache();

        return $method;
    }

    public function listForApi(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function (): array {
            return $this->repository->listActiveWithRates()
                ->map(fn (ShippingMethod $method): array => [
                    'id' => $method->id,
                    'name' => $method->name,
                    'code' => $method->code,
                    'volumetric_divisor' => $method->volumetric_divisor,
                    'minimum_charge' => (float) $method->minimum_charge,
                    'route' => [
                        'from' => ShippingMethod::ROUTE_FROM,
                        'to' => ShippingMethod::ROUTE_TO,
                    ],
                    'rates' => $method->activeRates->map(fn ($rate): array => [
                        'id' => $rate->id,
                        'min_weight' => (float) $rate->min_weight,
                        'max_weight' => (float) $rate->max_weight,
                        'rate_per_kg' => (float) $rate->rate_per_kg,
                    ])->values()->all(),
                ])
                ->values()
                ->all();
        });
    }

    public function findActiveByCode(string $code): ShippingMethod
    {
        $method = $this->repository->findByCode($code);

        if (! $method || ! $method->is_active) {
            throw ValidationException::withMessages([
                'method' => [__('api.shipping_method_not_found')],
            ]);
        }

        return $method;
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected function normalize(array $data): array
    {
        return [
            'name' => $data['name'],
            'code' => strtolower($data['code']),
            'volumetric_divisor' => (int) $data['volumetric_divisor'],
            'minimum_charge' => $data['minimum_charge'],
            'is_active' => (bool) ($data['is_active'] ?? true),
        ];
    }
}
