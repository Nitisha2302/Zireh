<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h4 class="mb-1">Customers</h4>
                    <p class="mb-0 text-body-secondary">Manage customer accounts and status.</p>
                </div>
            </div>
        </div>

        <div class="card-body border-top border-bottom">
            <div class="row align-items-center g-3">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
                        <input type="text" class="form-control" placeholder="Search customers..." wire:model.live.debounce.500ms="search">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" wire:model.live="statusFilter">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 text-md-end">
                    <span class="badge bg-label-primary fs-6">Total: {{ $customers->total() }}</span>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th width="80">ID</th>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th width="140">Status</th>
                        <th width="180">Joined</th>
                        <th width="100"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr>
                            <td><span class="fw-semibold">#{{ $customer->id }}</span></td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    @if ($customer->profile_photo)
                                        <img src="{{ app(\App\Services\FileManager::class)->url($customer->profile_photo) }}" class="rounded-circle object-fit-cover border" style="width: 42px; height: 42px;" alt="{{ $customer->name }}">
                                    @else
                                        <div class="d-flex align-items-center justify-content-center rounded-circle bg-label-secondary" style="width: 42px; height: 42px;">
                                            <i class="icon-base ti tabler-user"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="fw-semibold">{{ $customer->name ?: 'Unnamed customer' }}</div>
                                        <small class="text-body-secondary">Language: {{ strtoupper($customer->preferred_language ?: 'en') }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>{{ $customer->phone ?: '—' }}</div>
                                <small class="text-body-secondary">{{ $customer->email ?: 'No email' }}</small>
                            </td>
                            <td>
                                @if ($customer->status === \App\Models\User::STATUS_ACTIVE)
                                    <span class="badge bg-label-success">Active</span>
                                @elseif ($customer->status === \App\Models\User::STATUS_BLOCKED)
                                    <span class="badge bg-label-warning">Blocked</span>
                                @else
                                    <span class="badge bg-label-danger">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div>{{ $customer->created_at?->format('d M Y') }}</div>
                                <small class="text-body-secondary">{{ $customer->created_at?->diffForHumans() }}</small>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-icon" data-bs-toggle="dropdown">
                                        <i class="icon-base ti tabler-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a href="{{ route('admin.customers.edit', $customer) }}" class="dropdown-item">
                                                <i class="icon-base ti tabler-pencil me-2"></i>Edit
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{ route('admin.customers.wallet', $customer) }}" class="dropdown-item">
                                                <i class="icon-base ti tabler-wallet me-2"></i>Manage Wallet
                                            </a>
                                        </li>
                                        <li>
                                            <button type="button" class="dropdown-item" wire:click="toggleStatus({{ $customer->id }})">
                                                <i class="icon-base ti tabler-refresh me-2"></i>
                                                {{ $customer->isActive() ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <button type="button" class="dropdown-item text-danger" wire:click="delete({{ $customer->id }})">
                                                <i class="icon-base ti tabler-trash me-2"></i>Delete
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="text-center py-5">
                                    <i class="icon-base ti tabler-users" style="font-size: 60px"></i>
                                    <h5 class="mt-3">No customers found</h5>
                                    <p class="text-body-secondary mb-0">Customers will appear here after registration.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($customers->hasPages())
            <div class="card-footer">{{ $customers->links('livewire::bootstrap') }}</div>
        @endif
    </div>
</div>
