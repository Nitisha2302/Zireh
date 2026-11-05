<?php

namespace App\Services\Warehouse;

use App\Models\Admin;
use App\Models\CustomerOrder;
use App\Models\OrderException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class OrderExceptionService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function reportException(array $data, ?Admin $admin = null): OrderException
    {
        $type = (string) ($data['type'] ?? OrderException::TYPE_UNIDENTIFIED_SHIPMENT);
        if (! in_array($type, OrderException::TYPES, true)) {
            $type = OrderException::TYPE_UNIDENTIFIED_SHIPMENT;
        }

        $summary = trim((string) ($data['summary'] ?? ''));
        if ($summary === '') {
            $summary = __('admin.exception_type_'.$type);
        }

        $order = null;
        if (! empty($data['customer_order_id'])) {
            $order = CustomerOrder::query()->find((int) $data['customer_order_id']);
        } elseif (! empty($data['order_identifier'])) {
            $order = $this->findOrderByIdentifier((string) $data['order_identifier'], $admin);
        }

        $warehouseId = $data['warehouse_id']
            ?? $order?->warehouse_id
            ?? ($admin?->isTajikistanWarehouseStaff() ? $admin->warehouse_id : null);

        $shipmentId = $data['shipment_id'] ?? $order?->shipment_id;

        return OrderException::query()->create([
            'customer_order_id' => $order?->id,
            'shipment_id' => $shipmentId,
            'warehouse_id' => $warehouseId,
            'type' => $type,
            'status' => OrderException::STATUS_OPEN,
            'origin_panel' => (string) ($data['origin_panel'] ?? $this->resolveOriginPanel($admin)),
            'scanned_code' => isset($data['scanned_code']) && trim((string) $data['scanned_code']) !== ''
                ? trim((string) $data['scanned_code'])
                : null,
            'summary' => $summary,
            'details' => isset($data['details']) && trim((string) $data['details']) !== ''
                ? trim((string) $data['details'])
                : null,
            'error_payload' => is_array($data['error_payload'] ?? null) ? $data['error_payload'] : null,
            'reported_by_admin_id' => $admin?->id,
        ]);
    }

    /**
     * Automatically log an Elim API error on an order into Exception Management without interrupting operations.
     *
     * @param  array<string, mixed>  $payload
     */
    public function reportElimApiError(
        CustomerOrder $order,
        string $operation,
        string $message,
        array $payload = [],
        ?Admin $admin = null,
    ): ?OrderException {
        if (! Schema::hasTable('order_exceptions')) {
            return null;
        }

        return OrderException::query()->create([
            'customer_order_id' => $order->id,
            'shipment_id' => $order->shipment_id,
            'warehouse_id' => $order->warehouse_id,
            'type' => OrderException::TYPE_API_ERROR,
            'status' => OrderException::STATUS_OPEN,
            'origin_panel' => 'elim_api',
            'scanned_code' => $order->elim_order_id ?: $order->parcel_code,
            'summary' => sprintf('Elim API (%s): %s', $operation, mb_strimwidth($message, 0, 180, '...')),
            'details' => $message,
            'error_payload' => array_merge(['operation' => $operation], $payload),
            'reported_by_admin_id' => $admin?->id,
        ]);
    }

    public function updateStatus(
        OrderException $exception,
        string $status,
        ?string $resolutionNotes,
        Admin $admin,
        ?string $linkOrderIdentifier = null,
    ): OrderException {
        if (! in_array($status, OrderException::STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => [__('admin.exception_status_invalid')],
            ]);
        }

        $updates = [
            'status' => $status,
            'resolution_notes' => $resolutionNotes !== null && trim($resolutionNotes) !== ''
                ? trim($resolutionNotes)
                : $exception->resolution_notes,
        ];

        if ($linkOrderIdentifier !== null && trim($linkOrderIdentifier) !== '') {
            $linkedOrder = $this->findOrderByIdentifier(trim($linkOrderIdentifier), $admin);
            if (! $linkedOrder) {
                throw ValidationException::withMessages([
                    'linkOrderIdentifier' => [__('admin.parcel_scan_order_not_found', ['code' => $linkOrderIdentifier])],
                ]);
            }

            $updates['customer_order_id'] = $linkedOrder->id;
            $updates['warehouse_id'] = $linkedOrder->warehouse_id ?? $exception->warehouse_id;
            $updates['shipment_id'] = $linkedOrder->shipment_id ?? $exception->shipment_id;
        }

        if ($status === OrderException::STATUS_RESOLVED) {
            $updates['resolved_by_admin_id'] = $admin->id;
            $updates['resolved_at'] = now();
        }

        $exception->update($updates);

        return $exception->fresh(['order.user', 'shipment', 'warehouse', 'reportedByAdmin', 'resolvedByAdmin']);
    }

    public function paginateForPanel(
        Admin $admin,
        string $panel,
        ?string $search = null,
        ?string $typeFilter = null,
        ?string $statusFilter = null,
        int $perPage = 15,
    ): LengthAwarePaginator {
        $query = OrderException::query()
            ->with(['order.user', 'order.orderStatus', 'shipment', 'warehouse', 'reportedByAdmin', 'resolvedByAdmin']);

        if ($panel === 'tajikistan' && $admin->isTajikistanWarehouseStaff()) {
            $query->where(function (Builder $q) use ($admin): void {
                $q->where('warehouse_id', $admin->warehouse_id)
                    ->orWhere(function (Builder $inner) use ($admin): void {
                        $inner->whereNull('warehouse_id')
                            ->where('origin_panel', 'tajikistan')
                            ->where('reported_by_admin_id', $admin->id);
                    });
            });
        }

        return $query
            ->when($search, function (Builder $q) use ($search): void {
                $q->where(function (Builder $inner) use ($search): void {
                    $inner->where('scanned_code', 'like', '%'.$search.'%')
                        ->orWhere('summary', 'like', '%'.$search.'%')
                        ->orWhere('details', 'like', '%'.$search.'%')
                        ->orWhereHas('order', function (Builder $orderQuery) use ($search): void {
                            $orderQuery->where('parcel_code', 'like', '%'.$search.'%')
                                ->orWhere('elim_order_id', 'like', '%'.$search.'%')
                                ->orWhere('parcel_tracking_id', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when($typeFilter, fn (Builder $q) => $q->where('type', $typeFilter))
            ->when($statusFilter, fn (Builder $q) => $q->where('status', $statusFilter))
            ->latest('id')
            ->paginate($perPage);
    }

    public function findOrderByIdentifier(string $identifier, ?Admin $admin = null): ?CustomerOrder
    {
        $clean = trim($identifier);
        if (str_starts_with($clean, 'cargo-parcel:')) {
            $clean = substr($clean, strlen('cargo-parcel:'));
        }
        $clean = ltrim($clean, '#');

        if ($clean === '') {
            return null;
        }

        $query = CustomerOrder::query()->where(function (Builder $q) use ($clean): void {
            $q->where('parcel_code', $clean)
                ->orWhere('parcel_tracking_id', $clean)
                ->orWhere('elim_order_id', $clean);

            if (ctype_digit($clean)) {
                $q->orWhere('id', (int) $clean);
            }
        });

        if ($admin && $admin->isTajikistanWarehouseStaff()) {
            $query->where('warehouse_id', $admin->warehouse_id);
        }

        return $query->first();
    }

    protected function resolveOriginPanel(?Admin $admin): string
    {
        if (! $admin) {
            return 'system';
        }

        if ($admin->isChinaWarehouseStaff()) {
            return 'china';
        }

        if ($admin->isTajikistanWarehouseStaff()) {
            return 'tajikistan';
        }

        return 'admin';
    }
}
