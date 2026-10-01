<?php

namespace App\Services\Alif;

use App\Models\AlifPayment;
use App\Models\User;
use App\Services\Alif\Contracts\AlifWalletCreditorInterface;
use App\Support\Alif\Money;
use Illuminate\Support\Facades\DB;

class AlifPaymentSettler
{
    public function __construct(
        private readonly AlifWalletCreditorInterface $creditor,
    ) {}

    /**
     * Credit the wallet exactly once for a verified Alif acquiring payment.
     *
     * @param  array<string, mixed>  $payload
     */
    public function markPaid(AlifPayment $payment, array $payload): AlifPayment
    {
        return DB::transaction(function () use ($payment, $payload): AlifPayment {
            $locked = AlifPayment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->first();

            if (! $locked || $locked->isPaid()) {
                return $locked ?? $payment;
            }

            $transactionId = (string) ($payload['transactionId'] ?? $payload['transaction_id'] ?? '');
            $user = User::query()->whereKey($locked->user_id)->firstOrFail();
            $amount = Money::of($locked->amount);
            $ledger = $this->creditor->credit($user, $locked, $amount);

            $locked->update([
                'status' => AlifPayment::STATUS_PAID,
                'alif_transaction_id' => $transactionId !== '' ? $transactionId : $locked->alif_transaction_id,
                'callback_payload' => $payload,
                'paid_at' => now(),
                'wallet_transaction_id' => $ledger->id,
            ]);

            return $locked->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function markFailed(AlifPayment $payment, array $payload): AlifPayment
    {
        if ($payment->isPaid()) {
            return $payment;
        }

        $payment->update([
            'status' => AlifPayment::STATUS_FAILED,
            'callback_payload' => $payload,
        ]);

        return $payment->fresh();
    }
}
