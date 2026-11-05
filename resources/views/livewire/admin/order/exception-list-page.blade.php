<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-1">{{ __('admin.exception_management') }}</h4>
            <p class="mb-0 text-body-secondary">{{ __('admin.exception_management_description') }}</p>
        </div>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-label-secondary">
            <i class="icon-base ti tabler-arrow-left me-1"></i> {{ __('admin.back_to_orders') }}
        </a>
    </div>

    @include('livewire.warehouse.partials.exception-management-workspace')
</div>
