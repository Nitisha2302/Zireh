<?php

namespace App\Services\Order;

use App\Exceptions\Elim\ElimRequestException;
use App\Models\CustomerOrder;
use App\Models\OrderStatus;
use App\Models\User;
use App\Models\UserCartItem;
use App\Services\Elim\ElimOrderApiService;
use App\Services\Order\OrderCheckoutService;
use App\Services\Wallet\WalletService;
use Illuminate\Validation\ValidationException;

class CustomerOrderLifecycleService
{
    public function __construct(
        private readonly ElimOrderApiService $elimOrders,
        private readonly WalletService $walletService,
        private readonly OrderCheckoutService $checkoutService,
    ) {}

    public function syncFromElim(User $user, CustomerOrder $order): CustomerOrder
    {
        $this->ensureOwnership($user, $order);

        if ($order->is_demo_order) {
            throw ValidationException::withMessages([
                'sync' => [__('api.order_demo_sync_unavailable')],
            ]);
        }

        $this->assertHasElimOrderId($order);

        try {
            $response = $this->elimOrders->detail($order->elim_order_id);
        } catch (ElimRequestException $exception) {
            throw ValidationException::withMessages([
                'sync' => [$exception->getMessage()],
            ]);
        }

        return $this->applyElimDetail($order, $response);
    }

    public function cancel(User $user, CustomerOrder $order): CustomerOrder
    {
        $this->ensureOwnership($user, $order);

        if (! $order->isCancellable()) {
            throw ValidationException::withMessages([
                'order' => [__('api.order_not_cancellable')],
            ]);
        }

        if ($order->is_demo_order) {
            $order->update(['status' => OrderStatus::CODE_CANCELLED]);

            return $order->fresh()->load(['items', 'orderStatus', 'warehouse', 'userAddress', 'shippingMethod']);
        }

        $this->assertHasElimOrderId($order);

        try {
            $this->elimOrders->cancel($order->elim_order_id);
        } catch (ElimRequestException $exception) {
            throw ValidationException::withMessages([
                'cancel' => [$exception->getMessage()],
            ]);
        }

        return $this->syncFromElim($user, $order->fresh());
    }

    public function paymentPreview(User $user, CustomerOrder $order): array
    {
        $this->ensureOwnership($user, $order);

        $amountDue = $order->paymentAmountTjs();
        $walletBalance = $this->walletService->getBalance($user);

        return [
            'order_id' => $order->id,
            'elim_order_id' => $order->elim_order_id,
            'payment_status' => $order->payment_status,
            'status' => $order->status,
            'breakdown' => [
                'goods_subtotal_cny' => (float) $order->goods_subtotal_cny,
                'shipping_fee_cny' => (float) $order->shipping_fee_cny,
                'elim_service_fee_cny' => $order->elim_service_fee_cny !== null
                    ? (float) $order->elim_service_fee_cny
                    : 0.0,
                'commission_amount' => (float) $order->commission_amount,
                'cargo_shipping_fee_cny' => (float) $order->cargo_shipping_fee_cny,
                'cargo_shipping_fee_tjs' => (float) $order->cargo_shipping_fee_tjs,
                'total_cny' => $order->paymentAmountCny(),
                'total_tjs' => $amountDue,
            ],
            'payment_amount_tjs' => $amountDue,
            'payment_method' => $order->payment_method,
            'wallet' => [
                'balance' => $walletBalance,
                'balance_tjs' => $walletBalance,
                'currency' => \App\Models\UserWallet::CURRENCY_TJS,
                'available_tjs' => $walletBalance,
                'deficit_tjs' => max(0, round($amountDue - $walletBalance, 2)),
            ],
            'can_pay' => $order->payment_status === 'unpaid' && $walletBalance >= $amountDue,
        ];
    }

