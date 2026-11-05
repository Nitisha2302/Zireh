<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-1">{{ __('admin.exception_management') }}</h4>
            <p class="mb-0 text-body-secondary">
                @if ($assignedWarehouse)
                    {{ $assignedWarehouse->warehouse_name }} ({{ $assignedWarehouse->warehouse_code }}) ·
                @endif
                {{ __('admin.exception_management_description') }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('tajikistan.scanner') }}" class="btn btn-label-primary">
                <i class="icon-base ti tabler-barcode me-1"></i> {{ __('admin.parcel_barcode_scanner') }}
            </a>
            <a href="{{ route('tajikistan.orders.index') }}" class="btn btn-label-secondary">
                <i class="icon-base ti tabler-arrow-left me-1"></i> {{ __('admin.back_to_orders') }}
            </a>
        </div>
    </div>

    @include('livewire.warehouse.partials.exception-management-workspace')
</div>
