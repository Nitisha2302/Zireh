<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h4 class="mb-1">Customer Orders</h4>
                    <p class="mb-0 text-body-secondary">View orders with product prices, commission, and Elim status.</p>
                </div>
            </div>
        </div>

        <div class="card-body border-top border-bottom">
            <div class="row align-items-center g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
                        <input type="text" class="form-control" placeholder="Search order ID, customer..." wire:model.live.debounce.500ms="search">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" wire:model.live="platformFilter">
                        <option value="">All platforms</option>
                        <option value="taobao">Taobao</option>
                        <option value="1688">1688</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" wire:model.live="statusFilter">
                        <option value="">{{ __('admin.all_statuses') }}</option>
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status->code }}">{{ $status->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 text-md-end">
                    <span class="badge bg-label-primary fs-6">Total: {{ $orders->total() }}</span>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th width="80">ID</th>
                        <th>Elim Order</th>
                        <th>Customer</th>
                        <th>Platform</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>{{ __('admin.final_amount') }} (TJS)</th>
                        <th>Date</th>
                        <th width="80"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td><span class="fw-semibold">#{{ $order->id }}</span></td>
                            <td>{{ $order->elim_order_id ?? '—' }}</td>
                            <td>
                                <div class="fw-medium">{{ $order->user?->name ?? '—' }}</div>
                                <small class="text-body-secondary">{{ $order->user?->phone }}</small>
                            </td>
                            <td><span class="badge bg-label-info text-uppercase">{{ $order->platform }}</span></td>
                            <td>
                                @if ($order->orderStatus)
                                    <span class="badge {{ $order->orderStatus->badgeClass() }}">{{ $order->orderStatus->name }}</span>
                                @else
                                    <span class="badge bg-label-secondary">{{ $order->status }}</span>
                                @endif
                            </td>
                            <td><span class="badge bg-label-warning">{{ $order->payment_status }}</span></td>
                            <td class="fw-semibold">{{ number_format($order->paymentAmountTjs(), 2) }} TJS</td>
                            <td>{{ $order->created_at?->format('M d, Y H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-icon btn-text-secondary">
                                    <i class="icon-base ti tabler-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-body-secondary">No orders found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div class="card-footer">{{ $orders->links() }}</div>
        @endif
    </div>
</div>
