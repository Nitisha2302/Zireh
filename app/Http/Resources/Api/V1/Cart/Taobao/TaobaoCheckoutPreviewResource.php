<?php

namespace App\Http\Resources\Api\V1\Cart\Taobao;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaobaoCheckoutPreviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = collect($this->resource['items'] ?? []);

        return [
            'platform' => $this->resource['platform'] ?? 'taobao',
            'items' => TaobaoCartItemResource::collection($items)->resolve(),
            'checkout' => $this->resource['checkout'] ?? [],
            'demo_mode' => (bool) ($this->resource['demo_mode'] ?? false),
            'final_amount' => (float) ($this->resource['final_amount'] ?? 0),
        ];
    }
}
