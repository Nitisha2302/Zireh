<?php

namespace App\Http\Requests\Api\V1\Order;

use App\Models\CustomerOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayPickupShippingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method' => [
                'required',
                'string',
                Rule::in([
                    CustomerOrder::PAYMENT_METHOD_WALLET,
                    CustomerOrder::PAYMENT_METHOD_ONLINE,
                ]),
            ],
        ];
    }
}
