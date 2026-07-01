<?php

namespace App\Http\Requests\Api\V1\Cart\Taobao;

use App\Http\Requests\Api\ApiRequest;

class TaobaoCheckoutRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'remark' => ['nullable', 'string', 'max:500'],
            'promotion_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
