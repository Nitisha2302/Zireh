<?php

namespace App\Services\Order;

use App\Exceptions\Elim\ElimRequestException;
use App\Models\Admin;
use App\Models\CustomerOrder;
use App\Models\OrderStatus;
use App\Models\User;
use App\Models\UserCartItem;
use App\Models\UserWallet;
use App\Services\Elim\ElimOrderApiService;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerOrderLifecycleService
{
    public function __construct(
        private readonly ElimOrderApiService $elimOrders,
        private readonly WalletService $walletService,
        private readonly OrderCheckoutService $checkoutService,
        private readonly OrderPickupService $pickupService,
    ) {}

    public function syncFromElim(User $user, CustomerOrder $order, bool $forceRefresh = false): CustomerOrder
    {
        $this->ensureOwnership($user, $order);

        return $this->performElimSync($order, $forceRefresh);
    }

    public function refreshFromElim(User $user, CustomerOrder $order): CustomerOrder
    {
        return $this->syncFromElim($user, $order, forceRefresh: true);
    }

    public function syncFromElimByStaff(CustomerOrder $order, bool $forceRefresh = false): CustomerOrder
    {
        return $this->performElimSync($order, $forceRefresh);
    }

    protected function performElimSync(CustomerOrder $order, bool $forceRefresh = false): CustomerOrder
    {
        if ($order->platform === UserCartItem::PLATFORM_JD) {
            throw ValidationException::withMessages([
                'sync' => [__('api.jd_order_sync_unavailable')],
            ]);
        }

        if ($order->is_demo_order) {
            throw ValidationException::withMessages([
                'sync' => [__('api.order_demo_sync_unavailable')],
            ]);
        }

        $this->assertHasElimOrderId($order);

        try {
            $response = $forceRefresh
                ? $this->elimOrders->refreshOrder($order->elim_order_id)
                : $this->elimOrders->detail($order->elim_order_id);
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

        return $this->cancelAndRefund($order);
    }

    public function cancelByStaff(Admin $admin, CustomerOrder $order): CustomerOrder
    {
        return $this->cancelAndRefund($order, $admin);
    }

    public function cancelAndRefund(CustomerOrder $order, ?Admin $admin = null): CustomerOrder
    {
        if (! $order->isCancellable()) {
            throw ValidationException::withMessages([
                'order' => [__('admin.order_not_cancellable')],
            ]);
        }

        if (! $order->is_demo_order) {
            $this->assertHasElimOrderId($order);

            if ($order->payment_status === CustomerOrder::PAYMENT_STATUS_REQUESTED) {
                try {
                    $this->elimOrders->cancelPaymentRequest($order->elim_order_id);
                } catch (ElimRequestException) {
                    // Proceed with order cancellation even if payment request was already processed/cancelled.
                }
            }

            try {
                $this->elimOrders->cancel($order->elim_order_id);
            } catch (ElimRequestException $exception) {
                throw ValidationException::withMessages([
                    'cancel' => [$exception->getMessage()],
                ]);
            }
        }

        return DB::transaction(function () use ($order, $admin): CustomerOrder {
            $lockedOrder = CustomerOrder::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedOrder->isCancellable()) {
                throw ValidationException::withMessages([
                    'order' => [__('admin.order_already_cancelled_or_refunded')],
                ]);
            }

            $refundAmount = $lockedOrder->paymentAmountTjs();

            if ($refundAmount <= 0) {
                throw ValidationException::withMessages([
                    'order' => [__('admin.order_refund_amount_invalid')],
                ]);
            }

            $description = __('admin.order_cancellation_refund_description', [
                'id' => $lockedOrder->id,
                'elim' => $lockedOrder->elim_order_id ?: '#'.$lockedOrder->id,
                'method' => $lockedOrder->payment_method ?: 'unknown',
            ]);

            $refundTransaction = $this->walletService->creditOrderCancellationRefund(
                $lockedOrder,
                $refundAmount,
                $description,
                $admin
            );

            $lockedOrder->update([
                'status' => OrderStatus::CODE_CANCELLED,
                'payment_status' => CustomerOrder::PAYMENT_STATUS_REFUNDED,
                'cancellation_refund_transaction_id' => $refundTransaction->id,
                'cancelled_by_admin_id' => $admin?->id,
                'cancelled_at' => now(),
            ]);

            return $lockedOrder->fresh()->load([
                'items',
                'orderStatus',
                'warehouse',
                'userAddress',
                'shippingMethod',
                'user',
                'walletTransaction',
                'cancellationRefundTransaction',
                'cancelledByAdmin',
            ]);
        });
    }

    public function pickupPreview(User $user, CustomerOrder $order): array
    {
        $this->ensureOwnership($user, $order);

        $details = $this->pickupService->pickupDetailsForCustomer($order);
        $amountDue = $order->pickupPaymentAmountTjs();
        $walletBalance = $this->walletService->getBalance($user);
        $pending = $order->pickup_payment_status === CustomerOrder::PICKUP_PAYMENT_STATUS_PENDING
            && $amountDue > 0;
        $canPayWallet = $pending && $walletBalance >= $amountDue;
        $canPayOnline = $pending;

        return array_merge($details, [
            'wallet' => [
                'balance' => $walletBalance,
                'currency' => UserWallet::CURRENCY_TJS,
                'deficit_tjs' => max(0, round($amountDue - $walletBalance, 2)),
            ],
            'payment_methods' => [
                CustomerOrder::PAYMENT_METHOD_WALLET => [
                    'can_pay' => $canPayWallet,
                ],
                CustomerOrder::PAYMENT_METHOD_ONLINE => [
                    'can_pay' => $canPayOnline,
                ],
            ],
            'can_pay' => $canPayWallet || $canPayOnline,
        ]);
    }

    public function payPickupShipping(User $user, CustomerOrder $order, string $paymentMethod): CustomerOrder
    {
        $this->ensureOwnership($user, $order);

        if (! $order->isReadyForPickup()) {
            throw ValidationException::withMessages([
                'pickup' => [__('api.pickup_not_ready')],
            ]);
        }

        if ($order->isPickupShippingPaid()) {
            throw ValidationException::withMessages([
                'pickup_payment' => [__('api.pickup_shipping_already_paid')],
            ]);
        }

        $amountDue = $order->pickupPaymentAmountTjs();

        if ($amountDue <= 0) {
            throw ValidationException::withMessages([
                'pickup_payment' => [__('api.pickup_shipping_amount_invalid')],
            ]);
        }

        $description = 'Pickup shipping payment for order #'.$order->id;

        $transaction = $paymentMethod === CustomerOrder::PAYMENT_METHOD_ONLINE
            ? $this->walletService->recordPickupShippingOnlinePayment($user, $order, $amountDue, $description)
            : $this->walletService->payForPickupShipping($user, $order, $amountDue, $description);

        $order->update([
            'pickup_payment_status' => CustomerOrder::PICKUP_PAYMENT_STATUS_PAID,
            'pickup_payment_method' => $paymentMethod,
            'pickup_paid_at' => now(),
            'pickup_wallet_transaction_id' => $transaction->id,
        ]);

        return $order->fresh()->load(['items', 'orderStatus', 'warehouse', 'shippingMethod', 'pickupWalletTransaction']);
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
            'final_amount' => $amountDue,
            'payment_method' => $order->payment_method,
            'wallet' => [
                'balance' => $walletBalance,
                'balance_tjs' => $walletBalance,
                'currency' => UserWallet::CURRENCY_TJS,
                'available_tjs' => $walletBalance,
                'deficit_tjs' => max(0, round($amountDue - $walletBalance, 2)),
            ],
            'can_pay' => in_array($order->payment_status, ['unpaid', 'rejected'], true) && $walletBalance >= $amountDue,
        ];
    }

    public function pay(User $user, CustomerOrder $order): CustomerOrder
    {
        $this->ensureOwnership($user, $order);

        if (in_array($order->payment_status, [
            CustomerOrder::PAYMENT_STATUS_PAID,
            CustomerOrder::PAYMENT_STATUS_REQUESTED,
            CustomerOrder::PAYMENT_STATUS_APPROVED,
            CustomerOrder::PAYMENT_STATUS_REFUNDED,
        ], true)) {
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

    public function requestPayment(User $user, CustomerOrder $order): CustomerOrder
    {
        $this->ensureOwnership($user, $order);

        if (in_array($order->payment_status, ['paid', 'requested', 'approved'], true)) {
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

        $this->assertHasElimOrderId($order);

        $transaction = null;

        if ($order->wallet_transaction_id === null) {
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
                'Payment request for order '.$order->elim_order_id
            );
        }

        try {
            $response = $this->elimOrders->requestPayment($order->elim_order_id);
        } catch (ElimRequestException $exception) {
            if ($transaction !== null) {
                $this->walletService->refundOrderPayment(
                    $transaction,
                    'Refund: Elim payment request failed for '.$order->elim_order_id
                );
            }

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
            'payment_status' => CustomerOrder::PAYMENT_STATUS_REQUESTED,
            'wallet_transaction_id' => $transaction ? $transaction->id : $order->wallet_transaction_id,
            'elim_service_fee_cny' => $response['service_fee_cny'] ?? $order->elim_service_fee_cny,
        ]);

        return $order->fresh()->load(['items', 'orderStatus', 'warehouse', 'userAddress', 'shippingMethod', 'walletTransaction']);
    }

    public function cancelPaymentRequest(User $user, CustomerOrder $order): CustomerOrder
    {
        $this->ensureOwnership($user, $order);

        if ($order->payment_status !== CustomerOrder::PAYMENT_STATUS_REQUESTED) {
            throw ValidationException::withMessages([
                'payment' => ['Payment request can only be cancelled while in requested state.'],
            ]);
        }

        $this->assertHasElimOrderId($order);

        try {
            $this->elimOrders->cancelPaymentRequest($order->elim_order_id);
        } catch (ElimRequestException $exception) {
            throw ValidationException::withMessages([
                'payment' => [$exception->getMessage()],
            ]);
        }

        return DB::transaction(function () use ($order): CustomerOrder {
            $lockedOrder = CustomerOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->wallet_transaction_id !== null && $lockedOrder->walletTransaction) {
                $this->walletService->refundOrderPayment(
                    $lockedOrder->walletTransaction,
                    'Refund: Payment request withdrawn for '.$lockedOrder->elim_order_id
                );
            }

            $lockedOrder->update([
                'payment_status' => CustomerOrder::PAYMENT_STATUS_UNPAID,
                'wallet_transaction_id' => null,
            ]);

            return $lockedOrder->fresh()->load(['items', 'orderStatus', 'warehouse', 'userAddress', 'shippingMethod']);
        });
    }

    public function paymentDetail(User $user, CustomerOrder $order): array
    {
        $this->ensureOwnership($user, $order);
        $this->assertHasElimOrderId($order);

        try {
            $response = $this->elimOrders->paymentDetail($order->elim_order_id);
        } catch (ElimRequestException $exception) {
            throw ValidationException::withMessages([
                'payment' => [$exception->getMessage()],
            ]);
        }

        return $response['data'] ?? $response;
    }

    public function logistics(User $user, CustomerOrder $order, int|string|null $packageId = null, ?string $lang = null): array
    {
        $this->ensureOwnership($user, $order);

        if ($order->platform === UserCartItem::PLATFORM_1688) {
            if ($packageId !== null && $packageId !== '' && $order->elim_order_id && ! $order->is_demo_order) {
                try {
                    $response = $this->elimOrders->logisticDetail($order->elim_order_id, $packageId, $lang);

                    return [
                        'platform' => UserCartItem::PLATFORM_1688,
                        'source' => 'logistic_detail',
                        'package_id' => $packageId,
                        'logistics' => $response['data'] ?? $response,
                    ];
                } catch (ElimRequestException $exception) {
                    throw ValidationException::withMessages([
                        'logistics' => [$exception->getMessage()],
                    ]);
                }
            }

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

        if ($order->platform === UserCartItem::PLATFORM_JD) {
            return [
                'platform' => UserCartItem::PLATFORM_JD,
                'source' => 'local',
                'logistics' => [],
                'message' => __('api.jd_order_logistics_unavailable'),
            ];
        }

        if ($packageId === null || $packageId === '') {
            throw ValidationException::withMessages([
                'package_id' => [__('api.order_logistics_package_required')],
            ]);
        }

        try {
            $elimOrderId = ! empty($order->elim_order_id) ? $order->elim_order_id : $packageId;
            $response = $this->elimOrders->logisticDetail($elimOrderId, $packageId, $lang);
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
        $available = (float) ($data['available_balance'] ?? $data['available'] ?? max(0, $balance - $frozen));

        return [
            'balance' => $balance,
            'frozen_balance' => $frozen,
            'available' => $available,
            'available_balance' => $available,
            'total_deposited' => isset($data['total_deposited']) ? (float) $data['total_deposited'] : null,
            'total_spent' => isset($data['total_spent']) ? (float) $data['total_spent'] : null,
            'updated_at' => $data['updated_at'] ?? null,
        ];
    }

    public function elimPurchasingTransactions(array $query = []): array
    {
        try {
            $response = $this->elimOrders->purchasingTransactions($query);
        } catch (ElimRequestException $exception) {
            throw ValidationException::withMessages([
                'elim_wallet' => [$exception->getMessage()],
            ]);
        }

        return $response['data'] ?? $response;
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

    public function applyWebhookOrderUpdate(CustomerOrder $order, array $orderData, array $webhookPayload = []): CustomerOrder
    {
        return DB::transaction(function () use ($order, $orderData, $webhookPayload): CustomerOrder {
            $lockedOrder = CustomerOrder::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existingSnapshot = is_array($lockedOrder->elim_detail_snapshot) ? $lockedOrder->elim_detail_snapshot : [];
            $existingData = is_array($existingSnapshot['data'] ?? null) ? $existingSnapshot['data'] : $existingSnapshot;

            $mergedData = array_merge($existingData, $orderData);
            $snapshot = [
                'data' => $mergedData,
                '_last_webhook' => [
                    'id' => $webhookPayload['id'] ?? null,
                    'event' => $webhookPayload['event'] ?? null,
                    'created_at' => $webhookPayload['created_at'] ?? null,
                    'order_updated_at' => $orderData['updated_at'] ?? null,
                ],
            ];

            $elimStatus = isset($orderData['status']) ? (string) $orderData['status'] : null;
            $paymentStatus = isset($orderData['payment_status']) ? (string) $orderData['payment_status'] : null;

            return $this->applyElimOrderState(
                $lockedOrder,
                $snapshot,
                $elimStatus,
                $paymentStatus,
                $orderData['tracking_numbers'] ?? null
            );
        });
    }

    protected function applyElimDetail(CustomerOrder $order, array $response): CustomerOrder
    {
        return DB::transaction(function () use ($order, $response): CustomerOrder {
            $lockedOrder = CustomerOrder::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $parsed = $this->elimOrders->parseDetailResponse($response);

            $existingSnapshot = is_array($lockedOrder->elim_detail_snapshot) ? $lockedOrder->elim_detail_snapshot : [];
            $snapshot = $response;
            if (isset($existingSnapshot['_last_webhook']) && ! isset($snapshot['_last_webhook'])) {
                $snapshot['_last_webhook'] = $existingSnapshot['_last_webhook'];
            }

            return $this->applyElimOrderState(
                $lockedOrder,
                $snapshot,
                $parsed['status'],
                $parsed['payment_status'],
                $parsed['data']['tracking_numbers'] ?? null
            );
        });
    }

    protected function applyElimOrderState(
        CustomerOrder $lockedOrder,
        array $snapshot,
        ?string $elimStatus,
        ?string $paymentStatus,
        mixed $trackingNumbers = null,
    ): CustomerOrder {
        $updates = [
            'elim_detail_snapshot' => $snapshot,
        ];

        if ($elimStatus !== null && $elimStatus !== '') {
            $mappedStatus = OrderStatus::mapFromElimStatus($elimStatus);

            if ($mappedStatus !== null && $this->shouldApplyElimStatusTransition($lockedOrder->status, $mappedStatus)) {
                $updates['status'] = $mappedStatus;
            }
        }

        if ($paymentStatus !== null && $paymentStatus !== '') {
            $updates['payment_status'] = $paymentStatus;

            if ($paymentStatus === CustomerOrder::PAYMENT_STATUS_PAID && $lockedOrder->paid_at === null) {
                $updates['paid_at'] = now();
            }
        }

        if (empty($lockedOrder->parcel_tracking_id) && is_array($trackingNumbers) && ! empty($trackingNumbers[0]) && is_string($trackingNumbers[0])) {
            $updates['parcel_tracking_id'] = trim($trackingNumbers[0]);
        }

        // Automatic customer wallet refund when Elim cancels a paid order or refunds payment
        if (
            ($paymentStatus === CustomerOrder::PAYMENT_STATUS_REFUNDED || $elimStatus === 'cancelled')
            && $lockedOrder->cancellation_refund_transaction_id === null
            && (
                $lockedOrder->wallet_transaction_id !== null
                || in_array($lockedOrder->payment_status, [
                    CustomerOrder::PAYMENT_STATUS_PAID,
                    CustomerOrder::PAYMENT_STATUS_REQUESTED,
                    CustomerOrder::PAYMENT_STATUS_APPROVED,
                ], true)
            )
        ) {
            $refundAmount = $lockedOrder->paymentAmountTjs();

            if ($refundAmount > 0 && $lockedOrder->user) {
                $description = __('admin.order_cancellation_refund_description', [
                    'id' => $lockedOrder->id,
                    'elim' => $lockedOrder->elim_order_id ?: '#'.$lockedOrder->id,
                    'method' => $lockedOrder->payment_method ?: 'elim_sync',
                ]);

                $refundTransaction = $this->walletService->creditOrderCancellationRefund(
                    $lockedOrder,
                    $refundAmount,
                    $description
                );

                $updates['status'] = OrderStatus::CODE_CANCELLED;
                $updates['payment_status'] = CustomerOrder::PAYMENT_STATUS_REFUNDED;
                $updates['cancellation_refund_transaction_id'] = $refundTransaction->id;
                $updates['cancelled_at'] = $lockedOrder->cancelled_at ?? now();
            }
        } elseif (
            $paymentStatus === CustomerOrder::PAYMENT_STATUS_REJECTED
            && $lockedOrder->wallet_transaction_id !== null
            && $lockedOrder->cancellation_refund_transaction_id === null
            && $lockedOrder->walletTransaction
        ) {
            $this->walletService->refundOrderPayment(
                $lockedOrder->walletTransaction,
                'Refund: Elim rejected payment request for '.($lockedOrder->elim_order_id ?: '#'.$lockedOrder->id)
            );
            $updates['wallet_transaction_id'] = null;
        }

        $lockedOrder->update($updates);

        return $lockedOrder->fresh()->load(['items', 'orderStatus']);
    }

    protected function shouldApplyElimStatusTransition(string $currentStatus, string $mappedElimStatus): bool
    {
        if ($mappedElimStatus === OrderStatus::CODE_CANCELLED) {
            return true;
        }

        $statusRank = [
            OrderStatus::CODE_PAID => 10,
            OrderStatus::CODE_RECEIVED_AT_CHINA_WAREHOUSE => 20,
            OrderStatus::CODE_PREPARING_FOR_SHIPMENT => 30,
            OrderStatus::CODE_SHIPPED_TO_TAJIKISTAN => 40,
            OrderStatus::CODE_ARRIVED_IN_TAJIKISTAN => 50,
            OrderStatus::CODE_SORTING_CENTER => 60,
            OrderStatus::CODE_SENT_TO_SELECTED_WAREHOUSE => 70,
            OrderStatus::CODE_READY_FOR_PICKUP => 80,
            OrderStatus::CODE_DELIVERED_TO_CUSTOMER => 90,
        ];

        $currentRank = $statusRank[$currentStatus] ?? 0;
        $targetRank = $statusRank[$mappedElimStatus] ?? 0;

        if ($currentRank > 0 && $targetRank > 0 && $targetRank < $currentRank) {
            return false;
        }

        return true;
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
