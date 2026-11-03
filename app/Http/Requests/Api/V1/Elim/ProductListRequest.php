<?php

namespace App\Http\Requests\Api\V1\Elim;

use App\Http\Requests\Api\ApiRequest;

class ProductListRequest extends ApiRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->has('q') && $this->has('keyword')) {
            $this->merge([
                'q' => $this->input('keyword'),
            ]);
        }

        if ($this->has('keyword_translate')) {
            $val = $this->input('keyword_translate');
            if ($val === 'true' || $val === 'false') {
                $this->merge(['keyword_translate' => $val === 'true']);
            }
        } elseif ($this->has('keywordTranslate')) {
            $this->merge([
                'keyword_translate' => $this->boolean('keywordTranslate'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9\-_]+$/'],
            'lang' => ['nullable', 'in:vi,en,ru,ko,ja'],
            'sort' => ['nullable', 'in:PRICE_ASC,PRICE_DESC,SALE_QTY_ASC,SALE_QTY_DESC,RETENTION_ASC,RETENTION_DESC'],
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:1', 'max:100'],
            'filter' => ['nullable', 'array'],
            'keyword_translate' => ['nullable', 'boolean'],
        ];
    }
}
