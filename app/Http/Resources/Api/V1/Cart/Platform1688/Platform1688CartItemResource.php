<?php

namespace App\Http\Resources\Api\V1\Cart\Platform1688;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Platform1688CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'marketplace_id' => $this->marketplace_id,
            'sku_id' => $this->sku_id,
            'quantity' => $this->quantity,
            'final_amount' => (float) $this->final_amount_tjs,
            'product' => $this->product_snapshot,
            'selected_attributes' => $this->selected_attributes,
            'synced_at' => $this->synced_at,
            'created_at' => $this->created_at,
        ];
    }
}
