<?php

namespace App\Services\Order;

use App\Models\CustomerOrder;
use App\Models\OrderStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class OrderStatusService
{
    private const CACHE_KEY = 'order_statuses.active';

    public function listActive(): Collection
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): Collection {
            return OrderStatus::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        });
    }

    public function optionsForSelect(): array
    {
        return $this->listActive()
            ->mapWithKeys(fn (OrderStatus $status): array => [$status->code => $status->name])
            ->all();
    }

    public function findByCode(string $code): ?OrderStatus
    {
        return OrderStatus::query()->where('code', $code)->first();
    }

    public function paginate(
        string $view = 'active',
        ?string $search = null,
        ?string $statusFilter = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $view === 'trash'
            ? OrderStatus::onlyTrashed()
            : OrderStatus::query();

        return $query
            ->when($search, function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when($view === 'active' && $statusFilter === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when($view === 'active' && $statusFilter === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(array $data): OrderStatus
    {
        $validated = $this->validatePayload($data);

        $status = OrderStatus::query()->create([
            ...$validated,
            'is_system' => false,
        ]);

        $this->clearCache();

        return $status;
    }

    public function update(OrderStatus $status, array $data): OrderStatus
    {
        $validated = $this->validatePayload($data, $status);

        if ($status->isSystem()) {
            unset($validated['code']);
        }

        $status->update($validated);
        $this->clearCache();

        return $status->fresh();
    }

    public function toggleActive(OrderStatus $status): OrderStatus
    {
        $status->update(['is_active' => ! $status->is_active]);
        $this->clearCache();

        return $status->fresh();
    }

    public function softDelete(OrderStatus $status): void
    {
        if ($status->isSystem()) {
            throw ValidationException::withMessages([
                'status' => [__('admin.order_status_system_delete_forbidden')],
            ]);
        }

        $status->delete();
        $this->clearCache();
    }

    public function restore(int $id): OrderStatus
    {
        $status = OrderStatus::onlyTrashed()->findOrFail($id);
        $status->restore();
        $this->clearCache();

        return $status->fresh();
    }

    public function forceDelete(int $id): void
    {
        $status = OrderStatus::onlyTrashed()->findOrFail($id);

        if ($status->isSystem()) {
            throw ValidationException::withMessages([
                'status' => [__('admin.order_status_system_delete_forbidden')],
            ]);
        }

        if ($this->ordersCount($status) > 0) {
            throw ValidationException::withMessages([
                'status' => [__('admin.order_status_in_use_force_delete')],
            ]);
        }

        $status->forceDelete();
        $this->clearCache();
    }

    public function updateOrderStatus(CustomerOrder $order, string $statusCode): CustomerOrder
    {
        $status = OrderStatus::query()
            ->where('code', $statusCode)
            ->where('is_active', true)
            ->first();

        if (! $status) {
            throw ValidationException::withMessages([
                'status' => [__('admin.order_status_not_available')],
            ]);
        }

        $order->update(['status' => $status->code]);

        return $order->fresh();
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected function ordersCount(OrderStatus $status): int
    {
        return CustomerOrder::query()->where('status', $status->code)->count();
    }

    protected function validatePayload(array $data, ?OrderStatus $status = null): array
    {
        $codeRule = ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/'];

        if ($status) {
            $codeRule[] = 'unique:order_statuses,code,'.$status->id;
        } else {
            $codeRule[] = 'unique:order_statuses,code';
        }

        return validator($data, [
            'name' => ['required', 'string', 'max:255'],
            'code' => $codeRule,
            'color' => ['required', 'string', 'in:'.implode(',', OrderStatus::COLOR_OPTIONS)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ])->validate();
    }
}
