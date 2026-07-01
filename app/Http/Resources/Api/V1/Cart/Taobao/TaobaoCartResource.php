<?php

namespace App\Http\Resources\Api\V1\Cart\Taobao;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaobaoCartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = collect($this->resource['items'] ?? []);

        return [
            'platform' => $this->resource['platform'] ?? 'taobao',
            'items' => TaobaoCartItemResource::collection($items)->resolve(),
            'summary' => $this->resource['summary'] ?? [],
        ];
    }
}
