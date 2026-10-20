<?php

namespace App\Services\DcNext;

use App\Models\DcNextPayment;
use App\Models\User;
use App\Models\UserWallet;
use App\Models\WalletTransaction;
use App\Services\DcNext\Contracts\DcNextWalletCreditorInterface;
use App\Services\Wallet\WalletService;
use App\Support\Alif\Money;

/**
 * The only place a DC NEXT payment touches a balance.
 *
 * Writes into the project's existing wallet tables rather than a parallel
 * ledger, and does its arithmetic on decimal strings so a top-up can never
 * drift by a fraction of a diram.
 */
class DcNextWalletCreditor implements DcNextWalletCreditorInterface
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    public function credit(User $user, DcNextPayment $payment, Money $amount): WalletTransaction
    {
        $wallet = $this->lockedWallet($user);

        $balanceBefore = Money::of($wallet->balance);
        $balanceAfter = $balanceBefore->plus($amount);

        $wallet->update(['balance' => $balanceAfter->value()]);

        return WalletTransaction::query()->create([
            'user_id' => $user->id,
            'type' => WalletTransaction::TYPE_CREDIT,
            'source' => WalletTransaction::SOURCE_DC_NEXT_DEPOSIT,
            'amount' => $amount->value(),
            'balance_before' => $balanceBefore->value(),
            'balance_after' => $balanceAfter->value(),
            'currency' => $wallet->currency,
            'status' => WalletTransaction::STATUS_COMPLETED,
            'description' => 'DC NEXT top-up | Order: '.$payment->order_id,
            'reference_type' => DcNextPayment::class,
            'reference_id' => $payment->id,
        ]);
    }

    protected function lockedWallet(User $user): UserWallet
    {
        $wallet = UserWallet::query()
            ->where('user_id', $user->id)
            ->lockForUpdate()
            ->first();

        if ($wallet) {
            return $wallet;
        }

        $created = $this->walletService->getOrCreateWallet($user);

        return UserWallet::query()->whereKey($created->id)->lockForUpdate()->firstOrFail();
    }
}
