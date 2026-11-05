<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">{{ __('admin.create_shipment') }}</h4>
            <p class="mb-0 text-body-secondary">{{ __('admin.create_shipment_description') }}</p>
        </div>
        <a href="{{ route('china.shipments.index') }}" class="btn btn-label-secondary">
            <i class="icon-base ti tabler-arrow-left me-1"></i> {{ __('admin.back_to_shipments') }}
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form wire:submit="save" class="row g-4">
                <div class="col-md-6">
                    <label class="form-label" for="shipment-code">{{ __('admin.shipment_id') }} <span class="text-danger">*</span></label>
                    <input
                        id="shipment-code"
                        type="text"
                        class="form-control @error('shipment_code') is-invalid @enderror"
                        wire:model="shipmentCode"
                        maxlength="120"
                        placeholder="{{ __('admin.shipment_id_placeholder') }}"
                    >
                    @error('shipment_code') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="shipment-status">{{ __('admin.initial_shipment_status') }} <span class="text-danger">*</span></label>
                    <select id="shipment-status" class="form-select @error('status') is-invalid @enderror" wire:model="statusCode">
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status->code }}">{{ $status->name }}</option>
                        @endforeach
                    </select>
                    @error('status') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label class="form-label" for="unassigned-order-dropdown">{{ __('admin.select_unassigned_orders') }} <span class="text-danger">*</span></label>
                    <div class="row g-2 align-items-center mb-3">
                        <div class="col-md-9">
                            <select id="unassigned-order-dropdown" class="form-select" wire:model="orderToAdd">
                                <option value="">{{ __('admin.choose_order_from_dropdown') }}</option>
                                @foreach ($unassignedOrders as $order)
                                    @if (! in_array($order->id, array_map('intval', $selectedOrderIds), true))
                                        <option value="{{ $order->id }}">
                                            #{{ $order->id }} — {{ $order->elim_order_id ?? '—' }} ({{ strtoupper($order->platform) }}) · {{ $order->user?->name ?? '—' }} · {{ $order->orderStatus?->name ?? $order->status }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-outline-primary w-100" wire:click="addOrderFromDropdown">
                                <i class="icon-base ti tabler-plus me-1"></i> {{ __('admin.add_order_to_shipment') }}
                            </button>
                        </div>
                    </div>

                    <label class="form-label small text-body-secondary" for="unassigned-orders-multiselect">{{ __('admin.unassigned_orders_multiselect_hint') }}</label>
                    <select
                        id="unassigned-orders-multiselect"
                        class="form-select @error('order_ids') is-invalid @enderror"
                        wire:model.live="selectedOrderIds"
                        multiple
                        size="6"
                    >
                        @forelse ($unassignedOrders as $order)
                            <option value="{{ $order->id }}">
                                #{{ $order->id }} — {{ $order->elim_order_id ?? '—' }} ({{ strtoupper($order->platform) }}) · {{ $order->user?->name ?? '—' }} · {{ $order->warehouse?->warehouse_name ?? data_get($order->warehouse_snapshot, 'warehouse_name', '—') }}
                            </option>
                        @empty
                            <option value="" disabled>{{ __('admin.no_unassigned_orders_available') }}</option>
                        @endforelse
                    </select>
                    @error('order_ids') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                @if ($selectedOrders->isNotEmpty())
                    <div class="col-12">
                        <h6 class="mb-2">{{ __('admin.selected_orders') }} ({{ $selectedOrders->count() }})</h6>
                        <div class="table-responsive border rounded">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>{{ __('admin.elim_order_id') }}</th>
                                        <th>{{ __('admin.customer') }}</th>
                                        <th>{{ __('admin.platforms') }}</th>
                                        <th>{{ __('admin.status') }}</th>
                                        <th>{{ __('admin.tajikistan_warehouse') }}</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($selectedOrders as $selectedOrder)
                                        <tr>
                                            <td class="fw-semibold">#{{ $selectedOrder->id }}</td>
                                            <td>{{ $selectedOrder->elim_order_id ?? '—' }}</td>
                                            <td>{{ $selectedOrder->user?->name ?? '—' }}</td>
                                            <td><span class="badge bg-label-info text-uppercase">{{ $selectedOrder->platform }}</span></td>
                                            <td>{{ $selectedOrder->orderStatus?->name ?? $selectedOrder->status }}</td>
                                            <td>{{ $selectedOrder->warehouse?->warehouse_name ?? data_get($selectedOrder->warehouse_snapshot, 'warehouse_name', '—') }}</td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-label-danger" wire:click="removeSelectedOrder({{ $selectedOrder->id }})">
                                                    {{ __('admin.remove') }}
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <div class="col-12">
                    <label class="form-label" for="shipment-notes">{{ __('admin.notes') }}</label>
                    <textarea id="shipment-notes" class="form-control" rows="2" wire:model="notes" maxlength="1000"></textarea>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">{{ __('admin.create_shipment') }}</span>
                        <span wire:loading wire:target="save">{{ __('admin.saving') }}...</span>
                    </button>
                    <a href="{{ route('china.shipments.index') }}" class="btn btn-label-secondary">{{ __('admin.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>
