<?php

namespace App\Http\Requests\Api\V1\Elim;

use App\Http\Requests\Api\ApiRequest;

class ProductListRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'lang' => ['nullable', 'in:vi,en'],
            'sort' => ['nullable', 'in:PRICE_ASC,PRICE_DESC,SALE_QTY_ASC,SALE_QTY_DESC,RETENTION_ASC,RETENTION_DESC'],
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:1', 'max:100'],
            'filter' => ['nullable', 'array'],
        ];
    }
}
