<?php

namespace App\Http\Resources\Api\V1\Cart\Platform1688;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Platform1688CheckoutPreviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = collect($this->resource['items'] ?? []);

        return [
            'platform' => $this->resource['platform'] ?? '1688',
            'cart_item_id' => $this->resource['cart_item_id'] ?? null,
            'items' => Platform1688CartItemResource::collection($items)->resolve(),
            'checkout' => $this->resource['checkout'] ?? [],
            'demo_mode' => (bool) ($this->resource['demo_mode'] ?? false),
            'final_amount' => (float) ($this->resource['final_amount'] ?? 0),
            'preview_token' => $this->resource['preview_token'] ?? null,
            'goods_subtotal_cny' => (float) ($this->resource['goods_subtotal_cny'] ?? 0),
            'shipping_fee_cny' => (float) ($this->resource['shipping_fee_cny'] ?? 0),
        ];
    }
}