    public function pay(User $user, CustomerOrder $order): CustomerOrder
    {
        $this->ensureOwnership($user, $order);

        if ($order->payment_status === 'paid') {
            throw ValidationException::withMessages([
                'payment' => [__('api.order_already_paid')],
            ]);
        }

        if ($order->status === 'unknown') {
            throw ValidationException::withMessages([
                'payment' => [__('api.order_create_unknown')],
            ]);
        }

        if ($order->status === OrderStatus::CODE_CANCELLED) {
            throw ValidationException::withMessages([
                'payment' => [__('api.order_cancelled_cannot_pay')],
            ]);
        }

        $order = $this->checkoutService->processWalletPayment($user, $order, onlyIfWalletMethod: false);

        if (! $order->is_demo_order && $order->elim_order_id) {
            try {
                return $this->applyElimDetail(
                    $order,
                    $this->elimOrders->detail($order->elim_order_id)
                )->load(['items', 'orderStatus', 'warehouse', 'userAddress', 'shippingMethod', 'walletTransaction']);
            } catch (ElimRequestException) {
                // Payment succeeded locally; return order without live sync.
            }
        }

        return $order->load(['items', 'orderStatus', 'warehouse', 'userAddress', 'shippingMethod', 'walletTransaction']);
    }

    public function logistics(User $user, CustomerOrder $order, ?int $packageId = null): array
    {
        $this->ensureOwnership($user, $order);

        if ($order->platform === UserCartItem::PLATFORM_1688) {
            $detail = $order->elim_detail_snapshot;

            if (! is_array($detail) || empty($detail)) {
                $order = $this->syncFromElim($user, $order);
                $detail = $order->elim_detail_snapshot;
            }

            $data = $detail['data'] ?? $detail;

            return [
                'platform' => UserCartItem::PLATFORM_1688,
                'source' => 'order_detail',
                'logistics' => $data['logistics'] ?? $data['logistics_info'] ?? [],
            ];
        }

        if ($packageId === null) {
            throw ValidationException::withMessages([
                'package_id' => [__('api.order_logistics_package_required')],
            ]);
        }

        try {
            $response = $this->elimOrders->logisticDetail($packageId);
        } catch (ElimRequestException $exception) {
            throw ValidationException::withMessages([
                'logistics' => [$exception->getMessage()],
            ]);
        }

        return [
            'platform' => UserCartItem::PLATFORM_TAOBAO,
            'source' => 'logistic_detail',
            'package_id' => $packageId,
            'logistics' => $response['data'] ?? $response,
        ];
    }

    public function elimPurchasingWallet(): array
    {
        try {
            $response = $this->elimOrders->purchasingWallet();
        } catch (ElimRequestException $exception) {
            throw ValidationException::withMessages([
                'elim_wallet' => [$exception->getMessage()],
            ]);
        }

        $data = $response['data'] ?? $response;
        $balance = (float) ($data['balance'] ?? 0);
        $frozen = (float) ($data['frozen_balance'] ?? 0);

        return [
            'balance' => $balance,
            'frozen_balance' => $frozen,
            'available' => (float) ($data['available'] ?? max(0, $balance - $frozen)),
        ];
    }

    public function elimExchangeRates(): array
    {
        try {
            $response = $this->elimOrders->exchangeRates();
        } catch (ElimRequestException $exception) {
            throw ValidationException::withMessages([
                'exchange_rates' => [$exception->getMessage()],
            ]);
        }

        return $response['data'] ?? $response;
    }

    protected function applyElimDetail(CustomerOrder $order, array $response): CustomerOrder
    {
        $parsed = $this->elimOrders->parseDetailResponse($response);

        $updates = [
            'elim_detail_snapshot' => $response,
        ];

        if ($parsed['status'] !== null && $parsed['status'] !== '') {
            $mappedStatus = OrderStatus::mapFromElimStatus($parsed['status']);

            if ($mappedStatus !== null) {
                $updates['status'] = $mappedStatus;
            }
        }

        if ($parsed['payment_status'] !== null && $parsed['payment_status'] !== '') {
            $updates['payment_status'] = $parsed['payment_status'];
        }

        $order->update($updates);

        return $order->fresh()->load(['items', 'orderStatus']);
    }

    protected function ensureOwnership(User $user, CustomerOrder $order): void
    {
        if ($order->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'order' => [__('api.order_not_found')],
            ]);
        }
    }

    protected function assertHasElimOrderId(CustomerOrder $order): void
    {
        if (empty($order->elim_order_id)) {
            throw ValidationException::withMessages([
                'order' => [__('api.order_elim_id_missing')],
            ]);
        }
    }
}
