<?php

namespace App\Http\Requests\Api\V1\Elim;

class ProductSearchRequest extends ProductListRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'q' => ['required_without:category_id', 'nullable', 'string', 'max:255'],
            'category_id' => ['required_without:q', 'nullable', 'string', 'max:255', 'regex:/^[a-z0-9\-_]+$/'],
        ];
    }
}
