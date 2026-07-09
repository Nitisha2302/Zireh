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
            'items' => Platform1688CartItemResource::collection($items)->resolve(),
            'checkout' => $this->resource['checkout'] ?? [],
            'demo_mode' => (bool) ($this->resource['demo_mode'] ?? false),
            'final_amount' => (float) ($this->resource['final_amount'] ?? 0),
        ];
    }
}
