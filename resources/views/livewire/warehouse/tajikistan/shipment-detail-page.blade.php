<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">{{ __('admin.shipment') }}: {{ $shipment->shipment_code }}</h4>
            <p class="mb-0 text-body-secondary">
                {{ __('admin.created_date') }}: {{ $shipment->created_at?->translatedFormat('d M Y H:i') }}
            </p>
        </div>
        <a href="{{ route('tajikistan.shipments.index') }}" class="btn btn-label-secondary">
            <i class="icon-base ti tabler-arrow-left me-1"></i> {{ __('admin.back_to_shipments') }}
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">{{ __('admin.shipment_details') }}</h5></div>
                <div class="card-body">
                    <p class="mb-2"><strong>{{ __('admin.shipment_id') }}:</strong> <code>{{ $shipment->shipment_code }}</code></p>
                    <p class="mb-2">
                        <strong>{{ __('admin.status') }}:</strong>
                        @if ($shipment->orderStatus)
                            <span class="badge {{ $shipment->orderStatus->badgeClass() }}">{{ $shipment->orderStatus->name }}</span>
                        @else
                            <span class="badge bg-label-secondary">{{ $shipment->status }}</span>
                        @endif
                    </p>
                    <p class="mb-2"><strong>{{ __('admin.orders_count') }}:</strong> {{ $orders->count() }}</p>
                    @if ($shipment->notes)
                        <p class="mb-0"><strong>{{ __('admin.notes') }}:</strong> {{ $shipment->notes }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">{{ __('admin.tajikistan_receipt_status') }}</h5></div>
                <div class="card-body">
                    @if ($isLocked)
                        <div class="alert alert-success mb-3">
                            <i class="icon-base ti tabler-check me-1"></i>
                            {{ __('admin.shipment_received_individual_orders_enabled') }}
                        </div>
                        <button type="button" class="btn btn-secondary" disabled>
                            <i class="icon-base ti tabler-lock me-1"></i> {{ __('admin.shipment_status_update_disabled') }}
                        </button>
                    @else
                        <p class="text-body-secondary mb-3">{{ __('admin.receive_shipment_in_tajikistan_hint') }}</p>
                        @error('shipment') <div class="alert alert-danger">{{ $message }}</div> @enderror
                        <button
                            type="button"
                            class="btn btn-primary"
                            wire:click="markReceived"
                            wire:loading.attr="disabled"
                            wire:target="markReceived"
                        >
                            <span wire:loading.remove wire:target="markReceived">
                                <i class="icon-base ti tabler-package-import me-1"></i> {{ __('admin.mark_shipment_received_in_tajikistan') }}
                            </span>
                            <span wire:loading wire:target="markReceived">{{ __('admin.saving') }}...</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header"><h5 class="mb-0">{{ __('admin.shipment_orders') }} ({{ $orders->count() }})</h5></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>{{ __('admin.elim_order_id') }}</th>
                        <th>{{ __('admin.customer') }}</th>
                        <th>{{ __('admin.platforms') }}</th>
                        <th>{{ __('admin.status') }}</th>
                        <th>{{ __('admin.parcel_tracking_id') }}</th>
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
                            <td class="text-end">
                                <a href="{{ route('tajikistan.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">
                                    {{ __('admin.manage_order') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-body-secondary">{{ __('admin.no_orders_found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
