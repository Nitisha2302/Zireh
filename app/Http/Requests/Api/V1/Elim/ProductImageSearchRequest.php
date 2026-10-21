<?php

namespace App\Http\Requests\Api\V1\Elim;

use App\Http\Requests\Api\ApiRequest;

class ProductImageSearchRequest extends ApiRequest
{
    protected function prepareForValidation(): void
    {
        $merged = [];

        if (! $this->filled('img_url') && $this->filled('imgUrl')) {
            $merged['img_url'] = $this->input('imgUrl');
        }

        if (! $this->filled('img_id')) {
            if ($this->filled('imgId')) {
                $merged['img_id'] = $this->input('imgId');
            } elseif ($this->filled('imageId')) {
                $merged['img_id'] = $this->input('imageId');
            } elseif ($this->filled('image_id')) {
                $merged['img_id'] = $this->input('image_id');
            }
        }

        if (! $this->filled('keyword') && $this->filled('q')) {
            $merged['keyword'] = $this->input('q');
        }

        if (! $this->has('keyword_translate') && $this->has('keywordTranslate')) {
            $merged['keyword_translate'] = $this->boolean('keywordTranslate');
        }

        if ($merged !== []) {
            $this->merge($merged);
        }
    }

    public function rules(): array
    {
        return [
            'img_url' => [
                'required_without_all:img_id,file',
                'nullable',
                'url',
                'max:2048',
            ],
            'img_id' => ['required_without_all:img_url,file', 'nullable', 'string', 'max:255'],
            'file' => ['required_without_all:img_url,img_id', 'nullable', 'image', 'max:5120'],
            'lang' => ['nullable', 'in:vi,en,ru,ko,ja'],
            'sort' => ['nullable', 'in:PRICE_ASC,PRICE_DESC,SALE_QTY_ASC,SALE_QTY_DESC'],
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:1', 'max:20'],
            'filter' => ['nullable', 'array'],
            'keyword' => ['nullable', 'string', 'max:255'],
            'keyword_translate' => ['nullable', 'boolean'],
        ];
    }
}
