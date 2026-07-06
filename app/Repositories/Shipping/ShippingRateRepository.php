<?php

namespace App\Repositories\Shipping;

use App\Models\ShippingRate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ShippingRateRepository
{
    public function query(): Builder
    {
        return ShippingRate::query()->with('shippingMethod');
    }

    public function findOrFail(int $id): ShippingRate
    {
        return ShippingRate::query()->with('shippingMethod')->findOrFail($id);
    }

    public function create(array $data): ShippingRate
    {
        return ShippingRate::query()->create($data);
    }

    public function update(ShippingRate $rate, array $data): ShippingRate
    {
        $rate->update($data);

        return $rate->fresh(['shippingMethod']);
    }

    public function delete(ShippingRate $rate): void
    {
        $rate->delete();
    }

    public function paginate(
        ?string $search,
        ?int $methodFilter,
        ?string $statusFilter,
        ?float $weightFilter,
        string $sortField,
        string $sortDirection,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->query()
            ->when($methodFilter, fn (Builder $query) => $query->where('shipping_method_id', $methodFilter))
            ->when($statusFilter === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when($statusFilter === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->when($weightFilter !== null, function (Builder $query) use ($weightFilter): void {
                $query->where('min_weight', '<=', $weightFilter)
                    ->where('max_weight', '>=', $weightFilter);
            })
            ->when($search, function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->whereHas('shippingMethod', function (Builder $query) use ($search): void {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
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
     * @return \Illuminate\Support\Collection<int, ShippingRate>
     */
    public function forMethod(int $methodId, ?int $ignoreId = null): \Illuminate\Support\Collection
    {
        return ShippingRate::query()
            ->where('shipping_method_id', $methodId)
            ->when($ignoreId, fn (Builder $query) => $query->where('id', '!=', $ignoreId))
            ->get();
    }

    public function findActiveForWeight(int $methodId, float $weight): ?ShippingRate
    {
        return ShippingRate::query()
            ->where('shipping_method_id', $methodId)
            ->where('is_active', true)
            ->where('min_weight', '<=', $weight)
            ->where('max_weight', '>=', $weight)
            ->orderByDesc('min_weight')
            ->first();
    }
}
