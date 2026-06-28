<?php

namespace App\Http\Requests\Api\V1\Auth;

class UpdateUserAddressRequest extends StoreUserAddressRequest
{
    public function rules(): array
    {
        return $this->addressRules(partial: true);
    }
}
