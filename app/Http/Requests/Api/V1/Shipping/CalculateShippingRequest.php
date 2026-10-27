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
        $mode = $this->input('mode');
        $isCm = $mode === 'cm';
        $isWeight = $mode === 'weight';
        $isBoth = $mode === 'both';
        $requiresDimensions = $isCm || $isBoth;

        return [
            'method' => ['required', 'string', 'max:50'],
            'mode' => ['nullable', 'in:weight,cm,both'],
            'weight_g' => [($isWeight || $isBoth) && ! $this->filled('weight_kg') ? 'required' : 'nullable', 'numeric', 'gt:0'],
            'weight_kg' => [! $isCm && ! $this->filled('weight_g') ? 'required' : 'nullable', 'numeric', 'min:0'],
            'length_cm' => array_values(array_filter([$requiresDimensions ? 'required' : 'nullable', 'numeric', $requiresDimensions ? 'gt:0' : 'min:0', $requiresDimensions ? null : 'required_with:width_cm,height_cm'])),
            'width_cm' => array_values(array_filter([$requiresDimensions ? 'required' : 'nullable', 'numeric', $requiresDimensions ? 'gt:0' : 'min:0', $requiresDimensions ? null : 'required_with:length_cm,height_cm'])),
            'height_cm' => array_values(array_filter([$requiresDimensions ? 'required' : 'nullable', 'numeric', $requiresDimensions ? 'gt:0' : 'min:0', $requiresDimensions ? null : 'required_with:length_cm,width_cm'])),
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
        ];
    }
}
