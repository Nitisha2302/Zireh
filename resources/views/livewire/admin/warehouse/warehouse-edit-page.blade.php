<div class="container-xxl flex-grow-1 container-p-y">
    <form wire:submit="update">
        @include('livewire.admin.warehouse.partials.form', [
            'submitLabel' => __('admin.update_warehouse'),
            'cancelUrl' => route('admin.warehouses.show', $warehouse),
            'isEdit' => true,
        ])
    </form>
</div>
