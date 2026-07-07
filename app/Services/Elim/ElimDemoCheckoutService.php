<?php

namespace App\Services\Elim;

use App\Support\Elim\ElimApiConfig;

class ElimDemoCheckoutService
{
    public function __construct(
        private readonly ElimApiConfig $elimApiConfig,
    ) {}

    public function isEnabled(): bool
    {
        return $this->elimApiConfig->demoModeEnabled();
    }

    public function preview(array $payload): array
    {
        $goodsSubtotal = $this->estimateGoodsSubtotal($payload);

        return [
            'data' => [
                'goods_amount_cny' => $goodsSubtotal,
                'shipping_fee_cny' => 10.0,
                'service_fee_cny' => round(max($goodsSubtotal * 0.03, 5), 2),
                'unavailable_items' => [],
            ],
            '_demo' => true,
        ];
    }

    public function create(array $payload, array $parsedPreview): array
    {
        $orderId = 'ORD'.str_pad((string) random_int(1, 9999999999), 10, '0', STR_PAD_LEFT);

        return [
            'status' => 'pending_payment',
            'data' => [
                'id' => $orderId,
                'status' => 'pending_payment',
                'payment_status' => 'unpaid',
                'goods_amount_cny' => $parsedPreview['goods_subtotal_cny'],
                'shipping_fee_cny' => $parsedPreview['shipping_fee_cny'],
                'service_fee_cny' => $parsedPreview['service_fee_cny'],
            ],
            '_demo' => true,
        ];
    }

    protected function estimateGoodsSubtotal(array $payload): float
    {
        $total = 0.0;

        foreach ($payload['line_items'] ?? [] as $item) {
            $price = (float) ($item['price'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 1);
            $total += $price * max($quantity, 1);
        }

        return round(max($total, 1), 2);
    }
}
