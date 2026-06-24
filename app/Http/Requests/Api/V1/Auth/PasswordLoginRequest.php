<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\ApiRequest;

class PasswordLoginRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
            'device_token' => ['nullable', 'string'],
        ];
    }
}
