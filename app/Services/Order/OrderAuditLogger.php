<?php

namespace App\Services\Order;

use App\Models\Admin;
use App\Models\CustomerOrder;
use App\Models\OrderStatusHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class OrderAuditLogger
{
    /** @var list<array{source: string, admin_id: ?int, notes: ?string, metadata: array<string, mixed>}> */
    protected static array $contextStack = [];

    /**
     * Execute a callback within a specific audit context (e.g., elim_api, barcode_scan, manual).
     *
     * @template T
     *
     * @param  array<string, mixed>  $metadata
     * @param  callable(): T  $callback
     * @return T
     */
    public function withContext(
        string $source,
        ?Admin $admin = null,
        ?string $notes = null,
        array $metadata = [],
        ?callable $callback = null,
    ): mixed {
        self::$contextStack[] = [
            'source' => $source,
            'admin_id' => $admin?->id ?? $this->resolveAuthenticatedAdminId(),
            'notes' => $notes,
            'metadata' => $metadata,
        ];

        try {
            return $callback ? $callback() : null;
        } finally {
            array_pop(self::$contextStack);
        }
    }

    /**
     * Record a status transition for a customer order.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function recordTransition(
        CustomerOrder $order,
        ?string $fromStatus,
        string $toStatus,
        ?string $source = null,
        ?Admin $admin = null,
        ?string $notes = null,
        array $metadata = [],
    ): ?OrderStatusHistory {
        if ($fromStatus !== null && $fromStatus === $toStatus) {
            return null;
        }

        if (! Schema::hasTable('order_status_histories')) {
            return null;
        }

        $context = $this->currentContext();

        $resolvedSource = $source
            ?? $context['source']
            ?? OrderStatusHistory::SOURCE_MANUAL;

        if (! in_array($resolvedSource, OrderStatusHistory::SOURCES, true)) {
            $resolvedSource = OrderStatusHistory::SOURCE_MANUAL;
        }

        $resolvedAdminId = $admin?->id
            ?? $context['admin_id']
            ?? ($resolvedSource === OrderStatusHistory::SOURCE_ELIM_API ? null : $this->resolveAuthenticatedAdminId());

        $resolvedNotes = $notes ?? $context['notes'] ?? null;
        $resolvedMetadata = array_filter(array_merge($context['metadata'] ?? [], $metadata), fn ($val) => $val !== null);

        return OrderStatusHistory::query()->create([
            'customer_order_id' => $order->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'source' => $resolvedSource,
            'admin_id' => $resolvedAdminId,
            'notes' => $resolvedNotes,
            'metadata' => $resolvedMetadata !== [] ? $resolvedMetadata : null,
        ]);
    }

    /**
     * @return array{source: string, admin_id: ?int, notes: ?string, metadata: array<string, mixed>}|null
     */
    public function currentContext(): ?array
    {
        if (self::$contextStack === []) {
            return null;
        }

        return self::$contextStack[count(self::$contextStack) - 1];
    }

    protected function resolveAuthenticatedAdminId(): ?int
    {
        try {
            $user = Auth::guard('admin')->user();

            return $user instanceof Admin ? (int) $user->id : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
