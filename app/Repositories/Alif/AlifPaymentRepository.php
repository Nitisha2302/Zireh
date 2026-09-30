<?php

namespace App\Repositories\Alif;

use App\Models\AlifPayment;
use App\Models\WalletTransaction;
use App\Services\Alif\Contracts\AlifPaymentRepositoryInterface;
use App\Support\Alif\AlifResponseCode;

class AlifPaymentRepository implements AlifPaymentRepositoryInterface
{
    public function findByPaymentId(string $paymentId): ?AlifPayment
    {
        return AlifPayment::query()->where('payment_id', $paymentId)->first();
    }

    public function lockByPaymentId(string $paymentId): ?AlifPayment
    {
        return AlifPayment::query()
            ->where('payment_id', $paymentId)
            ->lockForUpdate()
            ->first();
    }

    public function createPending(array $attributes): AlifPayment
    {
        return AlifPayment::query()->create([
            ...$attributes,
            'status' => AlifPayment::STATUS_PENDING,
        ]);
    }

    public function markPaid(AlifPayment $payment, WalletTransaction $transaction): AlifPayment
    {
        $payment->update([
            'status' => AlifPayment::STATUS_PAID,
            'code' => AlifResponseCode::SUCCESS->value,
            'response_id' => (string) $transaction->id,
            'wallet_transaction_id' => $transaction->id,
            'paid_at' => now(),
        ]);

        return $payment;
    }

    public function markRejected(AlifPayment $payment, int $code): AlifPayment
    {
        $payment->update([
            'status' => AlifPayment::STATUS_REJECTED,
            'code' => $code,
        ]);

        return $payment;
    }
}
