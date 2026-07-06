<div class="container-xxl flex-grow-1 container-p-y">
    <div class="mb-4">
        <h4 class="mb-1">{{ __('admin.edit_shipping_rate') }}</h4>
        <p class="mb-0 text-body-secondary">{{ $shippingRate->weightRangeLabel() }} · {{ $shippingRate->shippingMethod?->name }}</p>
    </div>

    <form wire:submit="update">
        @include('livewire.admin.shipping-rate.partials.form', [
            'submitLabel' => __('admin.update_shipping_rate'),
            'cancelUrl' => route('admin.shipping-rates.index'),
        ])
    </form>
</div>
