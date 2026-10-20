<?php

namespace App\Services\DcNext\Contracts;

use App\Models\DcNextPayment;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Support\Alif\Money;

interface DcNextWalletCreditorInterface
{
    /**
     * Credit a verified DC NEXT top-up onto the customer's existing wallet.
     *
     * Must be called from inside a database transaction: it locks the wallet
     * row and writes the balance and the ledger entry together.
     */
    public function credit(User $user, DcNextPayment $payment, Money $amount): WalletTransaction;
}
