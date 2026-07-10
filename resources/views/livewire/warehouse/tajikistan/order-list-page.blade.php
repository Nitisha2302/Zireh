<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h4 class="mb-1">{{ __('admin.tajikistan_warehouse_orders') }}</h4>
                    <p class="mb-0 text-body-secondary">
                        @if ($assignedWarehouse)
                            {{ __('admin.tajikistan_warehouse_scope', ['name' => $assignedWarehouse->warehouse_name]) }}
                        @else
                            {{ __('admin.tajikistan_warehouse_orders_description') }}
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <div class="card-body border-top border-bottom">
            <div class="row align-items-center g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
                        <input type="text" class="form-control" placeholder="{{ __('admin.search_orders_placeholder') }}" wire:model.live.debounce.500ms="search">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" wire:model.live="platformFilter">
                        <option value="">{{ __('admin.all_platforms') }}</option>
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
                    <span class="badge bg-label-primary fs-6">{{ __('admin.total') }}: {{ $orders->total() }}</span>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>{{ __('admin.elim_order_id') }}</th>
                        <th>{{ __('admin.customer') }}</th>
                        <th>{{ __('admin.platforms') }}</th>
                        <th>{{ __('admin.status') }}</th>
                        <th>{{ __('admin.parcel_tracking_id') }}</th>
                        <th>{{ __('admin.payment') }}</th>
                        <th>{{ __('admin.created_date') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td class="fw-semibold">#{{ $order->id }}</td>
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
                            <td>{{ $order->parcel_tracking_id ?: '—' }}</td>
                            <td><span class="badge bg-label-warning">{{ trans()->has('admin.payment_status_'.$order->payment_status) ? __('admin.payment_status_'.$order->payment_status) : $order->payment_status }}</span></td>
                            <td>{{ $order->created_at?->translatedFormat('d M Y H:i') }}</td>
                            <td>
                                <a href="{{ route('tajikistan.orders.show', $order) }}" class="btn btn-sm btn-icon btn-text-secondary">
                                    <i class="icon-base ti tabler-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-body-secondary">{{ __('admin.no_orders_found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div class="card-footer">{{ $orders->links('livewire::bootstrap') }}</div>
        @endif
    </div>
</div>
