<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h4 class="mb-1">Wallet Transactions</h4>
                    <p class="mb-0 text-body-secondary">All customer wallet credits, debits, and admin adjustments.</p>
                </div>
                <button type="button" class="btn btn-primary" wire:click="export">
                    <i class="icon-base ti tabler-download me-1"></i> Export CSV
                </button>
            </div>
        </div>

        <div class="card-body border-top border-bottom">
            <div class="row g-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" placeholder="Search ID, user, phone..." wire:model.live.debounce.500ms="search">
                </div>
                <div class="col-md-3">
                    <select class="form-select" wire:model.live="userFilter">
                        <option value="">All customers</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name ?: 'User #'.$customer->id }} ({{ $customer->phone }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" wire:model.live="typeFilter">
                        <option value="">All types</option>
                        <option value="credit">Credit</option>
                        <option value="debit">Debit</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" wire:model.live="sourceFilter">
                        <option value="">All sources</option>
                        @foreach ($sources as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" wire:model.live="statusFilter">
                        <option value="">All statuses</option>
                        <option value="completed">Completed</option>
                        <option value="reverted">Reverted</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" class="form-control" wire:model.live="dateFrom">
                </div>
                <div class="col-md-3">
                    <input type="date" class="form-control" wire:model.live="dateTo">
                </div>
                <div class="col-md-6 text-md-end">
                    <span class="badge bg-label-primary fs-6">Total: {{ $transactions->total() }}</span>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th>Source</th>
                        <th>Amount</th>
                        <th>Balance After</th>
                        <th>Status</th>
                        <th>Description</th>
                        <th>Admin</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td>#{{ $transaction->id }}</td>
                            <td>{{ $transaction->created_at?->format('d M Y H:i') }}</td>
                            <td>
                                <div class="fw-medium">{{ $transaction->user?->name ?: '—' }}</div>
                                <small class="text-body-secondary">{{ $transaction->user?->phone }}</small>
                            </td>
                            <td>
                                <span class="badge bg-label-{{ $transaction->type === 'credit' ? 'success' : 'danger' }}">{{ ucfirst($transaction->type) }}</span>
                            </td>
                            <td>{{ str_replace('_', ' ', $transaction->source) }}</td>
                            <td class="fw-semibold">{{ $transaction->type === 'credit' ? '+' : '-' }}¥{{ number_format((float) $transaction->amount, 2) }}</td>
                            <td>¥{{ number_format((float) $transaction->balance_after, 2) }}</td>
                            <td>{{ $transaction->status }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($transaction->description ?: '—', 40) }}</td>
                            <td>{{ $transaction->admin?->name ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-body-secondary">No transactions found.</td>
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
