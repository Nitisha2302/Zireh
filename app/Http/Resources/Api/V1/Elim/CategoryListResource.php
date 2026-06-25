<?php

namespace App\Http\Resources\Api\V1\Elim;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'platform' => $this->resource['platform'] ?? null,
            'language' => $this->resource['language'] ?? null,
            'source' => $this->resource['source'] ?? null,
            'items' => $this->resource['items'] ?? [],
        ];
    }
}
