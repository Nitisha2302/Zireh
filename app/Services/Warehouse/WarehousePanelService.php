<?php

namespace App\Services\Warehouse;

use App\Models\Admin;
use App\Models\CustomerOrder;
use App\Models\OrderStatus;
use App\Services\Order\OrderStatusService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class WarehousePanelService
{
    public function __construct(
        private readonly OrderStatusService $orderStatusService,
    ) {}

    public function paginateChinaOrders(
        ?string $search = null,
        ?string $statusFilter = null,
        ?string $platformFilter = null,
        int $perPage = 15,
    ): LengthAwarePaginator {
        return $this->chinaOrdersQuery($search, $statusFilter, $platformFilter)
            ->paginate($perPage);
    }

    public function paginateTajikistanOrders(
        Admin $admin,
        ?string $search = null,
        ?string $statusFilter = null,
        ?string $platformFilter = null,
        int $perPage = 15,
    ): LengthAwarePaginator {
        return $this->tajikistanOrdersQuery($admin, $search, $statusFilter, $platformFilter)
            ->paginate($perPage);
    }

    public function chinaOrdersQuery(
        ?string $search = null,
        ?string $statusFilter = null,
        ?string $platformFilter = null,
    ): Builder {
        return $this->baseOrderQuery($search, $statusFilter, $platformFilter)
            ->where('status', '!=', OrderStatus::CODE_CANCELLED);
    }

    public function tajikistanOrdersQuery(
        Admin $admin,
        ?string $search = null,
        ?string $statusFilter = null,
        ?string $platformFilter = null,
    ): Builder {
        $query = $this->baseOrderQuery($search, $statusFilter, $platformFilter)
            ->whereNotNull('warehouse_id');

        if ($admin->isTajikistanWarehouseStaff()) {
            $query->where('warehouse_id', $admin->warehouse_id);
        }

        return $query;
    }

    public function ensureChinaOrderAccessible(Admin $admin, CustomerOrder $order): void
    {
        if (! $admin->canAccessChinaWarehousePanel()) {
            abort(403);
        }

        if ($order->status === OrderStatus::CODE_CANCELLED) {
            abort(404);
        }
    }

    public function ensureTajikistanOrderAccessible(Admin $admin, CustomerOrder $order): void
    {
        if (! $admin->canAccessTajikistanWarehousePanel()) {
            abort(403);
        }

        if ($order->warehouse_id === null) {
            throw ValidationException::withMessages([
                'order' => [__('admin.warehouse_order_not_available')],
            ]);
        }

        if ($admin->isTajikistanWarehouseStaff() && (int) $order->warehouse_id !== (int) $admin->warehouse_id) {
            abort(403);
        }
    }

    public function updateOrderStatus(CustomerOrder $order, string $statusCode): CustomerOrder
    {
        return $this->orderStatusService->updateOrderStatus($order, $statusCode);
    }

    public function updateParcelTracking(CustomerOrder $order, ?string $trackingId): CustomerOrder
    {
        $trackingId = $trackingId !== null ? trim($trackingId) : null;

        if ($trackingId === '') {
            $trackingId = null;
        }

        if ($trackingId !== null && strlen($trackingId) > 120) {
            throw ValidationException::withMessages([
                'parcelTrackingId' => [__('admin.parcel_tracking_too_long')],
            ]);
        }

        $order->update(['parcel_tracking_id' => $trackingId]);

        return $order->fresh();
    }

    protected function baseOrderQuery(
        ?string $search,
        ?string $statusFilter,
        ?string $platformFilter,
    ): Builder {
        return CustomerOrder::query()
            ->with(['user', 'items', 'orderStatus', 'warehouse'])
            ->when($search, function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('elim_order_id', 'like', '%'.$search.'%')
                        ->orWhere('parcel_tracking_id', 'like', '%'.$search.'%')
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('phone', 'like', '%'.$search.'%'));
                });
            })
            ->when($statusFilter, fn (Builder $query) => $query->where('status', $statusFilter))
            ->when($platformFilter, fn (Builder $query) => $query->where('platform', $platformFilter))
            ->latest();
    }
}
