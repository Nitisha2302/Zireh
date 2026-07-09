<?php

namespace App\Http\Resources\Api\V1\Order;

use App\Http\Resources\Api\V1\Cart\Platform1688\Platform1688OrderItemResource;
use App\Http\Resources\Api\V1\Cart\Taobao\TaobaoOrderItemResource;
use App\Models\UserCartItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerOrderResource extends JsonResource
{
    use MapsCustomerOrderDetails;

    public function toArray(Request $request): array
    {
        $itemResource = $this->platform === UserCartItem::PLATFORM_1688
            ? Platform1688OrderItemResource::class
            : TaobaoOrderItemResource::class;

        return array_merge([
            'id' => $this->id,
            'platform' => $this->platform,
            'elim_order_id' => $this->elim_order_id,
            'status' => $this->status,
            'status_label' => $this->orderStatus?->name ?? str($this->status)->replace('_', ' ')->title(),
            'status_color' => $this->orderStatus?->color,
            'payment_status' => $this->payment_status,
            'paid_at' => $this->paid_at,
            'final_amount' => $this->paymentAmountTjs(),
            'remark' => $this->remark,
            'is_cancellable' => $this->isCancellable(),
            'elim_detail' => $this->when(
                $this->elim_detail_snapshot !== null,
                fn () => $this->elim_detail_snapshot['data'] ?? $this->elim_detail_snapshot
            ),
            'items' => $itemResource::collection($this->whenLoaded('items'))->resolve(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ], $this->customerOrderDetailFields());
    }
}
