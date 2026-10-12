<?php

namespace App\Repositories\Shipping;

use App\Models\ShippingRate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ShippingRateRepository
{
    public function query(): Builder
    {
        return ShippingRate::query()->with(['shippingMethod', 'warehouse']);
    }

    public function findOrFail(int $id): ShippingRate
    {
        return ShippingRate::query()->with(['shippingMethod', 'warehouse'])->findOrFail($id);
    }

    public function create(array $data): ShippingRate
    {
        return ShippingRate::query()->create($data);
    }

    public function update(ShippingRate $rate, array $data): ShippingRate
    {
        $rate->update($data);

        return $rate->fresh(['shippingMethod', 'warehouse']);
    }

    public function delete(ShippingRate $rate): void
    {
        $rate->delete();
    }

    public function paginate(
        ?string $search,
        ?int $methodFilter,
        ?int $warehouseFilter,
        ?string $statusFilter,
        ?string $modeFilter,
        ?float $weightFilter,
        string $sortField,
        string $sortDirection,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->query()
            ->when($methodFilter, fn (Builder $query) => $query->where('shipping_method_id', $methodFilter))
            ->when($warehouseFilter, fn (Builder $query) => $query->where('warehouse_id', $warehouseFilter))
            ->when($statusFilter === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when($statusFilter === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->when(
                in_array($modeFilter, [ShippingRate::MODE_WEIGHT, ShippingRate::MODE_CM], true),
                fn (Builder $query) => $query->where('mode', $modeFilter),
            )
            ->when($weightFilter !== null, function (Builder $query) use ($weightFilter): void {
                $query->where('min_weight', '<=', $weightFilter)
                    ->where('max_weight', '>=', $weightFilter);
            })
            ->when($search, function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->whereHas('shippingMethod', function (Builder $query) use ($search): void {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    })->orWhereHas('warehouse', function (Builder $query) use ($search): void {
                        $query->where('warehouse_name', 'like', "%{$search}%")
                            ->orWhere('warehouse_code', 'like', "%{$search}%");
                    })->orWhere('rate_per_kg', 'like', "%{$search}%");
                });
            })
            ->orderBy($sortField, $sortDirection)
            ->paginate($perPage);
    }

    public function countStats(): array
    {
        return [
            'total' => ShippingRate::query()->count(),
            'active' => ShippingRate::query()->where('is_active', true)->count(),
            'inactive' => ShippingRate::query()->where('is_active', false)->count(),
        ];
    }

    /**
     * @return Collection<int, ShippingRate>
     */
    public function forMethod(int $methodId, ?int $ignoreId = null, ?int $warehouseId = null, ?string $mode = null): Collection
    {
        return ShippingRate::query()
            ->where('shipping_method_id', $methodId)
            ->when(
                $warehouseId === null,
                fn (Builder $query) => $query->whereNull('warehouse_id'),
                fn (Builder $query) => $query->where('warehouse_id', $warehouseId),
            )
            ->when(
                in_array($mode, [ShippingRate::MODE_WEIGHT, ShippingRate::MODE_CM], true),
                fn (Builder $query) => $query->where('mode', $mode),
            )
            ->when($ignoreId, fn (Builder $query) => $query->where('id', '!=', $ignoreId))
            ->get();
    }

    public function findActiveForWeight(int $methodId, float $weight, ?int $warehouseId = null, ?string $mode = null): ?ShippingRate
    {
        $weight = max(0, $weight);
        $preferred = $mode === ShippingRate::MODE_CM ? ShippingRate::MODE_CM : ShippingRate::MODE_WEIGHT;
        $fallback = $preferred === ShippingRate::MODE_CM ? ShippingRate::MODE_WEIGHT : ShippingRate::MODE_CM;

        $attempts = [
            [$warehouseId, $preferred],
            [$warehouseId, $fallback],
        ];

        if ($warehouseId !== null) {
            $attempts[] = [null, $preferred];
            $attempts[] = [null, $fallback];
        }

        foreach ($attempts as [$tryWarehouseId, $tryMode]) {
            $rates = $this->activeRatesForMethodAndWarehouse($methodId, $tryWarehouseId, $tryMode);

            if ($rates->isEmpty()) {
                continue;
            }

            return $this->pickRateForWeight($rates, $weight);
        }

        return null;
    }

    /**
     * @return Collection<int, ShippingRate>
     */
    protected function activeRatesForMethodAndWarehouse(int $methodId, ?int $warehouseId, string $mode): Collection
    {
        return ShippingRate::query()
            ->where('shipping_method_id', $methodId)
            ->where('is_active', true)
            ->where('mode', $mode)
            ->when(
                $warehouseId === null,
                fn (Builder $query) => $query->whereNull('warehouse_id'),
                fn (Builder $query) => $query->where('warehouse_id', $warehouseId),
            )
            ->orderBy('min_weight')
            ->get();
    }

    /**
     * @param  Collection<int, ShippingRate>  $rates
     */
    protected function pickRateForWeight(Collection $rates, float $weight): ShippingRate
    {
        $exact = $rates
            ->filter(fn (ShippingRate $rate) => (float) $rate->min_weight <= $weight
                && (float) $rate->max_weight >= $weight)
            ->sortByDesc(fn (ShippingRate $rate) => (float) $rate->min_weight)
            ->first();

        if ($exact) {
            return $exact;
        }

        if ($weight < (float) $rates->first()->min_weight) {
            return $rates->first();
        }

        if ($weight > (float) $rates->last()->max_weight) {
            return $rates->last();
        }

        return $rates
            ->filter(fn (ShippingRate $rate) => (float) $rate->max_weight < $weight)
            ->sortByDesc(fn (ShippingRate $rate) => (float) $rate->max_weight)
            ->first() ?? $rates->last();
    }
}
