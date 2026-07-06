<div class="container-xxl flex-grow-1 container-p-y">
    <div class="mb-4">
        <h4 class="mb-1">{{ __('admin.edit_order_status') }}</h4>
        <p class="mb-0 text-body-secondary">
            <code>{{ $orderStatus->code }}</code>
            @if ($orderStatus->is_system)
                · <span class="badge bg-label-info">{{ __('admin.order_status_system') }}</span>
            @endif
        </p>
    </div>

    <form wire:submit="update">
        @include('livewire.admin.order-status.partials.form', [
            'submitLabel' => __('admin.update_order_status'),
            'colors' => $colors,
            'readOnlyCode' => $orderStatus->is_system,
        ])
    </form>
</div>
