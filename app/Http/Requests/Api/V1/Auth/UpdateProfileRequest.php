<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\ApiRequest;
use App\Models\Warehouse;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends ApiRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('name') && ! $this->filled('full_name')) {
            $this->merge([
                'full_name' => $this->input('name'),
            ]);
        }
    }

    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'full_name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'profile_photo' => ['nullable', 'image', 'max:5120'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'device_token' => ['sometimes', 'nullable', 'string'],
            'warehouse_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('warehouses', 'id')->where('status', Warehouse::STATUS_ACTIVE),
            ],
        ];
    }
}
