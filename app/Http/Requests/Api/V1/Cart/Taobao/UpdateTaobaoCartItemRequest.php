<?php

namespace App\Http\Requests\Api\V1\Cart\Taobao;

use App\Http\Requests\Api\ApiRequest;

class UpdateTaobaoCartItemRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'sku_id' => ['sometimes', 'string', 'max:255'],
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'selected_attributes' => ['nullable', 'array'],
            'lang' => ['nullable', 'string', 'max:10'],
        ];
    }
}
