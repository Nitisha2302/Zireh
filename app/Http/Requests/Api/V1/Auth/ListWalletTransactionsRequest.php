<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Models\WalletTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListWalletTransactionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'type' => ['sometimes', 'string', Rule::in([WalletTransaction::TYPE_CREDIT, WalletTransaction::TYPE_DEBIT])],
            'source' => ['sometimes', 'string', 'max:50'],
            'status' => ['sometimes', 'string', Rule::in([WalletTransaction::STATUS_COMPLETED, WalletTransaction::STATUS_REVERTED])],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date', 'after_or_equal:date_from'],
        ];
    }

    public function filters(): array
    {
        return array_filter([
            'type' => $this->input('type'),
            'source' => $this->input('source'),
            'status' => $this->input('status'),
            'date_from' => $this->input('date_from'),
            'date_to' => $this->input('date_to'),
        ], fn ($value) => $value !== null && $value !== '');
    }
}
