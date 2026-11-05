<?php

namespace App\Services\Warehouse;

use App\Models\Admin;
use App\Models\CustomerOrder;
use App\Models\OrderStatus;
use App\Models\OrderStatusHistory;
use App\Models\Shipment;
use App\Services\Order\OrderAuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShipmentService
{
    public function __construct(
        private readonly OrderAuditLogger $auditLogger,
    ) {}

    public function unassignedOrdersQuery(?string $search = null): Builder
    {
        return CustomerOrder::query()
            ->with(['user', 'items', 'orderStatus', 'warehouse'])
            ->whereNull('shipment_id')
            ->where('status', '!=', OrderStatus::CODE_CANCELLED)
            ->when($search, function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('id', $search)
                        ->orWhere('elim_order_id', 'like', '%'.$search.'%')
                        ->orWhere('parcel_tracking_id', 'like', '%'.$search.'%')
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('phone', 'like', '%'.$search.'%'));
                });
            })
            ->latest('id');
    }

    public function listUnassignedOrders(?string $search = null): Collection
    {
        return $this->unassignedOrdersQuery($search)->get();
    }

    public function paginateChinaShipments(
        ?string $search = null,
        ?string $statusFilter = null,
        int $perPage = 15,
    ): LengthAwarePaginator {
        return $this->baseShipmentQuery($search, $statusFilter)
            ->paginate($perPage);
    }

    public function paginateTajikistanShipments(
        Admin $admin,
        ?string $search = null,
        ?string $statusFilter = null,
        int $perPage = 15,
    ): LengthAwarePaginator {
        return $this->tajikistanShipmentsQuery($admin, $search, $statusFilter)
            ->paginate($perPage);
    }

    public function tajikistanShipmentsQuery(
        Admin $admin,
        ?string $search = null,
        ?string $statusFilter = null,
    ): Builder {
        $query = $this->baseShipmentQuery($search, $statusFilter)
            ->whereHas('orders', function (Builder $orderQuery) use ($admin): void {
                $orderQuery->whereNotNull('warehouse_id');

                if ($admin->isTajikistanWarehouseStaff()) {
                    $orderQuery->where('warehouse_id', $admin->warehouse_id);
                }
            });

        if ($admin->isTajikistanWarehouseStaff()) {
            $query->withCount([
                'orders' => fn (Builder $orderQuery) => $orderQuery->where('warehouse_id', $admin->warehouse_id),
            ]);
        }

        return $query;
    }

    public function ensureChinaShipmentAccessible(Admin $admin, Shipment $shipment): void
    {
        if (! $admin->canAccessChinaWarehousePanel()) {
            abort(403);
        }
    }

    public function ensureTajikistanShipmentAccessible(Admin $admin, Shipment $shipment): void
    {
        if (! $admin->canAccessTajikistanWarehousePanel()) {
            abort(403);
        }

        if ($admin->isTajikistanWarehouseStaff()) {
            $hasWarehouseOrder = $shipment->orders()
                ->where('warehouse_id', $admin->warehouse_id)
                ->exists();

            if (! $hasWarehouseOrder) {
                abort(403);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createShipment(Admin $admin, array $data): Shipment
    {
        $shipmentCode = trim((string) ($data['shipment_code'] ?? ''));
        $statusCode = trim((string) ($data['status'] ?? OrderStatus::CODE_RECEIVED_AT_CHINA_WAREHOUSE));
        $notes = isset($data['notes']) && trim((string) $data['notes']) !== '' ? trim((string) $data['notes']) : null;
        $orderIds = array_values(array_unique(array_map('intval', array_filter((array) ($data['order_ids'] ?? [])))));

        validator([
            'shipment_code' => $shipmentCode,
            'status' => $statusCode,
            'notes' => $notes,
            'order_ids' => $orderIds,
        ], [
            'shipment_code' => ['required', 'string', 'max:120', 'unique:shipments,shipment_code'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'exists:customer_orders,id'],
        ], [
            'shipment_code.required' => __('admin.shipment_code_required'),
            'shipment_code.unique' => __('admin.shipment_code_already_exists'),
            'order_ids.required' => __('admin.shipment_orders_required'),
            'order_ids.min' => __('admin.shipment_orders_required'),
        ])->validate();

        $status = $this->resolveValidStatus($statusCode);

        return DB::transaction(function () use ($admin, $shipmentCode, $status, $notes, $orderIds): Shipment {
            $orders = CustomerOrder::query()
                ->whereIn('id', $orderIds)
                ->lockForUpdate()
                ->get();

            if ($orders->count() !== count($orderIds)) {
                throw ValidationException::withMessages([
                    'order_ids' => [__('admin.shipment_orders_invalid')],
                ]);
            }

            foreach ($orders as $order) {
                if ($order->status === OrderStatus::CODE_CANCELLED) {
                    throw ValidationException::withMessages([
                        'order_ids' => [__('admin.shipment_cancelled_order_not_allowed', ['id' => $order->id])],
                    ]);
                }

                if ($order->shipment_id !== null) {
                    throw ValidationException::withMessages([
                        'order_ids' => [__('admin.shipment_order_already_assigned', ['id' => $order->id])],
                    ]);
                }
            }

            $isTajikistanStatus = in_array($status->code, Shipment::TAJIKISTAN_RECEIVED_STATUSES, true);

            $shipment = Shipment::query()->create([
                'shipment_code' => $shipmentCode,
                'status' => $status->code,
                'notes' => $notes,
                'created_by_admin_id' => $admin->id,
                'received_in_tajikistan_at' => $isTajikistanStatus ? now() : null,
                'received_by_admin_id' => $isTajikistanStatus ? $admin->id : null,
            ]);

            $this->auditLogger->withContext(
                OrderStatusHistory::SOURCE_MANUAL,
                $admin,
                sprintf('Assigned to shipment %s', $shipmentCode),
                ['shipment_id' => $shipment->id, 'shipment_code' => $shipmentCode],
                function () use ($orders, $shipment, $status): void {
                    foreach ($orders as $order) {
                        $order->update([
                            'shipment_id' => $shipment->id,
                            'status' => $status->code,
                        ]);
                    }
                }
            );

            return $shipment->fresh(['orderStatus', 'orders.user', 'orders.orderStatus', 'orders.warehouse']);
        });
    }

    /**
     * @param  list<int|string>  $orderIds
     */
    public function attachOrders(Shipment $shipment, array $orderIds): Shipment
    {
        if ($shipment->isStatusUpdateLocked()) {
            throw ValidationException::withMessages([
                'shipment' => [__('admin.shipment_status_locked_in_tajikistan')],
            ]);
        }

        $orderIds = array_values(array_unique(array_map('intval', array_filter($orderIds))));

        if ($orderIds === []) {
            throw ValidationException::withMessages([
                'order_ids' => [__('admin.shipment_orders_required')],
            ]);
        }

        return DB::transaction(function () use ($shipment, $orderIds): Shipment {
            $orders = CustomerOrder::query()
                ->whereIn('id', $orderIds)
                ->lockForUpdate()
                ->get();

            if ($orders->count() !== count($orderIds)) {
                throw ValidationException::withMessages([
                    'order_ids' => [__('admin.shipment_orders_invalid')],
                ]);
            }

            foreach ($orders as $order) {
                if ($order->status === OrderStatus::CODE_CANCELLED) {
                    throw ValidationException::withMessages([
                        'order_ids' => [__('admin.shipment_cancelled_order_not_allowed', ['id' => $order->id])],
                    ]);
                }

                if ($order->shipment_id !== null && (int) $order->shipment_id !== (int) $shipment->id) {
                    throw ValidationException::withMessages([
                        'order_ids' => [__('admin.shipment_order_already_assigned', ['id' => $order->id])],
                    ]);
                }
            }

            $this->auditLogger->withContext(
                OrderStatusHistory::SOURCE_MANUAL,
                null,
                sprintf('Attached to shipment %s', $shipment->shipment_code),
                ['shipment_id' => $shipment->id, 'shipment_code' => $shipment->shipment_code],
                function () use ($orders, $shipment): void {
                    foreach ($orders as $order) {
                        $order->update([
                            'shipment_id' => $shipment->id,
                            'status' => $shipment->status,
                        ]);
                    }
                }
            );

            return $shipment->fresh(['orderStatus', 'orders.user', 'orders.orderStatus', 'orders.warehouse']);
        });
    }

    public function detachOrder(Shipment $shipment, CustomerOrder $order): Shipment
    {
        if ($shipment->isStatusUpdateLocked()) {
            throw ValidationException::withMessages([
                'shipment' => [__('admin.shipment_status_locked_in_tajikistan')],
            ]);
        }

        if ((int) $order->shipment_id !== (int) $shipment->id) {
            throw ValidationException::withMessages([
                'order' => [__('admin.shipment_orders_invalid')],
            ]);
        }

        if ($shipment->orders()->count() <= 1) {
            throw ValidationException::withMessages([
                'order' => [__('admin.shipment_must_have_at_least_one_order')],
            ]);
        }

        $order->update(['shipment_id' => null]);

        return $shipment->fresh(['orderStatus', 'orders.user', 'orders.orderStatus', 'orders.warehouse']);
    }

    public function updateShipmentStatus(Shipment $shipment, string $statusCode, ?Admin $admin = null): Shipment
    {
        if ($shipment->isStatusUpdateLocked()) {
            throw ValidationException::withMessages([
                'statusCode' => [__('admin.shipment_status_locked_in_tajikistan')],
            ]);
        }

        $status = $this->resolveValidStatus($statusCode);

        return DB::transaction(function () use ($shipment, $status, $admin): Shipment {
            $updates = [
                'status' => $status->code,
            ];

            if (in_array($status->code, Shipment::TAJIKISTAN_RECEIVED_STATUSES, true)) {
                $updates['received_in_tajikistan_at'] = $shipment->received_in_tajikistan_at ?? now();
                $updates['received_by_admin_id'] = $shipment->received_by_admin_id ?? $admin?->id;
            }

            $shipment->update($updates);

            $orders = $shipment->orders()
                ->where('status', '!=', OrderStatus::CODE_CANCELLED)
                ->get();

            $this->auditLogger->withContext(
                OrderStatusHistory::SOURCE_MANUAL,
                $admin,
                sprintf('Shipment %s status updated', $shipment->shipment_code),
                ['shipment_id' => $shipment->id, 'shipment_code' => $shipment->shipment_code],
                function () use ($orders, $status): void {
                    foreach ($orders as $order) {
                        $order->update(['status' => $status->code]);
                    }
                }
            );

            return $shipment->fresh(['orderStatus', 'orders.user', 'orders.orderStatus', 'orders.warehouse']);
        });
    }

    public function markReceivedInTajikistan(
        Shipment $shipment,
        Admin $admin,
        ?string $statusCode = null,
    ): Shipment {
        if ($shipment->isStatusUpdateLocked()) {
            throw ValidationException::withMessages([
                'shipment' => [__('admin.shipment_already_received_in_tajikistan')],
            ]);
        }

        $targetStatusCode = $statusCode ?: OrderStatus::CODE_ARRIVED_IN_TAJIKISTAN;
        $status = $this->resolveValidStatus($targetStatusCode);

        return DB::transaction(function () use ($shipment, $admin, $status): Shipment {
            $shipment->update([
                'status' => $status->code,
                'received_in_tajikistan_at' => now(),
                'received_by_admin_id' => $admin->id,
            ]);

            $orders = $shipment->orders()
                ->where('status', '!=', OrderStatus::CODE_CANCELLED)
                ->get();

            $this->auditLogger->withContext(
                OrderStatusHistory::SOURCE_MANUAL,
                $admin,
                sprintf('Shipment %s received at Tajikistan warehouse', $shipment->shipment_code),
                ['shipment_id' => $shipment->id, 'shipment_code' => $shipment->shipment_code],
                function () use ($orders, $status): void {
                    foreach ($orders as $order) {
                        $order->update(['status' => $status->code]);
                    }
                }
            );

            return $shipment->fresh(['orderStatus', 'orders.user', 'orders.orderStatus', 'orders.warehouse']);
        });
    }

    protected function resolveValidStatus(string $statusCode): OrderStatus
    {
        if ($statusCode === OrderStatus::CODE_CANCELLED) {
            throw ValidationException::withMessages([
                'statusCode' => [__('admin.order_status_cancel_via_button_only')],
            ]);
        }

        $status = OrderStatus::query()
            ->where('code', $statusCode)
            ->where('is_active', true)
            ->first();

        if (! $status) {
            throw ValidationException::withMessages([
                'statusCode' => [__('admin.order_status_not_available')],
            ]);
        }

        return $status;
    }

    protected function baseShipmentQuery(?string $search = null, ?string $statusFilter = null): Builder
    {
        return Shipment::query()
            ->with(['orderStatus', 'createdByAdmin', 'receivedByAdmin'])
            ->withCount('orders')
            ->when($search, function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('shipment_code', 'like', '%'.$search.'%')
                        ->orWhereHas('orders', function (Builder $orderQuery) use ($search): void {
                            $orderQuery->where('id', $search)
                                ->orWhere('elim_order_id', 'like', '%'.$search.'%')
                                ->orWhere('parcel_tracking_id', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when($statusFilter, fn (Builder $query) => $query->where('status', $statusFilter))
            ->latest('id');
    }
}
