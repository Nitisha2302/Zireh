<?php

namespace App\Http\Requests\Api\V1\Order;

use Illuminate\Foundation\Http\FormRequest;

class OrderLogisticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('package_id') && $this->input('package_id') !== null && $this->input('package_id') !== '') {
            $raw = trim((string) $this->input('package_id'));
            if (ctype_digit($raw) && (string) (int) $raw === $raw && (int) $raw >= 1) {
                $this->merge(['package_id' => (int) $raw]);
            } else {
                $this->merge(['package_id' => $raw]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'package_id' => [
                'nullable',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_int($value)) {
                        if ($value < 1) {
                            $fail('The '.$attribute.' must be at least 1.');
                        }

                        return;
                    }

                    if (! is_string($value) || trim($value) === '' || strlen($value) > 100) {
                        $fail('The '.$attribute.' must be a valid package identifier.');
                    }
                },
            ],
            'lang' => ['nullable', 'string', 'in:vi,en,ru,ko,ja'],
        ];
    }
}
