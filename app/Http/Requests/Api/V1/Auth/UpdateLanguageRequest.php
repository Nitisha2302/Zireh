<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\ApiRequest;

class UpdateLanguageRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'language' => ['required', 'in:en,ru,tg'],
        ];
    }
}
