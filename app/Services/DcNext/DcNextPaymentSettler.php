<?php

namespace App\Services\DcNext;

use App\Models\DcNextPayment;
use App\Models\User;
use App\Services\DcNext\Contracts\DcNextWalletCreditorInterface;
use App\Support\Alif\Money;
use Illuminate\Support\Facades\DB;

class DcNextPaymentSettler
{
    public function __construct(
        private readonly DcNextWalletCreditorInterface $creditor,
    ) {}

    /**
     * Credit the wallet exactly once for a verified DC NEXT acquiring payment.
     *
     * @param  array<string, mixed>  $payload
     */
    public function markPaid(DcNextPayment $payment, array $payload): DcNextPayment
    {
        return DB::transaction(function () use ($payment, $payload): DcNextPayment {
            $locked = DcNextPayment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->first();

            if (! $locked || $locked->isPaid()) {
                return $locked ?? $payment;
            }

            $invoiceId = (string) ($payload['invoice_id'] ?? '');
            $payid = (string) ($payload['payid'] ?? $payload['transactionId'] ?? $payload['transaction_id'] ?? '');

            $user = User::query()->whereKey($locked->user_id)->firstOrFail();
            $amount = Money::of($locked->amount_tjs);
            $ledger = $this->creditor->credit($user, $locked, $amount);

            $locked->update([
                'status' => DcNextPayment::STATUS_PAID,
                'invoice_id' => $invoiceId !== '' ? $invoiceId : $locked->invoice_id,
                'payid' => $payid !== '' ? $payid : $locked->payid,
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
    public function markFailed(DcNextPayment $payment, array $payload): DcNextPayment
    {
        if ($payment->isPaid()) {
            return $payment;
        }

        $payment->update([
            'status' => DcNextPayment::STATUS_FAILED,
            'callback_payload' => $payload,
        ]);

        return $payment->fresh();
    }
}
