<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h4 class="mb-1">{{ __('admin.tajikistan_warehouse_shipments') }}</h4>
                    <p class="mb-0 text-body-secondary">
                        @if ($assignedWarehouse)
                            {{ __('admin.tajikistan_warehouse_scope', ['name' => $assignedWarehouse->warehouse_name]) }}
                        @else
                            {{ __('admin.shipment_management_description') }}
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <div class="card-body border-top border-bottom">
            <div class="row align-items-center g-3">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
                        <input type="text" class="form-control" placeholder="{{ __('admin.search_shipments_placeholder') }}" wire:model.live.debounce.500ms="search">
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-select" wire:model.live="statusFilter">
                        <option value="">{{ __('admin.all_statuses') }}</option>
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status->code }}">{{ $status->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 text-md-end">
                    <span class="badge bg-label-primary fs-6">{{ __('admin.total') }}: {{ $shipments->total() }}</span>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>{{ __('admin.shipment_id') }}</th>
                        <th>{{ __('admin.status') }}</th>
                        <th>{{ __('admin.orders_count') }}</th>
                        <th>{{ __('admin.tajikistan_receipt_status') }}</th>
                        <th>{{ __('admin.created_date') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($shipments as $shipment)
                        <tr>
                            <td class="fw-semibold">
                                <a href="{{ route('tajikistan.shipments.show', $shipment) }}" class="text-heading">
                                    {{ $shipment->shipment_code }}
                                </a>
                            </td>
                            <td>
                                @if ($shipment->orderStatus)
                                    <span class="badge {{ $shipment->orderStatus->badgeClass() }}">{{ $shipment->orderStatus->name }}</span>
                                @else
                                    <span class="badge bg-label-secondary">{{ $shipment->status }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-label-info">{{ $shipment->orders_count }}</span>
                            </td>
                            <td>
                                @if ($shipment->isStatusUpdateLocked())
                                    <span class="badge bg-label-success">
                                        <i class="icon-base ti tabler-check me-1"></i>{{ __('admin.received_in_tajikistan') }}
                                    </span>
                                @else
                                    <span class="badge bg-label-warning">{{ __('admin.awaiting_tajikistan_receipt') }}</span>
                                @endif
                            </td>
                            <td>{{ $shipment->created_at?->translatedFormat('d M Y H:i') }}</td>
                            <td class="text-end">
                                <a href="{{ route('tajikistan.shipments.show', $shipment) }}" class="btn btn-sm btn-icon btn-text-secondary">
                                    <i class="icon-base ti tabler-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-body-secondary">{{ __('admin.no_shipments_found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($shipments->hasPages())
            <div class="card-footer">{{ $shipments->links('livewire::bootstrap') }}</div>
        @endif
    </div>
</div>
