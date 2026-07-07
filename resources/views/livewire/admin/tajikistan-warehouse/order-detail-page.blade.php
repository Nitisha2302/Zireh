<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">{{ __('admin.tajikistan_warehouse_order') }} #{{ $order->id }}</h4>
            <p class="mb-0 text-body-secondary">{{ $order->elim_order_id ?? __('admin.no_elim_order_id') }}</p>
        </div>
        <a href="{{ route('admin.warehouse.orders.index') }}" class="btn btn-label-secondary">
            <i class="icon-base ti tabler-arrow-left me-1"></i> {{ __('admin.back_to_orders') }}
        </a>
    </div>

    @include('livewire.admin.warehouse.partials.order-detail-cards', ['order' => $order, 'statusOptions' => $statusOptions])
</div>
