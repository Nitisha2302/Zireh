<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1">Customer Wallet</h4>
            <p class="mb-0 text-body-secondary">{{ $customer->name }} · {{ $customer->phone }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-label-secondary">Back to Customer</a>
            <a href="{{ route('admin.wallet-transactions.index', ['user' => $customer->id]) }}" class="btn btn-label-primary">All Transactions</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-body-secondary mb-1">Current Balance</p>
                    <h3 class="mb-0">сом. {{ number_format((float) $wallet->balance, 2) }} <small class="text-body-secondary fs-6">{{ $wallet->currency }}</small></h3>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header border-bottom">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item">
                            <button type="button" class="nav-link {{ $walletAction === 'add' ? 'active' : '' }}" wire:click="$set('walletAction', 'add')">
                                <i class="icon-base ti tabler-plus me-1"></i>Add Funds
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link {{ $walletAction === 'deduct' ? 'active' : '' }}" wire:click="$set('walletAction', 'deduct')">
                                <i class="icon-base ti tabler-minus me-1"></i>Deduct Balance
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    @if ($walletAction === 'add')
                        <form wire:submit="addFunds" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Amount (TJS)</label>
                                <input type="number" step="0.01" min="0.01" class="form-control @error('amount') is-invalid @enderror" wire:model="amount">
                                @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Description</label>
                                <input type="text" class="form-control @error('description') is-invalid @enderror" wire:model="description" placeholder="Optional note for this deposit">
                                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="addFunds">
                                    <i class="icon-base ti tabler-plus me-1"></i>Add Funds
                                </button>
                            </div>
                        </form>
                    @else
                        <form wire:submit="confirmDeduct" class="row g-3">
                            <div class="col-12">
                                <div class="alert alert-warning mb-0">
                                    <i class="icon-base ti tabler-alert-triangle me-1"></i>
                                    This will reduce the customer wallet balance and create a debit transaction.
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Amount (TJS)</label>
                                <input type="number" step="0.01" min="0.01" max="{{ (float) $wallet->balance }}" class="form-control @error('deductAmount') is-invalid @enderror" wire:model="deductAmount">
                                @error('deductAmount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <small class="text-body-secondary">Available: сом. {{ number_format((float) $wallet->balance, 2) }}</small>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Description</label>
                                <input type="text" class="form-control @error('deductDescription') is-invalid @enderror" wire:model="deductDescription" placeholder="Reason for deduction">
                                @error('deductDescription') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-danger" wire:loading.attr="disabled" wire:target="confirmDeduct,deductFunds" @disabled((float) $wallet->balance <= 0)>
                                    <i class="icon-base ti tabler-minus me-1"></i>Deduct Balance
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header"><h5 class="mb-0">Wallet History</h5></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Source</th>
                        <th>Amount</th>
                        <th>Balance After</th>
                        <th>Status</th>
                        <th>Description</th>
                        <th>Admin</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td>#{{ $transaction->id }}</td>
                            <td>{{ $transaction->created_at?->format('d M Y H:i') }}</td>
                            <td>
                                <span class="badge bg-label-{{ $transaction->type === 'credit' ? 'success' : 'danger' }}">{{ ucfirst($transaction->type) }}</span>
                            </td>
                            <td>{{ str_replace('_', ' ', $transaction->source) }}</td>
                            <td class="fw-semibold {{ $transaction->type === 'credit' ? 'text-success' : 'text-danger' }}">
                                {{ $transaction->type === 'credit' ? '+' : '-' }}сом. {{ number_format((float) $transaction->amount, 2) }}
                            </td>
                            <td>сом. {{ number_format((float) $transaction->balance_after, 2) }}</td>
                            <td><span class="badge bg-label-secondary">{{ $transaction->status }}</span></td>
                            <td>{{ $transaction->description ?: '—' }}</td>
                            <td>{{ $transaction->admin?->name ?: '—' }}</td>
                            <td>
                                @if ($transaction->isRevertable())
                                    <button type="button" class="btn btn-sm btn-label-warning" wire:click="confirmRevert({{ $transaction->id }})">Revert</button>
                                @elseif ($transaction->reverts_transaction_id)
                                    <small class="text-body-secondary">Revert of #{{ $transaction->reverts_transaction_id }}</small>
                                @elseif ($transaction->reverted_by_transaction_id)
                                    <small class="text-body-secondary">Reverted by #{{ $transaction->reverted_by_transaction_id }}</small>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-body-secondary">No wallet transactions yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($transactions->hasPages())
            <div class="card-footer">{{ $transactions->links('livewire::bootstrap') }}</div>
        @endif
    </div>
</div>
