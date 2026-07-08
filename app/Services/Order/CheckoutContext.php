<?php

namespace App\Services\Order;

use App\Models\ShippingMethod;
use App\Models\UserAddress;
use App\Models\Warehouse;

class CheckoutContext
{
    public function __construct(
        public readonly Warehouse $warehouse,
        public readonly ?UserAddress $address,
        public readonly ShippingMethod $shippingMethod,
        public readonly string $paymentMethod,
    ) {}

    public function warehouseSnapshot(): array
    {
        return [
            'id' => $this->warehouse->id,
            'warehouse_name' => $this->warehouse->warehouse_name,
            'warehouse_code' => $this->warehouse->warehouse_code,
            'contact_person' => $this->warehouse->contact_person,
            'contact_number' => $this->warehouse->contact_number,
            'email' => $this->warehouse->email,
            'country' => $this->warehouse->country,
            'state' => $this->warehouse->state,
            'city' => $this->warehouse->city,
            'address' => $this->warehouse->address,
            'postal_code' => $this->warehouse->postal_code,
            'latitude' => $this->warehouse->latitude,
            'longitude' => $this->warehouse->longitude,
        ];
    }

    public function addressSnapshot(): ?array
    {
        if ($this->address === null) {
            return null;
        }

        return [
            'id' => $this->address->id,
            'full_name' => $this->address->full_name,
            'phone' => $this->address->phone,
            'alternate_phone' => $this->address->alternate_phone,
            'address_line_1' => $this->address->address_line_1,
            'address_line_2' => $this->address->address_line_2,
            'landmark' => $this->address->landmark,
            'city' => $this->address->city,
            'state' => $this->address->state,
            'country' => $this->address->country,
            'postal_code' => $this->address->postal_code,
            'address_type' => $this->address->address_type,
            'latitude' => $this->address->latitude,
            'longitude' => $this->address->longitude,
        ];
    }

    public function toPreviewArray(): array
    {
        return [
            'warehouse' => $this->warehouseSnapshot(),
            'delivery_address' => $this->address ? $this->addressSnapshot() : null,
            'shipping_method' => [
                'id' => $this->shippingMethod->id,
                'name' => $this->shippingMethod->name,
                'code' => $this->shippingMethod->code,
            ],
            'cargo_shipping' => null,
            'payment_method' => $this->paymentMethod,
            'cargo_shipping_fee_tjs' => 0,
            'cargo_shipping_fee_cny' => 0,
            'pickup_shipping_note' => __('api.pickup_shipping_charged_at_warehouse'),
        ];
    }
}
