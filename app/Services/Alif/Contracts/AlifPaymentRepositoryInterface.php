<?php

namespace App\Services\Alif\Contracts;

use App\Models\AlifPayment;
use App\Models\WalletTransaction;
use Illuminate\Database\UniqueConstraintViolationException;

interface AlifPaymentRepositoryInterface
{
    public function findByPaymentId(string $paymentId): ?AlifPayment;

    /**
     * Re-read a payment inside a transaction with the row locked, so a
     * concurrent settlement cannot slip in between the read and the write.
     */
    public function lockByPaymentId(string $paymentId): ?AlifPayment;

    /**
     * Insert a pending payment. Throws
     * {@see UniqueConstraintViolationException} when the
     * Alif payment id is already taken, which is how duplicate pay requests
     * are serialized.
     */
    public function createPending(array $attributes): AlifPayment;

    public function markPaid(AlifPayment $payment, WalletTransaction $transaction): AlifPayment;

    public function markRejected(AlifPayment $payment, int $code): AlifPayment;
}
