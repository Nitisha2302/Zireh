<?php

namespace App\Livewire\Admin\Wallet;

use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin', ['title' => 'Customer Wallet'])]
class CustomerWalletPage extends Component
{
    use WithPagination;

    public User $customer;

    public string $amount = '';

    public string $description = '';

    public string $deductAmount = '';

    public string $deductDescription = '';

    public string $walletAction = 'add';

    public ?int $revertTransactionId = null;

    public bool $deductPending = false;

    public function mount(User $customer): void
    {
        $this->customer = $customer;
    }

    public function addFunds(WalletService $walletService): void
    {
        $validated = $this->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $walletService->adminAddFunds(
                $this->customer,
                (float) $validated['amount'],
                $validated['description'] ?? null,
                Auth::guard('admin')->user()
            );
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        $this->reset(['amount', 'description']);
        flash()->success('Funds added to customer wallet successfully.');
    }

    public function confirmDeduct(): void
    {
        $this->validate([
            'deductAmount' => ['required', 'numeric', 'min:0.01'],
            'deductDescription' => ['nullable', 'string', 'max:500'],
        ], [], [
            'deductAmount' => 'amount',
            'deductDescription' => 'description',
        ]);

        $this->deductPending = true;
        sweetalert()->showDenyButton()->warning('Deduct this amount from the customer wallet?');
    }

    public function deductFunds(WalletService $walletService): void
    {
        if (! $this->deductPending) {
            return;
        }

        try {
            $walletService->adminDeductFunds(
                $this->customer,
                (float) $this->deductAmount,
                $this->deductDescription !== '' ? $this->deductDescription : null,
                Auth::guard('admin')->user()
            );
        } catch (ValidationException $exception) {
            $this->deductPending = false;
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        $this->reset(['deductAmount', 'deductDescription', 'deductPending']);
        flash()->success('Amount deducted from customer wallet successfully.');
    }

    public function confirmRevert(int $transactionId): void
    {
        $this->revertTransactionId = $transactionId;
        sweetalert()->showDenyButton()->warning('Revert this deposit? The amount will be deducted from the customer wallet.');
    }

    public function revert(WalletService $walletService): void
    {
        if (! $this->revertTransactionId) {
            return;
        }

        $transaction = WalletTransaction::query()
            ->where('user_id', $this->customer->id)
            ->findOrFail($this->revertTransactionId);

        try {
            $walletService->adminRevertTransaction(
                $transaction,
                Auth::guard('admin')->user()
            );
        } catch (ValidationException $exception) {
            $this->revertTransactionId = null;
            flash()->error(collect($exception->errors())->flatten()->first() ?? 'Unable to revert transaction.');

            return;
        }

        $this->revertTransactionId = null;
        flash()->success('Transaction reverted successfully.');
    }

    #[\Livewire\Attributes\On('sweetalert:confirmed')]
    public function onRevertConfirmed(): void
    {
        if ($this->deductPending) {
            $this->deductFunds(app(WalletService::class));

            return;
        }

        $this->revert(app(WalletService::class));
    }

    #[\Livewire\Attributes\On('sweetalert:denied')]
    public function onRevertDenied(): void
    {
        $this->revertTransactionId = null;
        $this->deductPending = false;
    }

    public function render(WalletService $walletService)
    {
        $wallet = $walletService->getOrCreateWallet($this->customer);

        $transactions = WalletTransaction::query()
            ->where('user_id', $this->customer->id)
            ->with('admin')
            ->latest()
            ->paginate(15);

        return view('livewire.admin.wallet.customer-wallet-page', [
            'wallet' => $wallet,
            'transactions' => $transactions,
        ])->title('Wallet — '.$this->customer->name);
    }
}
