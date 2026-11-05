<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">{{ __('admin.shipment') }}: {{ $shipment->shipment_code }}</h4>
            <p class="mb-0 text-body-secondary">
                {{ __('admin.created_date') }}: {{ $shipment->created_at?->translatedFormat('d M Y H:i') }}
            </p>
        </div>
        <a href="{{ route('china.shipments.index') }}" class="btn btn-label-secondary">
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
                    <p class="mb-2"><strong>{{ __('admin.orders_count') }}:</strong> {{ $shipment->orders->count() }}</p>
                    <p class="mb-2">
                        <strong>{{ __('admin.tajikistan_receipt_status') }}:</strong>
                        @if ($isLocked)
                            <span class="badge bg-label-success">{{ __('admin.received_in_tajikistan') }}</span>
                            @if ($shipment->received_in_tajikistan_at)
                                <small class="text-body-secondary ms-1">({{ $shipment->received_in_tajikistan_at->translatedFormat('d M Y H:i') }})</small>
                            @endif
                        @else
                            <span class="badge bg-label-warning">{{ __('admin.managed_via_shipment') }}</span>
                        @endif
                    </p>
                    @if ($shipment->notes)
                        <p class="mb-0"><strong>{{ __('admin.notes') }}:</strong> {{ $shipment->notes }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">{{ __('admin.update_shipment_status') }}</h5></div>
                <div class="card-body">
                    @if ($isLocked)
                        <div class="alert alert-warning mb-3">
                            <i class="icon-base ti tabler-lock me-1"></i>
                            {{ __('admin.shipment_status_locked_in_tajikistan') }}
                        </div>
                    @else
                        <p class="text-body-secondary small mb-3">{{ __('admin.shipment_status_cascade_hint') }}</p>
                    @endif

                    <form wire:submit="updateStatus">
                        <label class="form-label" for="shipment-status-select">{{ __('admin.change_shipment_status') }}</label>
                        <select
                            id="shipment-status-select"
                            class="form-select mb-2 @error('statusCode') is-invalid @enderror"
                            wire:model="statusCode"
                            @if ($isLocked) disabled @endif
                        >
                            @foreach ($statusOptions as $status)
                                <option value="{{ $status->code }}">{{ $status->name }}</option>
                            @endforeach
                        </select>
                        @error('statusCode') <div class="invalid-feedback d-block mb-2">{{ $message }}</div> @enderror

                        <button
                            type="submit"
                            class="btn btn-primary"
                            wire:loading.attr="disabled"
                            wire:target="updateStatus"
                            @if ($isLocked) disabled @endif
                        >
                            <span wire:loading.remove wire:target="updateStatus">{{ __('admin.update_shipment_status') }}</span>
                            <span wire:loading wire:target="updateStatus">{{ __('admin.saving') }}...</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if (! $isLocked)
        <div class="card mt-4">
            <div class="card-header"><h5 class="mb-0">{{ __('admin.add_order_to_shipment') }}</h5></div>
            <div class="card-body">
                <form wire:submit="addOrderToShipment" class="row g-3 align-items-end">
                    <div class="col-md-9">
                        <label class="form-label" for="add-unassigned-order">{{ __('admin.select_unassigned_orders') }}</label>
                        <select id="add-unassigned-order" class="form-select @error('order_ids') is-invalid @enderror" wire:model="orderToAdd">
                            <option value="">{{ __('admin.choose_order_from_dropdown') }}</option>
                            @foreach ($unassignedOrders as $order)
                                <option value="{{ $order->id }}">
                                    #{{ $order->id }} — {{ $order->elim_order_id ?? '—' }} ({{ strtoupper($order->platform) }}) · {{ $order->user?->name ?? '—' }}
                                </option>
                            @endforeach
                        </select>
                        @error('order_ids') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-outline-primary w-100" wire:loading.attr="disabled" wire:target="addOrderToShipment">
                            <i class="icon-base ti tabler-plus me-1"></i> {{ __('admin.add_order_to_shipment') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="card mt-4">
        <div class="card-header"><h5 class="mb-0">{{ __('admin.shipment_orders') }} ({{ $shipment->orders->count() }})</h5></div>
        @error('order')
            <div class="card-body pb-0">
                <div class="alert alert-danger mb-0">{{ $message }}</div>
            </div>
        @enderror
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
                        <th>{{ __('admin.tajikistan_warehouse') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($shipment->orders as $order)
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
                            <td>{{ $order->warehouse?->warehouse_name ?? data_get($order->warehouse_snapshot, 'warehouse_name', '—') }}</td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('china.orders.show', $order) }}" class="btn btn-sm btn-icon btn-text-secondary">
                                        <i class="icon-base ti tabler-eye"></i>
                                    </a>
                                    @if (! $isLocked && $shipment->orders->count() > 1)
                                        <button type="button" class="btn btn-sm btn-label-danger" wire:click="removeOrderFromShipment({{ $order->id }})">
                                            {{ __('admin.remove') }}
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-body-secondary">{{ __('admin.no_orders_found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
