<?php

namespace App\Http\Requests\Api\V1\Warehouse;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NearestWarehousesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'address_id' => [
                'required',
                'integer',
                Rule::exists('user_addresses', 'id')->where(fn ($query) => $query->where('user_id', $this->user()->id)),
            ],
        ];
    }
}
