<?php

namespace App\Http\Requests\Api\V1\Cart\Platform1688;

use App\Http\Requests\Api\ApiRequest;

class UpdatePlatform1688CartItemRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'sku_id' => ['sometimes', 'string', 'max:255'],
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'final_amount' => ['required', 'numeric', 'min:0.01'],
            'selected_attributes' => ['nullable', 'array'],
            'lang' => ['nullable', 'string', 'max:10'],
        ];
    }
}
