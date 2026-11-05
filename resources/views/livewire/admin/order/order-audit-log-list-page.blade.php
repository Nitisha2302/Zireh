<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-1">{{ __('admin.order_audit_log') }}</h4>
            <p class="mb-0 text-body-secondary">{{ __('admin.order_audit_log_description') }}</p>
        </div>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-label-secondary">
            <i class="icon-base ti tabler-arrow-left me-1"></i> {{ __('admin.back_to_orders') }}
        </a>
    </div>

    <div class="card">
        <div class="card-header border-bottom">
            <div class="row g-3">
                <div class="col-md-5">
                    <input
                        type="text"
                        class="form-control"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('admin.search_audit_logs_placeholder') }}"
                    >
                </div>
                <div class="col-md-4">
                    <select class="form-select" wire:model.live="sourceFilter">
                        <option value="">{{ __('admin.all_audit_sources') }}</option>
                        @foreach ($sources as $source)
                            <option value="{{ $source }}">{{ __('admin.audit_source_'.$source) }}</option>
                        @endforeach
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
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ __('admin.created_date') }}</th>
                        <th>{{ __('admin.orders') }}</th>
                        <th>{{ __('admin.status') }}</th>
                        <th>{{ __('admin.audit_source') }}</th>
                        <th>{{ __('admin.employee_id') }}</th>
                        <th>{{ __('admin.notes') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="text-nowrap small text-body-secondary">
                                {{ $log->created_at?->translatedFormat('d M Y H:i:s') }}
                            </td>
                            <td>
                                @if ($log->order)
                                    <a href="{{ route('admin.orders.show', $log->order) }}" class="fw-semibold">
                                        #{{ $log->order->id }} · {{ $log->order->parcel_code }}
                                    </a>
                                    <div class="small text-body-secondary">{{ $log->order->elim_order_id ?? '—' }}</div>
                                @else
                                    #{{ $log->customer_order_id }}
                                @endif
                            </td>
                            <td>
                                @if ($log->from_status)
                                    <span class="badge bg-label-secondary">{{ $log->fromOrderStatus?->name ?? $log->from_status }}</span>
                                    <i class="icon-base ti tabler-arrow-right mx-1 small"></i>
                                @endif
                                <span class="badge {{ $log->toOrderStatus?->badgeClass() ?? 'bg-label-primary' }}">
                                    {{ $log->toOrderStatus?->name ?? $log->to_status }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $log->sourceBadgeClass() }}">{{ $log->sourceLabel() }}</span>
                            </td>
                            <td>
                                @if ($log->admin)
                                    <span class="fw-medium">#{{ $log->admin->id }} — {{ $log->admin->name }}</span>
                                @else
                                    <span class="text-body-secondary">{{ __('admin.system_automated') }}</span>
                                @endif
                            </td>
                            <td class="small text-body-secondary">
                                {{ $log->notes ?: '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-body-secondary">
                                {{ __('admin.no_audit_logs_found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="card-footer">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
