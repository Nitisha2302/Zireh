<?php

namespace App\Http\Resources\Api\V1\Order;

trait MapsCustomerOrderDetails
{
    protected function customerOrderDetailFields(): array
    {
        $warehouse = null;

        if ($this->relationLoaded('warehouse') && $this->warehouse) {
            $warehouse = [
                'id' => $this->warehouse->id,
                'warehouse_name' => $this->warehouse->warehouse_name,
                'warehouse_code' => $this->warehouse->warehouse_code,
                'city' => $this->warehouse->city,
                'address' => $this->warehouse->address,
                'contact_person' => $this->warehouse->contact_person,
                'contact_number' => $this->warehouse->contact_number,
            ];
        } elseif (is_array($this->warehouse_snapshot)) {
            $warehouse = $this->warehouse_snapshot;
        }

        $deliveryAddress = null;

        if ($this->relationLoaded('userAddress') && $this->userAddress) {
            $deliveryAddress = [
                'id' => $this->userAddress->id,
                'full_name' => $this->userAddress->full_name,
                'phone' => $this->userAddress->phone,
                'address_line_1' => $this->userAddress->address_line_1,
                'address_line_2' => $this->userAddress->address_line_2,
                'city' => $this->userAddress->city,
                'state' => $this->userAddress->state,
                'country' => $this->userAddress->country,
                'postal_code' => $this->userAddress->postal_code,
                'latitude' => $this->userAddress->latitude,
                'longitude' => $this->userAddress->longitude,
            ];
        } elseif (is_array($this->address_snapshot)) {
            $deliveryAddress = $this->address_snapshot;
        }

        $shippingMethod = null;

        if ($this->relationLoaded('shippingMethod') && $this->shippingMethod) {
            $shippingMethod = [
                'id' => $this->shippingMethod->id,
                'name' => $this->shippingMethod->name,
                'code' => $this->shippingMethod->code,
            ];
        }

        return [
            'payment_method' => $this->payment_method,
            'is_demo_order' => (bool) $this->is_demo_order,
            'cargo_shipping_fee_tjs' => (float) ($this->cargo_shipping_fee_tjs ?? 0),
            'cargo_shipping_fee_cny' => (float) ($this->cargo_shipping_fee_cny ?? 0),
            'china_receiver_address' => $this->receiver_address,
            'warehouse' => $warehouse,
            'delivery_address' => $deliveryAddress,
            'shipping_method' => $shippingMethod,
            'pickup' => $this->when(
                $this->pickup_token !== null || $this->isReadyForPickup(),
                fn () => [
                    'payment_status' => $this->pickup_payment_status,
                    'payment_method' => $this->pickup_payment_method,
                    'shipping_fee_tjs' => $this->pickup_shipping_fee_tjs !== null ? (float) $this->pickup_shipping_fee_tjs : null,
                    'calculation_method' => $this->pickup_shipping_calculation_method,
                    'package' => [
                        'length_cm' => $this->package_length_cm !== null ? (float) $this->package_length_cm : null,
                        'width_cm' => $this->package_width_cm !== null ? (float) $this->package_width_cm : null,
                        'height_cm' => $this->package_height_cm !== null ? (float) $this->package_height_cm : null,
                        'weight_kg' => $this->package_weight_kg !== null ? (float) $this->package_weight_kg : null,
                        'volume_m3' => $this->package_volume_m3 !== null ? (float) $this->package_volume_m3 : null,
                    ],
                    'qr' => [
                        'token' => $this->pickup_token,
                        'payload' => $this->pickupQrPayload(),
                    ],
                    'paid_at' => $this->pickup_paid_at,
                ]
            ),
        ];
    }
}
