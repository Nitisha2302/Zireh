<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\ApiRequest;

class SendOtpRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'phone_number' => ['required', 'string', 'max:30'],
            'purpose' => ['required', 'string', 'in:register,login'],
        ];
    }
}
