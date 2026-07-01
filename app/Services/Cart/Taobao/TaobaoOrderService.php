<?php

namespace App\Services\Cart\Taobao;

use App\Exceptions\Elim\ElimRequestException;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderItem;
use App\Models\Platform;
use App\Models\User;
use App\Models\UserCartItem;
use App\Services\Elim\ElimApiClient;
use App\Services\PlatformCommissionService;
use App\Support\Elim\ElimWarehouseAddress;
use App\Support\Elim\ProductNormalizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaobaoOrderService
{
    public function __construct(
        protected TaobaoCartService $cartService,
        protected ElimApiClient $elimClient,
        protected ProductNormalizer $normalizer,
        protected PlatformCommissionService $commissionService,
    ) {}

    public function preview(User $user, array $options = []): array
    {
        $items = $this->cartService->cartItems($user);

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => [__('api.cart_empty')],
            ]);
        }

        $payload = $this->buildOrderPayload($items, $options);
        $previewResponse = $this->callPreview($payload);
        $parsed = $this->parsePreviewResponse($previewResponse);

        if (! empty($parsed['unavailable_items'])) {
            throw ValidationException::withMessages([
                'cart' => [__('api.cart_unavailable_items')],
            ]);
        }

        $commission = $this->resolveCommission($parsed['goods_subtotal_cny']);

        return [
            'platform' => UserCartItem::PLATFORM_TAOBAO,
            'items' => $items->values()->all(),
            'elim_preview' => $parsed,
            'commission' => $commission,
            'customer_total' => round(
                $parsed['goods_subtotal_cny'] + $parsed['shipping_fee_cny'] + ($commission['commission_amount'] ?? 0),
                2
            ),
        ];
    }

    public function checkout(User $user, array $options = []): CustomerOrder
    {
        $items = $this->cartService->cartItems($user);

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => [__('api.cart_empty')],
            ]);
        }

        $payload = $this->buildOrderPayload($items, $options);
        $previewResponse = $this->callPreview($payload);
        $parsed = $this->parsePreviewResponse($previewResponse);

        if (! empty($parsed['unavailable_items'])) {
            throw ValidationException::withMessages([
                'unavailable_items' => [__('api.cart_unavailable_items')],
            ]);
        }

        try {
            $createResponse = $this->elimClient->post('/v1/orders', $payload);
        } catch (ElimRequestException $exception) {
            throw ValidationException::withMessages([
                'checkout' => [$exception->getMessage()],
            ]);
        }

        $orderData = $createResponse['data'] ?? $createResponse;
        $status = (string) ($createResponse['status'] ?? $orderData['status'] ?? 'creating');

        if ($status === 'unknown') {
            throw ValidationException::withMessages([
                'checkout' => [__('api.order_create_unknown')],
            ]);
        }

        $commission = $this->resolveCommission($parsed['goods_subtotal_cny']);
        $platform = Platform::query()->where('code', UserCartItem::PLATFORM_TAOBAO)->firstOrFail();
        $receiverAddress = ElimWarehouseAddress::get();

        return DB::transaction(function () use (
            $user,
            $items,
            $platform,
            $orderData,
            $createResponse,
            $previewResponse,
            $parsed,
            $commission,
            $receiverAddress,
            $status,
            $options
        ): CustomerOrder {
            $order = CustomerOrder::query()->create([
                'user_id' => $user->id,
                'platform_id' => $platform->id,
                'platform' => UserCartItem::PLATFORM_TAOBAO,
                'elim_order_id' => (string) ($orderData['id'] ?? null),
                'status' => $status,
                'payment_status' => (string) ($orderData['payment_status'] ?? 'unpaid'),
                'goods_subtotal_cny' => $parsed['goods_subtotal_cny'],
                'shipping_fee_cny' => $parsed['shipping_fee_cny'],
                'elim_service_fee_cny' => $parsed['service_fee_cny'],
                'commission_slab_id' => $commission['slab_id'] ?? null,
                'commission_percentage' => $commission['commission_percentage'] ?? 0,
                'commission_amount' => $commission['commission_amount'] ?? 0,
                'customer_total_cny' => round(
                    $parsed['goods_subtotal_cny'] + $parsed['shipping_fee_cny'] + ($commission['commission_amount'] ?? 0),
                    2
                ),
                'receiver_address' => $receiverAddress,
                'remark' => $options['remark'] ?? null,
                'elim_preview_snapshot' => $previewResponse,
                'elim_create_snapshot' => $createResponse,
            ]);

            foreach ($items as $item) {
                CustomerOrderItem::query()->create([
                    'customer_order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'marketplace_id' => $item->marketplace_id,
                    'sku_id' => $item->sku_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_subtotal' => $item->lineSubtotal(),
                    'product_snapshot' => $item->product_snapshot,
                    'selected_attributes' => $item->selected_attributes,
                ]);
            }

            $this->cartService->clearCartItems($user);

            return $order->load('items');
        });
    }

    public function list(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return CustomerOrder::query()
            ->where('user_id', $user->id)
            ->where('platform', UserCartItem::PLATFORM_TAOBAO)
            ->with('items')
            ->latest()
            ->paginate($perPage);
    }

    public function show(User $user, CustomerOrder $order): CustomerOrder
    {
        $this->ensureOwnership($user, $order);

        return $order->load('items');
    }

    protected function buildOrderPayload($items, array $options): array
    {
        $payload = [
            'platform' => 'taobao',
            'receiver_address' => ElimWarehouseAddress::get(),
            'line_items' => $items->map(fn (UserCartItem $item): array => $this->normalizer->toElimLineItem(
                (string) ($item->marketplace_id ?: $item->product_id),
                $item->product_id,
                $item->sku_id,
                $item->quantity,
                (float) $item->unit_price
            ))->values()->all(),
        ];

        if (! empty($options['remark'])) {
            $payload['remark'] = $options['remark'];
        }

        if (! empty($options['promotion_id'])) {
            $payload['promotion_id'] = $options['promotion_id'];
        }

        return $payload;
    }

    protected function callPreview(array $payload): array
    {
        try {
            return $this->elimClient->post('/v1/orders/preview', $payload);
        } catch (ElimRequestException $exception) {
            throw ValidationException::withMessages([
                'preview' => [$exception->getMessage()],
            ]);
        }
    }

    protected function parsePreviewResponse(array $response): array
    {
        $data = $response['data'] ?? $response;

        return [
            'goods_subtotal_cny' => (float) ($data['goods_amount_cny'] ?? $data['goods_amount'] ?? $data['goods_subtotal'] ?? 0),
            'shipping_fee_cny' => (float) ($data['shipping_fee_cny'] ?? $data['shipping_fee'] ?? 0),
            'service_fee_cny' => isset($data['service_fee_cny']) ? (float) $data['service_fee_cny'] : null,
            'unavailable_items' => $data['unavailable_items'] ?? [],
            'raw' => $response,
        ];
    }

    protected function resolveCommission(float $subtotal): array
    {
        if ($subtotal <= 0) {
            return [
                'slab_id' => null,
                'commission_percentage' => 0.0,
                'commission_amount' => 0.0,
            ];
        }

        $platform = Platform::query()->where('code', UserCartItem::PLATFORM_TAOBAO)->firstOrFail();

        return $this->commissionService->calculate($platform, $subtotal);
    }

    protected function ensureOwnership(User $user, CustomerOrder $order): void
    {
        if ($order->user_id !== $user->id || $order->platform !== UserCartItem::PLATFORM_TAOBAO) {
            throw ValidationException::withMessages([
                'order' => [__('api.order_not_found')],
            ]);
        }
    }
}
