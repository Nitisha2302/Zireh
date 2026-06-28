<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\ApiRequest;
use Illuminate\Validation\Rule;

class CompleteRegistrationRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9]{8,15}$/', Rule::unique('users', 'phone')],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'profile_photo' => ['nullable', 'image', 'max:5120'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'device_token' => ['nullable', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
        ];
    }
}
