<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlatformCommissionSlabResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'min_amount' => (float) $this->min_amount,
            'max_amount' => $this->max_amount !== null ? (float) $this->max_amount : null,
            'is_unlimited' => $this->max_amount === null,
            'commission_percentage' => (float) $this->commission_percentage,
        ];
    }
}
