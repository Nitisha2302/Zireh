<?php

namespace App\Services\Wallet;

use App\Models\Admin;
use App\Models\User;
use App\Models\UserWallet;
use App\Models\WalletTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WalletService
{
    public function getOrCreateWallet(User $user): UserWallet
    {
        return UserWallet::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0, 'currency' => UserWallet::CURRENCY_CNY]
        );
    }

    public function getBalance(User $user): float
    {
        return (float) $this->getOrCreateWallet($user)->balance;
    }

    public function adminAddFunds(User $user, float $amount, ?string $description, Admin $admin): WalletTransaction
    {
        $this->assertPositiveAmount($amount);

        return DB::transaction(function () use ($user, $amount, $description, $admin): WalletTransaction {
            $wallet = UserWallet::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $wallet) {
                $wallet = $this->getOrCreateWallet($user);
                $wallet = UserWallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();
            }

            $balanceBefore = (float) $wallet->balance;
            $balanceAfter = round($balanceBefore + $amount, 2);

            $wallet->update(['balance' => $balanceAfter]);

            return WalletTransaction::query()->create([
                'user_id' => $user->id,
                'admin_id' => $admin->id,
                'type' => WalletTransaction::TYPE_CREDIT,
                'source' => WalletTransaction::SOURCE_ADMIN_DEPOSIT,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'currency' => $wallet->currency,
                'status' => WalletTransaction::STATUS_COMPLETED,
                'description' => $description ?: 'Admin wallet deposit',
            ]);
        });
    }

    public function adminRevertTransaction(WalletTransaction $transaction, Admin $admin, ?string $description = null): WalletTransaction
    {
        if (! $transaction->isRevertable()) {
            throw ValidationException::withMessages([
                'transaction' => ['This transaction cannot be reverted.'],
            ]);
        }

        return DB::transaction(function () use ($transaction, $admin, $description): WalletTransaction {
            $transaction = WalletTransaction::query()->whereKey($transaction->id)->lockForUpdate()->firstOrFail();

            if (! $transaction->isRevertable()) {
                throw ValidationException::withMessages([
                    'transaction' => ['This transaction has already been reverted.'],
                ]);
            }

            $wallet = UserWallet::query()
                ->where('user_id', $transaction->user_id)
                ->lockForUpdate()
                ->firstOrFail();

            $amount = (float) $transaction->amount;
            $balanceBefore = (float) $wallet->balance;

            if ($balanceBefore < $amount) {
                throw ValidationException::withMessages([
                    'balance' => ['Insufficient wallet balance to revert this deposit.'],
                ]);
            }

            $balanceAfter = round($balanceBefore - $amount, 2);
            $wallet->update(['balance' => $balanceAfter]);

            $revert = WalletTransaction::query()->create([
                'user_id' => $transaction->user_id,
                'admin_id' => $admin->id,
                'type' => WalletTransaction::TYPE_DEBIT,
                'source' => WalletTransaction::SOURCE_ADMIN_REVERT,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'currency' => $wallet->currency,
                'status' => WalletTransaction::STATUS_COMPLETED,
                'description' => $description ?: 'Revert admin deposit #'.$transaction->id,
                'reverts_transaction_id' => $transaction->id,
            ]);

            $transaction->update([
                'status' => WalletTransaction::STATUS_REVERTED,
                'reverted_by_transaction_id' => $revert->id,
            ]);

            return $revert;
        });
    }

    public function listForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->transactionQuery(['user_id' => $user->id])
            ->paginate($perPage);
    }

    public function listForAdmin(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->applyFilters($this->transactionQuery(), $filters)
            ->paginate($perPage);
    }

    public function exportCsv(array $filters = []): StreamedResponse
    {
        $filename = 'wallet-transactions-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($filters): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Date',
                'User ID',
                'User Name',
                'Phone',
                'Type',
                'Source',
                'Amount',
                'Balance Before',
                'Balance After',
                'Currency',
                'Status',
                'Description',
                'Admin ID',
                'Reverts Transaction ID',
            ]);

            $this->applyFilters($this->transactionQuery(), $filters)
                ->orderByDesc('id')
                ->chunk(200, function ($transactions) use ($handle): void {
                    foreach ($transactions as $transaction) {
                        fputcsv($handle, [
                            $transaction->id,
                            $transaction->created_at?->toDateTimeString(),
                            $transaction->user_id,
                            $transaction->user?->name,
                            $transaction->user?->phone,
                            $transaction->type,
                            $transaction->source,
                            $transaction->amount,
                            $transaction->balance_before,
                            $transaction->balance_after,
                            $transaction->currency,
                            $transaction->status,
                            $transaction->description,
                            $transaction->admin_id,
                            $transaction->reverts_transaction_id,
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    protected function transactionQuery(array $defaults = []): Builder
    {
        return WalletTransaction::query()
            ->with(['user', 'admin'])
            ->when(isset($defaults['user_id']), fn (Builder $query) => $query->where('user_id', $defaults['user_id']))
            ->latest();
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when(! empty($filters['user_id']), fn (Builder $q) => $q->where('user_id', $filters['user_id']))
            ->when(! empty($filters['search']), function (Builder $q) use ($filters) {
                $search = $filters['search'];
                $q->where(function (Builder $inner) use ($search) {
                    $inner->where('id', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%')
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('phone', 'like', '%'.$search.'%'));
                });
            })
            ->when(! empty($filters['type']), fn (Builder $q) => $q->where('type', $filters['type']))
            ->when(! empty($filters['source']), fn (Builder $q) => $q->where('source', $filters['source']))
            ->when(! empty($filters['status']), fn (Builder $q) => $q->where('status', $filters['status']))
            ->when(! empty($filters['date_from']), fn (Builder $q) => $q->whereDate('created_at', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn (Builder $q) => $q->whereDate('created_at', '<=', $filters['date_to']));
    }

    protected function assertPositiveAmount(float $amount): void
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Amount must be greater than zero.'],
            ]);
        }
    }
}
