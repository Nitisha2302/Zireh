<?php

namespace App\Http\Resources\Api\V1\Cart\Taobao;

use App\Support\Currency\CurrencyPriceConverter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaobaoCartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return app(CurrencyPriceConverter::class)->applyToCartItem([
            'id' => $this->id,
            'product_id' => $this->product_id,
            'marketplace_id' => $this->marketplace_id,
            'sku_id' => $this->sku_id,
            'quantity' => $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'line_subtotal' => $this->lineSubtotal(),
            'product' => $this->product_snapshot,
            'selected_attributes' => $this->selected_attributes,
            'synced_at' => $this->synced_at,
            'created_at' => $this->created_at,
        ]);
    }
}
