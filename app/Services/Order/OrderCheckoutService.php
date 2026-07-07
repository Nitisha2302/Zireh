<?php

namespace App\Services\Order;

use App\Exceptions\Elim\ElimRequestException;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderItem;
use App\Models\OrderStatus;
use App\Models\Platform;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserCartItem;
use App\Models\Warehouse;
use App\Services\Currency\CurrencyExchangeService;
use App\Services\Elim\ElimApiClient;
use App\Services\Elim\ElimDemoCheckoutService;
use App\Services\Elim\ElimOrderApiService;
use App\Services\Shipping\ShippingRateService;
use App\Services\Wallet\WalletService;
use App\Support\Elim\ElimWarehouseAddress;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderCheckoutService
{
    public function __construct(
        private readonly ShippingRateService $shippingRateService,
        private readonly CurrencyExchangeService $currencyExchangeService,
        private readonly ElimApiClient $elimClient,
        private readonly ElimDemoCheckoutService $demoCheckout,
        private readonly ElimOrderApiService $elimOrders,
        private readonly WalletService $walletService,
    ) {}

    public function isDemoMode(): bool
    {
        return $this->demoCheckout->isEnabled();
    }

    public function resolveContext(User $user, array $options): CheckoutContext
    {
        $warehouse = Warehouse::query()
            ->whereKey($options['warehouse_id'])
            ->where('status', Warehouse::STATUS_ACTIVE)
            ->first();

        if (! $warehouse) {
            throw ValidationException::withMessages([
                'warehouse_id' => [__('api.warehouse_not_available')],
            ]);
        }

        $address = UserAddress::query()
            ->whereKey($options['address_id'])
            ->where('user_id', $user->id)
            ->first();

        if (! $address) {
            throw ValidationException::withMessages([
                'address_id' => [__('api.address_not_found')],
            ]);
        }

        $shippingMethod = ShippingMethod::query()
            ->whereKey($options['shipping_method_id'])
            ->where('is_active', true)
            ->first();

        if (! $shippingMethod) {
            throw ValidationException::withMessages([
                'shipping_method_id' => [__('api.shipping_method_not_found')],
            ]);
        }

        $paymentMethod = (string) $options['payment_method'];

        if (! in_array($paymentMethod, [CustomerOrder::PAYMENT_METHOD_WALLET, CustomerOrder::PAYMENT_METHOD_ONLINE], true)) {
            throw ValidationException::withMessages([
                'payment_method' => [__('api.payment_method_invalid')],
            ]);
        }

        $cargoShipping = $this->shippingRateService->calculate(
            $shippingMethod->code,
            (float) $options['weight_kg'],
            isset($options['length_cm']) ? (float) $options['length_cm'] : null,
            isset($options['width_cm']) ? (float) $options['width_cm'] : null,
            isset($options['height_cm']) ? (float) $options['height_cm'] : null,
        );

        $cargoShippingFeeTjs = (float) $cargoShipping['shipping_cost'];
        $cargoShippingFeeCny = $this->convertTjsToCny($cargoShippingFeeTjs);

        return new CheckoutContext(
            warehouse: $warehouse,
            address: $address,
            shippingMethod: $shippingMethod,
            paymentMethod: $paymentMethod,
            cargoShipping: $cargoShipping,
            cargoShippingFeeTjs: $cargoShippingFeeTjs,
            cargoShippingFeeCny: $cargoShippingFeeCny,
            weightKg: (float) $options['weight_kg'],
        );
    }

    public function callPreview(array $payload): array
    {
        if ($this->isDemoMode()) {
            return $this->demoCheckout->preview($payload);
        }

        try {
            return $this->elimClient->post('/v1/orders/preview', $payload);
        } catch (ElimRequestException $exception) {
            throw ValidationException::withMessages([
                'preview' => [$exception->getMessage()],
            ]);
        }
    }

    public function callCreate(array $payload, array $parsedPreview): array
    {
        if ($this->isDemoMode()) {
            return $this->demoCheckout->create($payload, $parsedPreview);
        }

        try {
            return $this->elimClient->post('/v1/orders', $payload);
        } catch (ElimRequestException $exception) {
            throw ValidationException::withMessages([
                'checkout' => [$exception->getMessage()],
            ]);
        }
    }

    public function parsePreviewResponse(array $response): array
    {
        $data = $response['data'] ?? $response;

        return [
            'goods_subtotal_cny' => (float) ($data['goods_amount_cny'] ?? $data['goods_amount'] ?? $data['goods_subtotal'] ?? 0),
            'shipping_fee_cny' => (float) ($data['shipping_fee_cny'] ?? $data['shipping_fee'] ?? 0),
            'service_fee_cny' => isset($data['service_fee_cny']) ? (float) $data['service_fee_cny'] : null,
            'unavailable_items' => $this->normalizeUnavailableItems($data['unavailable_items'] ?? []),
            'raw' => $response,
        ];
    }

    public function hasUnavailableCartItems(array $parsed): bool
    {
        if ($this->isDemoMode()) {
            return false;
        }

        return $this->normalizeUnavailableItems($parsed['unavailable_items'] ?? []) !== [];
    }

    /**
     * @return list<mixed>
     */
    public function normalizeUnavailableItems(mixed $value): array
    {
        if ($value === null || $value === false || $value === '') {
            return [];
        }

        if (! is_array($value)) {
            return [];
        }

        if (array_key_exists('items', $value) && is_array($value['items'])) {
            return $this->filterUnavailableEntries($value['items']);
        }

        if (array_key_exists('line_items', $value) && is_array($value['line_items'])) {
            return $this->filterUnavailableEntries($value['line_items']);
        }

        if (isset($value['count']) && (int) $value['count'] === 0) {
            return [];
        }

        if (array_is_list($value)) {
            return $this->filterUnavailableEntries($value);
        }

        return $this->filterUnavailableEntries(array_values($value));
    }

    /**
     * @param  array<int, mixed>  $items
     * @return list<mixed>
     */
    protected function filterUnavailableEntries(array $items): array
    {
        return array_values(array_filter(
            $items,
            fn (mixed $item): bool => $item !== null && $item !== false && $item !== ''
        ));
    }

    public function calculateCustomerTotalCny(
        float $goodsSubtotal,
        float $elimShippingFee,
        ?float $serviceFee,
        float $commissionAmount,
        float $cargoShippingFeeCny,
    ): float {
        return round(
            $goodsSubtotal + $elimShippingFee + ($serviceFee ?? 0) + $commissionAmount + $cargoShippingFeeCny,
            2
        );
    }

    /**
     * @param  Collection<int, UserCartItem>  $items
     */
    public function persistOrder(
        User $user,
        string $platformCode,
        Collection $items,
        CheckoutContext $context,
        array $previewResponse,
        array $createResponse,
        array $parsed,
        array $commission,
        array $options,
    ): CustomerOrder {
        $orderData = $createResponse['data'] ?? $createResponse;
        $platform = Platform::query()->where('code', $platformCode)->firstOrFail();
        $receiverAddress = ElimWarehouseAddress::get();
        $exchangeRate = $this->currencyExchangeService->getRate();

        $customerTotalCny = $this->calculateCustomerTotalCny(
            $parsed['goods_subtotal_cny'],
            $parsed['shipping_fee_cny'],
            $parsed['service_fee_cny'],
            $commission['commission_amount'] ?? 0,
            $context->cargoShippingFeeCny,
        );

        $order = CustomerOrder::query()->create([
            'user_id' => $user->id,
            'warehouse_id' => $context->warehouse->id,
            'user_address_id' => $context->address->id,
            'shipping_method_id' => $context->shippingMethod->id,
            'platform_id' => $platform->id,
            'platform' => $platformCode,
            'elim_order_id' => (string) ($orderData['id'] ?? null),
            'status' => OrderStatus::CODE_PAID,
            'payment_status' => (string) ($orderData['payment_status'] ?? 'unpaid'),
            'payment_method' => $context->paymentMethod,
            'goods_subtotal_cny' => $parsed['goods_subtotal_cny'],
            'shipping_fee_cny' => $parsed['shipping_fee_cny'],
            'cargo_shipping_fee_tjs' => $context->cargoShippingFeeTjs,
            'cargo_shipping_fee_cny' => $context->cargoShippingFeeCny,
            'elim_service_fee_cny' => $parsed['service_fee_cny'],
            'commission_slab_id' => $commission['slab_id'] ?? null,
            'commission_percentage' => $commission['commission_percentage'] ?? 0,
            'commission_amount' => $commission['commission_amount'] ?? 0,
            'customer_total_cny' => $customerTotalCny,
            'exchange_rate' => $exchangeRate,
            'customer_total_tjs' => round(
                ($this->currencyExchangeService->convertCnyToTjs($customerTotalCny - $context->cargoShippingFeeCny) ?? 0)
                + $context->cargoShippingFeeTjs,
                2
            ),
            'receiver_address' => $receiverAddress,
            'warehouse_snapshot' => $context->warehouseSnapshot(),
            'address_snapshot' => $context->addressSnapshot(),
            'remark' => $options['remark'] ?? null,
            'elim_preview_snapshot' => $previewResponse,
            'elim_create_snapshot' => $createResponse,
            'is_demo_order' => $this->isDemoMode(),
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

        return $order;
    }

    public function processWalletPayment(
        User $user,
        CustomerOrder $order,
        bool $onlyIfWalletMethod = true,
    ): CustomerOrder {
        if ($onlyIfWalletMethod && $order->payment_method !== CustomerOrder::PAYMENT_METHOD_WALLET) {
            return $order;
        }

        if ($order->payment_status === 'paid') {
            return $order;
        }

        $amountDue = $order->paymentAmountTjs();
        $walletBalance = $this->walletService->getBalance($user);

        if ($walletBalance < $amountDue) {
            throw ValidationException::withMessages([
                'wallet' => [__('api.wallet_insufficient_balance')],
                'deficit' => [(string) max(0, round($amountDue - $walletBalance, 2))],
            ]);
        }

        $transaction = $this->walletService->payForOrder(
            $user,
            $order,
            $amountDue,
            'Checkout payment for order '.($order->elim_order_id ?: '#'.$order->id)
        );

        if ($this->isDemoMode() || $order->is_demo_order) {
            $order->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
                'wallet_transaction_id' => $transaction->id,
                'status' => OrderStatus::CODE_PAID,
            ]);

            return $order->fresh();
        }

        if (empty($order->elim_order_id)) {
            throw ValidationException::withMessages([
                'payment' => [__('api.order_elim_id_missing')],
            ]);
        }

        try {
            $confirmResponse = $this->elimOrders->confirmPayment($order->elim_order_id);
        } catch (ElimRequestException $exception) {
            $this->walletService->refundOrderPayment(
                $transaction,
                'Refund: Elim payment failed for '.$order->elim_order_id
            );

            if ($this->elimOrders->isInsufficientBalanceError($exception)) {
                $payload = $this->elimOrders->insufficientBalancePayload($exception);

                throw ValidationException::withMessages([
                    'elim_wallet' => [__('api.elim_purchasing_wallet_insufficient')],
                    'deficit' => [(string) $payload['deficit']],
                    'required' => [(string) $payload['required']],
                ]);
            }

            throw ValidationException::withMessages([
                'payment' => [$exception->getMessage()],
            ]);
        }

        $order->update([
            'payment_status' => 'paid',
            'paid_at' => $confirmResponse['paid_at'] ?? now(),
            'wallet_transaction_id' => $transaction->id,
            'elim_service_fee_cny' => $confirmResponse['service_fee_cny'] ?? $order->elim_service_fee_cny,
            'status' => OrderStatus::CODE_PAID,
        ]);

        return $order->fresh();
    }

    public function finalizeCheckout(User $user, CustomerOrder $order): CustomerOrder
    {
        return DB::transaction(function () use ($user, $order): CustomerOrder {
            if ($order->payment_method === CustomerOrder::PAYMENT_METHOD_WALLET) {
                $order = $this->processWalletPayment($user, $order);
            } elseif (
                $order->payment_method === CustomerOrder::PAYMENT_METHOD_ONLINE
                && ($this->isDemoMode() || $order->is_demo_order)
            ) {
                $order->update([
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                    'status' => OrderStatus::CODE_PAID,
                ]);
                $order = $order->fresh();
            }

            if ($order->payment_status === 'paid' && $order->status !== OrderStatus::CODE_CANCELLED) {
                $order->update(['status' => OrderStatus::CODE_PAID]);
                $order = $order->fresh();
            }

            return $order->load([
                'items',
                'orderStatus',
                'warehouse',
                'userAddress',
                'shippingMethod',
                'walletTransaction',
            ]);
        });
    }

    public function convertTjsToCny(float $tjs): float
    {
        $rate = $this->currencyExchangeService->getRate();

        if ($rate <= 0) {
            return 0.0;
        }

        return round($tjs / $rate, 2);
    }
}
