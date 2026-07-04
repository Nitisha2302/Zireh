<?php

namespace App\Http\Resources\Api\V1\Cart\Taobao;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaobaoOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'platform' => $this->platform,
            'elim_order_id' => $this->elim_order_id,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'goods_subtotal_cny' => (float) $this->goods_subtotal_cny,
            'shipping_fee_cny' => (float) $this->shipping_fee_cny,
            'elim_service_fee_cny' => $this->elim_service_fee_cny !== null ? (float) $this->elim_service_fee_cny : null,
            'commission' => [
                'slab_id' => $this->commission_slab_id,
                'commission_percentage' => (float) $this->commission_percentage,
                'commission_amount' => (float) $this->commission_amount,
            ],
            'customer_total_cny' => (float) $this->customer_total_cny,
            'exchange_rate' => $this->exchange_rate !== null ? (float) $this->exchange_rate : null,
            'customer_total_tjs' => $this->customer_total_tjs !== null ? (float) $this->customer_total_tjs : null,
            'remark' => $this->remark,
            'items' => TaobaoOrderItemResource::collection($this->whenLoaded('items'))->resolve(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
