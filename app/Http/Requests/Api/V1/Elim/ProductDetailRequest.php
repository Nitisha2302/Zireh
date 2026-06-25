<?php

namespace App\Http\Requests\Api\V1\Elim;

use App\Http\Requests\Api\ApiRequest;

class ProductDetailRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'lang' => ['nullable', 'in:vi,en'],
        ];
    }
}
