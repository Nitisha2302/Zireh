<?php

namespace App\Http\Requests\Api\V1\Cart;

use App\Http\Requests\Api\ApiRequest;
use App\Models\CustomerOrder;
use Illuminate\Validation\Rule;

abstract class CheckoutRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'shipping_method_id' => ['required', 'integer', 'exists:shipping_methods,id'],
            'payment_method' => ['required', 'string', Rule::in([
                CustomerOrder::PAYMENT_METHOD_WALLET,
                CustomerOrder::PAYMENT_METHOD_ONLINE,
            ])],
            'remark' => ['nullable', 'string', 'max:500'],
            'promotion_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
