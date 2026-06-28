<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\ApiRequest;

class VerifyLoginOtpRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'phone_number' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9]{8,15}$/'],
            'otp' => ['required', 'string', 'digits:6'],
            'device_name' => ['required', 'string', 'max:255'],
            'device_token' => ['nullable', 'string'],
        ];
    }
}
