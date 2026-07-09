<?php

namespace App\Http\Resources\Api\V1\Cart\Taobao;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaobaoOrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'marketplace_id' => $this->marketplace_id,
            'sku_id' => $this->sku_id,
            'quantity' => $this->quantity,
            'final_amount' => round((float) ($this->final_amount_tjs ?? 0), 2),
            'product' => $this->product_snapshot,
            'selected_attributes' => $this->selected_attributes,
        ];
    }
}
