<?php

namespace App\Http\Resources\Api\V1\Cart\Platform1688;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Platform1688CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = collect($this->resource['items'] ?? []);

        return [
            'platform' => $this->resource['platform'] ?? '1688',
            'items' => Platform1688CartItemResource::collection($items)->resolve(),
            'summary' => $this->resource['summary'] ?? [],
        ];
    }
}
