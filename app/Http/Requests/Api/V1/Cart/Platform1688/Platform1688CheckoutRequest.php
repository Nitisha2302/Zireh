<?php

namespace App\Http\Requests\Api\V1\Cart\Platform1688;

use App\Http\Requests\Api\ApiRequest;

class Platform1688CheckoutRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'remark' => ['nullable', 'string', 'max:500'],
            'promotion_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
