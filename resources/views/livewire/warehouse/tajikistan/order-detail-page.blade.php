<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">{{ __('admin.tajikistan_warehouse_order') }} #{{ $order->id }}</h4>
            <p class="mb-0 text-body-secondary">{{ $order->elim_order_id ?? __('admin.no_elim_order_id') }}</p>
        </div>
        <a href="{{ route('tajikistan.orders.index') }}" class="btn btn-label-secondary">
            <i class="icon-base ti tabler-arrow-left me-1"></i> {{ __('admin.back_to_orders') }}
        </a>
    </div>

    @include('livewire.admin.warehouse.partials.order-detail-cards', [
        'order' => $order,
        'statusOptions' => collect(),
        'hideStatusForm' => true,
    ])

    @if ($canMeasure)
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">{{ __('admin.pickup_measurement') }}</h5>
                <small class="text-body-secondary d-block">{{ __('admin.pickup_measurement_hint') }}</small>
                <small class="text-body-secondary">{{ __('admin.pickup_shipping_rate_hint') }}</small>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">{{ __('admin.shipping_method') }}</label>
                        <select class="form-select @error('shippingMethodId') is-invalid @enderror" wire:model="shippingMethodId">
                            <option value="">{{ __('admin.select_shipping_method') }}</option>
                            @foreach ($shippingMethods as $method)
                                <option value="{{ $method->id }}">{{ $method->name }} ({{ $method->code }})</option>
                            @endforeach
                        </select>
                        @error('shippingMethodId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('admin.package_length_cm') }}</label>
                        <input type="number" step="0.01" class="form-control @error('packageLengthCm') is-invalid @enderror" wire:model.blur="packageLengthCm">
                        @error('packageLengthCm') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('admin.package_width_cm') }}</label>
                        <input type="number" step="0.01" class="form-control @error('packageWidthCm') is-invalid @enderror" wire:model.blur="packageWidthCm">
                        @error('packageWidthCm') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('admin.package_height_cm') }}</label>
                        <input type="number" step="0.01" class="form-control @error('packageHeightCm') is-invalid @enderror" wire:model.blur="packageHeightCm">
                        @error('packageHeightCm') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('admin.package_weight_kg') }}</label>
                        <input type="number" step="0.01" class="form-control @error('packageWeightKg') is-invalid @enderror" wire:model.blur="packageWeightKg">
                        @error('packageWeightKg') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                @if ($shippingPreview)
                    <div class="border rounded p-3 mt-4 bg-label-primary">
                        <h6 class="mb-3">{{ __('admin.pickup_shipping_preview') }}</h6>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('admin.shipping_method') }}:</strong> {{ $shippingPreview['method']['name'] ?? '—' }}</p>
                                <p class="mb-1"><strong>{{ __('admin.package_volume_m3') }}:</strong> {{ number_format($shippingPreview['package']['volume_m3'], 4) }} m³</p>
                                <p class="mb-1"><strong>{{ __('admin.volumetric_weight_kg') }}:</strong> {{ number_format($shippingPreview['package']['volumetric_weight_kg'], 2) }} kg</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('admin.weight_cost') }}:</strong> {{ number_format($shippingPreview['weight_cost_tjs'], 2) }} TJS</p>
                                <p class="mb-1"><strong>{{ __('admin.volume_cost') }}:</strong> {{ number_format($shippingPreview['volume_cost_tjs'], 2) }} TJS</p>
                                <p class="mb-1"><strong>{{ __('admin.applied_method') }}:</strong> {{ isset($shippingPreview['applied_method']) && trans()->has('admin.pickup_method_'.$shippingPreview['applied_method']) ? __('admin.pickup_method_'.$shippingPreview['applied_method']) : ucfirst($shippingPreview['applied_method'] ?? '—') }}</p>
                            </div>
                        </div>
                        <hr class="my-3">
                        <p class="mb-0 fs-5 fw-semibold text-primary">
                            {{ __('admin.final_pickup_shipping') }}: {{ number_format($shippingPreview['shipping_cost'], 2) }} TJS
                        </p>
                    </div>
                @endif

                <div class="d-flex gap-2 mt-4">
                    <button type="button" class="btn btn-label-primary" wire:click="previewPickupShipping" wire:loading.attr="disabled" wire:target="previewPickupShipping">
                        <span wire:loading.remove wire:target="previewPickupShipping">{{ __('admin.preview_pickup_shipping') }}</span>
                        <span wire:loading wire:target="previewPickupShipping">{{ __('admin.calculating') }}...</span>
                    </button>
                    <button type="button" class="btn btn-primary" wire:click="confirmPickup" wire:loading.attr="disabled" wire:target="confirmPickup" @if (!$shippingPreview) disabled @endif>
                        <span wire:loading.remove wire:target="confirmPickup">{{ __('admin.confirm_ready_for_pickup') }}</span>
                        <span wire:loading wire:target="confirmPickup">{{ __('admin.saving') }}...</span>
                    </button>
                </div>
            </div>
        </div>
    @elseif ($isReadyForPickup)
        <div class="card mt-4">
            <div class="card-header"><h5 class="mb-0">{{ __('admin.pickup_shipping') }}</h5></div>
            <div class="card-body">
                <div class="row g-4 align-items-start">
                    <div class="col-md-7">
                        <p class="mb-1"><strong>{{ __('admin.pickup_shipping') }}:</strong> {{ number_format((float) $order->pickup_shipping_fee_tjs, 2) }} TJS</p>
                        <p class="mb-1"><strong>{{ __('admin.applied_method') }}:</strong> {{ $order->pickup_shipping_calculation_method && trans()->has('admin.pickup_method_'.$order->pickup_shipping_calculation_method) ? __('admin.pickup_method_'.$order->pickup_shipping_calculation_method) : ($order->pickup_shipping_calculation_method ? ucfirst($order->pickup_shipping_calculation_method) : '—') }}</p>
                        <p class="mb-1"><strong>{{ __('admin.pickup_payment_status') }}:</strong> {{ $order->pickup_payment_status && trans()->has('admin.payment_status_'.$order->pickup_payment_status) ? __('admin.payment_status_'.$order->pickup_payment_status) : $order->pickup_payment_status }}</p>
                        <p class="mb-1"><strong>{{ __('admin.package_weight_kg') }}:</strong> {{ $order->package_weight_kg }} kg</p>
                        <p class="mb-3"><strong>{{ __('admin.pickup_token') }}:</strong> <code>{{ $order->pickup_token }}</code></p>
                        <a href="{{ route('tajikistan.pickup.show', $order->pickup_token) }}" class="btn btn-primary">
                            {{ __('admin.open_pickup_view') }}
                        </a>
                    </div>
                    <div class="col-md-5">
                        <div class="border rounded p-3 text-center bg-light h-100">
                            <p class="fw-medium mb-2">{{ __('admin.pickup_qr_code') }}</p>
                            <img
                                src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($order->pickupQrPayload()) }}"
                                alt="{{ __('admin.pickup_qr_code') }}"
                                width="200"
                                height="200"
                                class="img-fluid rounded bg-white p-2 border"
                            >
                            <p class="small text-body-secondary mt-2 mb-0">{{ __('admin.pickup_qr_code_hint') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="card mt-4">
            <div class="card-header"><h5 class="mb-0">{{ __('admin.order_status') }}</h5></div>
            <div class="card-body">
                <form wire:submit="updateStatus">
                    <label class="form-label">{{ __('admin.change_order_status') }}</label>
                    <select class="form-select mb-2 @error('statusCode') is-invalid @enderror" wire:model="statusCode">
                        @foreach (\App\Models\OrderStatus::query()->where('is_active', true)->orderBy('sort_order')->get() as $status)
                            <option value="{{ $status->code }}">{{ $status->name }}</option>
                        @endforeach
                    </select>
                    @error('statusCode') <div class="invalid-feedback d-block mb-2">{{ $message }}</div> @enderror
                    <button type="submit" class="btn btn-sm btn-primary" wire:loading.attr="disabled">{{ __('admin.update_order_status') }}</button>
                </form>
            </div>
        </div>
    @endif
</div>
