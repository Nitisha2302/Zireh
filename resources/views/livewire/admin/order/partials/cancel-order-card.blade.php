@if (! empty($canCancelOrder))
    <div class="card mt-4 border-danger">
        <div class="card-header">
            <h5 class="mb-0 text-danger">{{ __('admin.cancel_order_and_refund') }}</h5>
        </div>
        <div class="card-body">
            @error('order')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror
            @error('cancel')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror

            <p class="mb-2">
                {{ __('admin.cancel_order_and_refund_hint', [
                    'customer' => $order->user?->name ?: __('admin.customer'),
                    'amount' => number_format($order->paymentAmountTjs(), 2),
                ]) }}
            </p>
            <p class="mb-3 text-body-secondary small mb-0">
                {{ __('admin.cancel_order_and_refund_methods_hint') }}
            </p>

            <button
                type="button"
                class="btn btn-danger mt-3"
                wire:click="cancelOrder"
                wire:confirm="{{ __('admin.cancel_order_and_refund_confirm', ['amount' => number_format($order->paymentAmountTjs(), 2)]) }}"
                wire:loading.attr="disabled"
                wire:target="cancelOrder"
            >
                <span wire:loading.remove wire:target="cancelOrder">{{ __('admin.cancel_order_and_refund') }}</span>
                <span wire:loading wire:target="cancelOrder">{{ __('admin.saving') }}...</span>
            </button>
        </div>
    </div>
@endif
