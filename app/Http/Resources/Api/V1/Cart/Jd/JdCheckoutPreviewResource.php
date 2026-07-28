<?php

namespace App\Http\Resources\Api\V1\Cart\Jd;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JdCheckoutPreviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = collect($this->resource['items'] ?? []);

        return [
            'platform' => $this->resource['platform'] ?? 'jd',
            'cart_item_id' => $this->resource['cart_item_id'] ?? null,
            'items' => JdCartItemResource::collection($items)->resolve(),
            'checkout' => $this->resource['checkout'] ?? [],
            'demo_mode' => (bool) ($this->resource['demo_mode'] ?? false),
            'final_amount' => (float) ($this->resource['final_amount'] ?? 0),
        ];
    }
}
