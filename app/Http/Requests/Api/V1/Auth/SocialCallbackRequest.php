<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\ApiRequest;

class SocialCallbackRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'device_name' => ['required', 'string', 'max:255'],
            'device_token' => ['nullable', 'string'],
            'preferred_language' => ['nullable', 'string', 'max:10'],
            'location_permission' => ['nullable', 'boolean'],
            'referral_code' => ['nullable', 'string', 'max:30'],
        ];
    }
}
