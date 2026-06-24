<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\ApiRequest;
use Illuminate\Validation\Rule;

class RegisterCustomerRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:30', Rule::unique('users', 'phone')],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'profile_photo' => ['nullable', 'image', 'max:5120'],
            'password' => ['nullable', 'string', 'min:6'],
            'preferred_language' => ['nullable', 'string', 'max:10'],
            'device_token' => ['nullable', 'string'],
            'location_permission' => ['nullable', 'boolean'],
            'referral_code' => ['nullable', 'string', 'max:30', 'exists:users,referral_code'],
            'otp' => ['nullable', 'string', 'digits:6'],
            'device_name' => ['required', 'string', 'max:255'],
        ];
    }
}
