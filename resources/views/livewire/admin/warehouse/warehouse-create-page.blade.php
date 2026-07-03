<div class="container-xxl flex-grow-1 container-p-y">
    <form wire:submit="save">
        @include('livewire.admin.warehouse.partials.form', [
            'submitLabel' => __('admin.save_warehouse'),
            'cancelUrl' => route('admin.warehouses.index'),
        ])
    </form>
</div>
