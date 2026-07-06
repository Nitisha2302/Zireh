<?php

namespace App\Http\Requests\Api\V1\Shipping;

use Illuminate\Foundation\Http\FormRequest;

class CalculateShippingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'method' => ['required', 'string', 'max:50'],
            'weight_kg' => ['required', 'numeric', 'min:0'],
            'length_cm' => ['nullable', 'numeric', 'min:0', 'required_with:width_cm,height_cm'],
            'width_cm' => ['nullable', 'numeric', 'min:0', 'required_with:length_cm,height_cm'],
            'height_cm' => ['nullable', 'numeric', 'min:0', 'required_with:length_cm,width_cm'],
        ];
    }
}
