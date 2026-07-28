<?php

namespace App\Http\Resources\Api\V1\Cart\Jd;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JdCartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = collect($this->resource['items'] ?? []);

        return [
            'platform' => $this->resource['platform'] ?? 'jd',
            'items' => JdCartItemResource::collection($items)->resolve(),
            'summary' => $this->resource['summary'] ?? [],
        ];
    }
}
