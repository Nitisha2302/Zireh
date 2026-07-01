<?php

namespace App\Http\Requests\Api\V1\Cart\Taobao;

use App\Http\Requests\Api\ApiRequest;

class StoreTaobaoCartItemRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'string', 'max:255'],
            'sku_id' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'selected_attributes' => ['nullable', 'array'],
            'lang' => ['nullable', 'string', 'max:10'],
        ];
    }
}
