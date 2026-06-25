<?php

namespace App\Http\Requests\Api\V1\Elim;

use App\Http\Requests\Api\ApiRequest;

class ProductImageUploadRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'image', 'max:5120'],
        ];
    }
}
