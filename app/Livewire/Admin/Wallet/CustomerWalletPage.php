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

    public ?int $revertTransactionId = null;

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
        $this->revert(app(WalletService::class));
    }

    #[\Livewire\Attributes\On('sweetalert:denied')]
    public function onRevertDenied(): void
    {
        $this->revertTransactionId = null;
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
