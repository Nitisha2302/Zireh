<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\ApiRequest;
use App\Models\UserAddress;
use Illuminate\Validation\Rule;

class StoreUserAddressRequest extends ApiRequest
{
    public function rules(): array
    {
        return $this->addressRules();
    }

    protected function addressRules(bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return [
            'full_name' => [$required, 'string', 'max:255'],
            'phone' => [$required, 'string', 'max:30', 'regex:/^\+?[0-9]{8,15}$/'],
            'alternate_phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9]{8,15}$/'],
            'address_line_1' => [$required, 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'city' => [$required, 'string', 'max:120'],
            'state' => [$required, 'string', 'max:120'],
            'country' => [$required, 'string', 'max:120'],
            'postal_code' => [$required, 'string', 'max:20'],
            'address_type' => [$required, 'string', Rule::in([
                UserAddress::TYPE_HOME,
                UserAddress::TYPE_WORK,
                UserAddress::TYPE_OTHER,
            ])],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
