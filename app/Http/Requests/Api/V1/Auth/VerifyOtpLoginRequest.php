<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\ApiRequest;

class VerifyOtpLoginRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'phone_number' => ['required', 'string', 'max:30'],
            'otp' => ['required', 'string', 'digits:6'],
            'purpose' => ['required', 'string', 'in:register,login'],
            'device_name' => ['required', 'string', 'max:255'],
            'device_token' => ['nullable', 'string'],
        ];
    }
}
