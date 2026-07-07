<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">{{ __('admin.china_warehouse_order') }} #{{ $order->id }}</h4>
            <p class="mb-0 text-body-secondary">{{ $order->elim_order_id ?? __('admin.no_elim_order_id') }}</p>
        </div>
        <a href="{{ route('china.orders.index') }}" class="btn btn-label-secondary">
            <i class="icon-base ti tabler-arrow-left me-1"></i> {{ __('admin.back_to_orders') }}
        </a>
    </div>

    @include('livewire.admin.warehouse.partials.order-detail-cards', ['order' => $order, 'statusOptions' => $statusOptions])

    <div class="card mt-4">
        <div class="card-header"><h5 class="mb-0">{{ __('admin.parcel_tracking_id') }}</h5></div>
        <div class="card-body">
            <form wire:submit="updateParcelTracking" class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="form-label">{{ __('admin.parcel_tracking_id') }}</label>
                    <input type="text" class="form-control @error('parcelTrackingId') is-invalid @enderror" wire:model="parcelTrackingId" maxlength="120" placeholder="{{ __('admin.parcel_tracking_placeholder') }}">
                    @error('parcelTrackingId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="updateParcelTracking">
                        <span wire:loading.remove wire:target="updateParcelTracking">{{ __('admin.save_parcel_tracking') }}</span>
                        <span wire:loading wire:target="updateParcelTracking">{{ __('admin.saving') }}...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
