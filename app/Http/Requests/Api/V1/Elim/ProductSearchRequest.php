<?php

namespace App\Http\Requests\Api\V1\Elim;

class ProductSearchRequest extends ProductListRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'q' => ['required', 'string', 'max:255'],
        ];
    }
}
