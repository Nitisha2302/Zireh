<?php

namespace App\Repositories\Shipping;

use App\Models\ShippingMethod;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ShippingMethodRepository
{
    public function query(): Builder
    {
        return ShippingMethod::query();
    }

    public function findOrFail(int $id): ShippingMethod
    {
        return ShippingMethod::query()->findOrFail($id);
    }

    public function findByCode(string $code): ?ShippingMethod
    {
        return ShippingMethod::query()->where('code', $code)->first();
    }

    public function create(array $data): ShippingMethod
    {
        return ShippingMethod::query()->create($data);
    }

    public function update(ShippingMethod $method, array $data): ShippingMethod
    {
        $method->update($data);

        return $method->fresh();
    }

    public function delete(ShippingMethod $method): void
    {
        $method->delete();
    }

    public function paginate(
        ?string $search,
        ?string $statusFilter,
        string $sortField,
        string $sortDirection,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->query()
            ->when($search, function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when($statusFilter === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when($statusFilter === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->orderBy($sortField, $sortDirection)
            ->paginate($perPage);
    }

    public function countStats(): array
    {
        return [
            'total' => $this->query()->count(),
            'active' => $this->query()->where('is_active', true)->count(),
            'inactive' => $this->query()->where('is_active', false)->count(),
        ];
    }

    public function listActiveWithRates(): \Illuminate\Support\Collection
    {
        return $this->query()
            ->where('is_active', true)
            ->with(['activeRates'])
            ->orderBy('name')
            ->get();
    }
}
