<?php

namespace App\Http\Resources\Api\V1\Elim;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'platform' => $this->resource['platform'] ?? null,
            'pagination' => $this->resource['pagination'] ?? [],
            'items' => $this->resource['items'] ?? [],
            'currency' => $this->resource['currency'] ?? null,
        ];
    }
}
