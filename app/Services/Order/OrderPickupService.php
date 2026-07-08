<?php

namespace App\Services\Order;

use App\Models\Admin;
use App\Models\CustomerOrder;
use App\Models\OrderStatus;
use App\Models\ShippingMethod;
use App\Services\Shipping\ShippingRateService;
use App\Services\Warehouse\WarehousePanelService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderPickupService
{
    public function __construct(
        private readonly ShippingRateService $shippingRateService,
        private readonly WarehousePanelService $warehousePanelService,
    ) {}

    public function previewShipping(CustomerOrder $order, array $measurements): array
    {
        $this->assertCanMeasure($order);
        $method = $this->resolveShippingMethod($order);

        return $this->shippingRateService->calculatePickupShipping(
            $method,
            (float) $measurements['package_weight_kg'],
            (float) $measurements['package_length_cm'],
            (float) $measurements['package_width_cm'],
            (float) $measurements['package_height_cm'],
        );
    }

    public function confirmPickup(CustomerOrder $order, Admin $admin, array $measurements): CustomerOrder
    {
        $this->warehousePanelService->ensureTajikistanOrderAccessible($admin, $order);
        $this->assertCanMeasure($order);

        $calculation = $this->previewShipping($order, $measurements);
        $package = $calculation['package'];

        $order->update([
            'package_length_cm' => $package['length_cm'],
            'package_width_cm' => $package['width_cm'],
            'package_height_cm' => $package['height_cm'],
            'package_weight_kg' => $package['weight_kg'],
            'package_volume_m3' => $package['volume_m3'],
            'pickup_shipping_fee_tjs' => $calculation['shipping_cost'],
            'pickup_shipping_weight_fee_tjs' => $calculation['weight_cost_tjs'],
            'pickup_shipping_volume_fee_tjs' => $calculation['volume_cost_tjs'],
            'pickup_shipping_calculation_method' => $calculation['applied_method'],
            'pickup_shipping_snapshot' => $calculation,
            'cargo_shipping_fee_tjs' => $calculation['shipping_cost'],
            'pickup_payment_status' => CustomerOrder::PICKUP_PAYMENT_STATUS_PENDING,
            'pickup_token' => (string) Str::uuid(),
            'pickup_confirmed_at' => now(),
            'pickup_confirmed_by' => $admin->id,
            'status' => OrderStatus::CODE_READY_FOR_PICKUP,
        ]);

        return $order->fresh(['user', 'items', 'orderStatus', 'warehouse', 'shippingMethod']);
    }

    public function markPaymentReceived(CustomerOrder $order, Admin $admin): CustomerOrder
    {
        $this->warehousePanelService->ensureTajikistanOrderAccessible($admin, $order);
        $this->assertReadyForPickup($order);

        if ($order->isPickupShippingPaid()) {
            throw ValidationException::withMessages([
                'pickup_payment' => [__('api.pickup_shipping_already_paid')],
            ]);
        }

        $order->update([
            'pickup_payment_status' => CustomerOrder::PICKUP_PAYMENT_STATUS_PAID,
            'pickup_paid_at' => now(),
        ]);

        return $order->fresh(['user', 'items', 'orderStatus', 'warehouse', 'shippingMethod']);
    }

    public function deliver(CustomerOrder $order, Admin $admin): CustomerOrder
    {
        $this->warehousePanelService->ensureTajikistanOrderAccessible($admin, $order);
        $this->assertReadyForPickup($order);

        if (! $order->isPickupShippingPaid()) {
            throw ValidationException::withMessages([
                'pickup_payment' => [__('api.pickup_shipping_unpaid_cannot_deliver')],
            ]);
        }

        $order->update([
            'status' => OrderStatus::CODE_DELIVERED_TO_CUSTOMER,
        ]);

        return $order->fresh(['user', 'items', 'orderStatus', 'warehouse', 'shippingMethod']);
    }

    public function findByPickupToken(string $token, Admin $admin): CustomerOrder
    {
        $order = CustomerOrder::query()
            ->where('pickup_token', $token)
            ->first();

        if (! $order) {
            throw ValidationException::withMessages([
                'pickup_token' => [__('api.pickup_token_invalid')],
            ]);
        }

        $this->warehousePanelService->ensureTajikistanOrderAccessible($admin, $order);

        return $order->load(['user', 'items', 'orderStatus', 'warehouse', 'shippingMethod']);
    }

    public function pickupDetailsForCustomer(CustomerOrder $order): array
    {
        if (! $order->isReadyForPickup() && $order->status !== OrderStatus::CODE_DELIVERED_TO_CUSTOMER) {
            throw ValidationException::withMessages([
                'pickup' => [__('api.pickup_not_available')],
            ]);
        }

        return [
            'order_id' => $order->id,
            'status' => $order->status,
            'payment_status' => $order->pickup_payment_status,
            'shipping_fee_tjs' => $order->pickupPaymentAmountTjs(),
            'calculation_method' => $order->pickup_shipping_calculation_method,
            'package' => [
                'length_cm' => $order->package_length_cm !== null ? (float) $order->package_length_cm : null,
                'width_cm' => $order->package_width_cm !== null ? (float) $order->package_width_cm : null,
                'height_cm' => $order->package_height_cm !== null ? (float) $order->package_height_cm : null,
                'weight_kg' => $order->package_weight_kg !== null ? (float) $order->package_weight_kg : null,
                'volume_m3' => $order->package_volume_m3 !== null ? (float) $order->package_volume_m3 : null,
            ],
            'qr' => [
                'token' => $order->pickup_token,
                'payload' => $order->pickupQrPayload(),
            ],
        ];
    }

    protected function assertCanMeasure(CustomerOrder $order): void
    {
        if ($order->isReadyForPickup()) {
            throw ValidationException::withMessages([
                'order' => [__('api.pickup_already_confirmed')],
            ]);
        }

        if (! in_array($order->status, CustomerOrder::PRE_PICKUP_STATUSES, true)) {
            throw ValidationException::withMessages([
                'order' => [__('api.pickup_measurement_not_allowed')],
            ]);
        }

        if ($order->shipping_method_id === null) {
            throw ValidationException::withMessages([
                'shipping_method' => [__('api.shipping_method_not_found')],
            ]);
        }
    }

    protected function assertReadyForPickup(CustomerOrder $order): void
    {
        if (! $order->isReadyForPickup()) {
            throw ValidationException::withMessages([
                'order' => [__('api.pickup_not_ready')],
            ]);
        }
    }

    protected function resolveShippingMethod(CustomerOrder $order): ShippingMethod
    {
        $method = $order->shippingMethod;

        if (! $method || ! $method->is_active) {
            throw ValidationException::withMessages([
                'shipping_method' => [__('api.shipping_method_not_found')],
            ]);
        }

        return $method;
    }
}
