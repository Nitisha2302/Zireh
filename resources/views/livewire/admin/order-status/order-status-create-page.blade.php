<div class="container-xxl flex-grow-1 container-p-y">
    <div class="mb-4">
        <h4 class="mb-1">{{ __('admin.add_order_status') }}</h4>
        <p class="mb-0 text-body-secondary">{{ __('admin.order_statuses_description') }}</p>
    </div>

    <form wire:submit="save">
        @include('livewire.admin.order-status.partials.form', [
            'submitLabel' => __('admin.save_order_status'),
            'colors' => $colors,
            'readOnlyCode' => false,
        ])
    </form>
</div>
