<?php

namespace App\Services\Cart\Platform1688;

use App\Models\CustomerOrder;
use App\Models\Platform;
use App\Models\User;
use App\Models\UserCartItem;
use App\Services\Order\CustomerOrderLifecycleService;
use App\Services\Order\OrderCheckoutService;
use App\Services\PlatformCommissionService;
use App\Support\Currency\CurrencyPriceConverter;
use App\Support\Elim\ElimWarehouseAddress;
use App\Support\Elim\ProductNormalizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Platform1688OrderService
{
    public function __construct(
        protected Platform1688CartService $cartService,
        protected ProductNormalizer $normalizer,
        protected PlatformCommissionService $commissionService,
        protected CurrencyPriceConverter $currencyPriceConverter,
        protected CustomerOrderLifecycleService $lifecycleService,
        protected OrderCheckoutService $checkoutService,
    ) {}

    public function preview(User $user, array $options = []): array
    {
        $items = $this->cartService->cartItems($user);

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => [__('api.cart_empty')],
            ]);
        }

        $context = $this->checkoutService->resolveContext($user, $options);
        $payload = $this->buildOrderPayload($items, $options);
        $previewResponse = $this->checkoutService->callPreview($payload);
        $parsed = $this->checkoutService->parsePreviewResponse($previewResponse);

        if ($this->checkoutService->hasUnavailableCartItems($parsed)) {
            throw ValidationException::withMessages([
                'cart' => [__('api.cart_unavailable_items')],
            ]);
        }

        $commission = $this->resolveCommission($parsed['goods_subtotal_cny']);

        return $this->currencyPriceConverter->applyToCheckout([
            'platform' => UserCartItem::PLATFORM_1688,
            'demo_mode' => $this->checkoutService->isDemoMode(),
            'items' => $items->values()->all(),
            'checkout' => $context->toPreviewArray(),
            'elim_preview' => $parsed,
            'commission' => $commission,
            'customer_total' => $this->checkoutService->calculateCustomerTotalCny(
                $parsed['goods_subtotal_cny'],
                $parsed['shipping_fee_cny'],
                $parsed['service_fee_cny'],
                $commission['commission_amount'] ?? 0,
                $context->cargoShippingFeeCny,
            ),
        ]);
    }

    public function checkout(User $user, array $options = []): CustomerOrder
    {
        $items = $this->cartService->cartItems($user);

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => [__('api.cart_empty')],
            ]);
        }

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

        $commission = $this->resolveCommission($parsed['goods_subtotal_cny']);

        return DB::transaction(function () use (
            $user,
            $items,
            $context,
            $previewResponse,
            $createResponse,
            $parsed,
            $commission,
            $options
        ): CustomerOrder {
            $order = $this->checkoutService->persistOrder(
                $user,
                UserCartItem::PLATFORM_1688,
                $items,
                $context,
                $previewResponse,
                $createResponse,
                $parsed,
                $commission,
                $options,
            );

            $this->cartService->clearCartItems($user);

            return $this->checkoutService->finalizeCheckout($user, $order);
        });
    }

    public function list(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return CustomerOrder::query()
            ->where('user_id', $user->id)
            ->where('platform', UserCartItem::PLATFORM_1688)
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

    protected function buildOrderPayload($items, array $options): array
    {
        $payload = [
            'platform' => 'alibaba',
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

    protected function resolveCommission(float $subtotal): array
    {
        if ($subtotal <= 0) {
            return [
                'slab_id' => null,
                'commission_percentage' => 0.0,
                'commission_amount' => 0.0,
            ];
        }

        $platform = Platform::query()->where('code', UserCartItem::PLATFORM_1688)->firstOrFail();

        return $this->commissionService->calculate($platform, $subtotal);
    }

    protected function ensureOwnership(User $user, CustomerOrder $order): void
    {
        if ($order->user_id !== $user->id || $order->platform !== UserCartItem::PLATFORM_1688) {
            throw ValidationException::withMessages([
                'order' => [__('api.order_not_found')],
            ]);
        }
    }
}
