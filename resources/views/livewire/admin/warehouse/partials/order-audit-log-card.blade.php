@php
    $histories = $order->relationLoaded('statusHistories')
        ? $order->statusHistories
        : $order->statusHistories()->with(['admin', 'fromOrderStatus', 'toOrderStatus'])->get();
@endphp
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="icon-base ti tabler-history me-1"></i>
            {{ __('admin.order_audit_log') }} ({{ $histories->count() }})
        </h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>{{ __('admin.created_date') }}</th>
                    <th>{{ __('admin.status') }}</th>
                    <th>{{ __('admin.audit_source') }}</th>
                    <th>{{ __('admin.employee_id') }}</th>
                    <th>{{ __('admin.notes') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($histories as $history)
                    <tr>
                        <td class="text-nowrap small text-body-secondary">
                            {{ $history->created_at?->translatedFormat('d M Y H:i:s') }}
                        </td>
                        <td>
                            @if ($history->from_status)
                                <span class="badge bg-label-secondary">{{ $history->fromOrderStatus?->name ?? $history->from_status }}</span>
                                <i class="icon-base ti tabler-arrow-right mx-1 small"></i>
                            @endif
                            <span class="badge {{ $history->toOrderStatus?->badgeClass() ?? 'bg-label-primary' }}">
                                {{ $history->toOrderStatus?->name ?? $history->to_status }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $history->sourceBadgeClass() }}">{{ $history->sourceLabel() }}</span>
                        </td>
                        <td>
                            @if ($history->admin)
                                <span class="fw-medium">#{{ $history->admin->id }} — {{ $history->admin->name }}</span>
                            @else
                                <span class="text-body-secondary">{{ __('admin.system_automated') }}</span>
                            @endif
                        </td>
                        <td class="small text-body-secondary">
                            {{ $history->notes ?: '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-body-secondary">
                            {{ __('admin.no_audit_logs_found') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
