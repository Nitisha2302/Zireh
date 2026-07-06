<div class="container-xxl flex-grow-1 container-p-y">
    <div class="mb-4">
        <h4 class="mb-1">{{ __('admin.edit_shipping_method') }}</h4>
        <p class="mb-0 text-body-secondary">{{ $shippingMethod->name }} · <code>{{ $shippingMethod->code }}</code></p>
    </div>

    <form wire:submit="update">
        @include('livewire.admin.shipping-method.partials.form', [
            'submitLabel' => __('admin.update_shipping_method'),
            'cancelUrl' => route('admin.shipping-methods.index'),
        ])
    </form>
</div>
