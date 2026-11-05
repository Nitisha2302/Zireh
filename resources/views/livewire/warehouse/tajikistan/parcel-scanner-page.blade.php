<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-1">{{ __('admin.parcel_barcode_scanner') }}</h4>
            <p class="mb-0 text-body-secondary">
                @if ($assignedWarehouse)
                    {{ $assignedWarehouse->warehouse_name }} ({{ $assignedWarehouse->warehouse_code }}) ·
                @endif
                {{ __('admin.parcel_barcode_scanner_description') }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('tajikistan.processing') }}" class="btn btn-label-primary">
                <i class="icon-base ti tabler-scale me-1"></i> {{ __('admin.tajikistan_warehouse_processing') }}
            </a>
            <a href="{{ route('tajikistan.exceptions.index') }}" class="btn btn-label-warning">
                <i class="icon-base ti tabler-alert-triangle me-1"></i> {{ __('admin.exception_management') }}
            </a>
            <a href="{{ route('tajikistan.orders.index') }}" class="btn btn-label-secondary">
                <i class="icon-base ti tabler-arrow-left me-1"></i> {{ __('admin.back_to_orders') }}
            </a>
        </div>
    </div>

    @include('livewire.warehouse.partials.parcel-scanner-workspace')
</div>
