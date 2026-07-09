<?php

namespace App\Services\Cart\Taobao;

use App\Models\CustomerOrder;
use App\Models\User;
use App\Models\UserCartItem;
use App\Services\Order\CustomerOrderLifecycleService;
use App\Services\Order\OrderCheckoutService;
use App\Support\Elim\ElimWarehouseAddress;
use App\Support\Elim\ProductNormalizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaobaoOrderService
{
    public function __construct(
        protected TaobaoCartService $cartService,
        protected ProductNormalizer $normalizer,
        protected CustomerOrderLifecycleService $lifecycleService,
        protected OrderCheckoutService $checkoutService,
    ) {}

    public function preview(User $user, array $options = []): array
    {
        $item = $this->cartService->resolveCartItem($user, (int) $options['cart_item_id']);
        $items = $this->itemsForCheckout($item);

        $context = $this->checkoutService->resolveContext($user, $options);
        $payload = $this->buildOrderPayload($items, $options);
        $previewResponse = $this->checkoutService->callPreview($payload);
        $parsed = $this->checkoutService->parsePreviewResponse($previewResponse);

        if ($this->checkoutService->hasUnavailableCartItems($parsed)) {
            throw ValidationException::withMessages([
                'cart' => [__('api.cart_unavailable_items')],
            ]);
        }

        return [
            'platform' => UserCartItem::PLATFORM_TAOBAO,
            'cart_item_id' => $item->id,
            'demo_mode' => $this->checkoutService->isDemoMode(),
            'items' => $items->values()->all(),
            'checkout' => $context->toPreviewArray(),
            'final_amount' => round((float) $item->final_amount_tjs, 2),
        ];
    }

    public function checkout(User $user, array $options = []): CustomerOrder
    {
        $item = $this->cartService->resolveCartItem($user, (int) $options['cart_item_id']);
        $items = $this->itemsForCheckout($item);

        $context = $this->checkoutService->resolveContext($user, $options);
        $payload = $this->buildOrderPayload($items, $options);
        $previewResponse = $this->checkoutService->callPreview($payload);
        $parsed = $this->checkoutService->parsePreviewResponse($previewResponse);

        if ($this->checkoutService->hasUnavailableCartItems($parsed)) {
            throw ValidationException::withMessages([
                'unavailable_items' => [__('api.cart_unavailable_items')],
            ]);
        }

        $createResponse = $this->checkoutService->callCreate($payload, $parsed);

        if (($createResponse['status'] ?? null) === 'unknown') {
            throw ValidationException::withMessages([
                'checkout' => [__('api.order_create_unknown')],
            ]);
        }

        return DB::transaction(function () use (
            $user,
            $item,
            $items,
            $context,
            $previewResponse,
            $createResponse,
            $parsed,
            $options
        ): CustomerOrder {
            $order = $this->checkoutService->persistOrder(
                $user,
                UserCartItem::PLATFORM_TAOBAO,
                $items,
                $context,
                $previewResponse,
                $createResponse,
                $parsed,
                $options,
            );

            $this->cartService->removeCartItem($user, $item);

            return $this->checkoutService->finalizeCheckout($user, $order);
        });
    }

    public function list(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return CustomerOrder::query()
            ->where('user_id', $user->id)
            ->where('platform', UserCartItem::PLATFORM_TAOBAO)
            ->with(['items', 'orderStatus', 'warehouse', 'userAddress', 'shippingMethod'])
            ->latest()
            ->paginate($perPage);
    }

    public function show(User $user, CustomerOrder $order, bool $syncFromElim = false): CustomerOrder
    {
        $this->ensureOwnership($user, $order);

        if ($syncFromElim && $order->elim_order_id && ! $order->is_demo_order) {
            return $this->lifecycleService->syncFromElim($user, $order);
        }

        return $order->load(['items', 'orderStatus', 'warehouse', 'userAddress', 'shippingMethod']);
    }

    protected function itemsForCheckout(UserCartItem $item): Collection
    {
        return collect([$item]);
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

    protected function ensureOwnership(User $user, CustomerOrder $order): void
    {
        if ($order->user_id !== $user->id || $order->platform !== UserCartItem::PLATFORM_TAOBAO) {
            throw ValidationException::withMessages([
                'order' => [__('api.order_not_found')],
            ]);
        }
    }
}
