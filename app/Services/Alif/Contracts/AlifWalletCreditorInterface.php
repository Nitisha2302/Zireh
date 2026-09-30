<?php

namespace App\Services\Alif\Contracts;

use App\Models\AlifPayment;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Support\Alif\Money;

interface AlifWalletCreditorInterface
{
    /**
     * Credit a verified Alif top-up onto the customer's existing wallet.
     *
     * Must be called from inside a database transaction: it locks the wallet
     * row and writes the balance and the ledger entry together.
     */
    public function credit(User $user, AlifPayment $payment, Money $amount): WalletTransaction;
}
