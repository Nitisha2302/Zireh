<?php

namespace App\Http\Resources\Api\V1\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'platform' => $this->platform,
            'product_id' => $this->product_id,
            'product' => $this->product_snapshot,
            'synced_at' => $this->synced_at,
            'created_at' => $this->created_at,
        ];
    }
}
