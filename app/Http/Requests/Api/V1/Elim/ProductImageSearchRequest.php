<?php

namespace App\Http\Requests\Api\V1\Elim;

use App\Http\Requests\Api\ApiRequest;

class ProductImageSearchRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'img_url' => ['required_without:img_id', 'nullable', 'url', 'max:2048'],
            'img_id' => ['required_without:img_url', 'nullable', 'string', 'max:255'],
            'lang' => ['nullable', 'in:vi,en'],
            'sort' => ['nullable', 'in:PRICE_ASC,PRICE_DESC,SALE_QTY_ASC,SALE_QTY_DESC'],
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:1', 'max:100'],
            'filter' => ['nullable', 'array'],
        ];
    }
}
