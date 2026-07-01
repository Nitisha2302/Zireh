<?php

namespace App\Http\Resources\Api\V1\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'source' => $this->source,
            'amount' => (float) $this->amount,
            'signed_amount' => $this->signedAmount(),
            'balance_before' => (float) $this->balance_before,
            'balance_after' => (float) $this->balance_after,
            'currency' => $this->currency,
            'status' => $this->status,
            'description' => $this->description,
            'reverts_transaction_id' => $this->reverts_transaction_id,
            'reverted_by_transaction_id' => $this->reverted_by_transaction_id,
            'created_at' => $this->created_at,
        ];
    }
}
