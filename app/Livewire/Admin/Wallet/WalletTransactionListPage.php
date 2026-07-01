<?php

namespace App\Livewire\Admin\Wallet;

use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Wallet\WalletService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts::admin', ['title' => 'Wallet Transactions'])]
class WalletTransactionListPage extends Component
{
    use WithPagination;

    public string $search = '';

    public string $userFilter = '';

    public string $typeFilter = '';

    public string $sourceFilter = '';

    public string $statusFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public function mount(): void
    {
        $userId = request()->query('user');

        if ($userId) {
            $this->userFilter = (string) $userId;
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingUserFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSourceFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function export(WalletService $walletService): StreamedResponse
    {
        return $walletService->exportCsv($this->filters());
    }

    public function render(WalletService $walletService)
    {
        $transactions = $walletService->listForAdmin($this->filters(), 15);
        $customers = User::query()->orderBy('name')->get(['id', 'name', 'phone']);

        return view('livewire.admin.wallet.wallet-transaction-list-page', [
            'transactions' => $transactions,
            'customers' => $customers,
            'sources' => [
                WalletTransaction::SOURCE_ADMIN_DEPOSIT => 'Admin Deposit',
                WalletTransaction::SOURCE_ADMIN_REVERT => 'Admin Revert',
                WalletTransaction::SOURCE_ORDER_PAYMENT => 'Order Payment',
                WalletTransaction::SOURCE_ORDER_REFUND => 'Order Refund',
            ],
        ])->title('Wallet Transactions');
    }

    protected function filters(): array
    {
        return array_filter([
            'search' => $this->search,
            'user_id' => $this->userFilter !== '' ? (int) $this->userFilter : null,
            'type' => $this->typeFilter,
            'source' => $this->sourceFilter,
            'status' => $this->statusFilter,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ], fn ($value) => $value !== null && $value !== '');
    }
}
