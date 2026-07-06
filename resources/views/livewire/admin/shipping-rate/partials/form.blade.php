<div class="row g-4">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">{{ __('admin.shipping_rate_details') }}</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-4">
                    <i class="icon-base ti tabler-route me-1"></i>
                    {{ __('admin.shipping_route_fixed', ['from' => \App\Models\ShippingMethod::ROUTE_FROM, 'to' => \App\Models\ShippingMethod::ROUTE_TO]) }}
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">{{ __('admin.shipping_method') }}</label>
                        <select class="form-select @error('shippingMethodId') is-invalid @enderror" wire:model.blur="shippingMethodId">
                            <option value="">{{ __('admin.select_shipping_method') }}</option>
                            @foreach ($methods as $method)
                                <option value="{{ $method->id }}">{{ $method->name }} ({{ $method->code }})</option>
                            @endforeach
                        </select>
                        @error('shippingMethodId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('admin.shipping_min_weight') }}</label>
                        <div class="input-group">
                            <input type="number" step="0.01" class="form-control @error('minWeight') is-invalid @enderror" wire:model.blur="minWeight" min="0">
                            <span class="input-group-text">KG</span>
                        </div>
                        @error('minWeight') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('admin.shipping_max_weight') }}</label>
                        <div class="input-group">
                            <input type="number" step="0.01" class="form-control @error('maxWeight') is-invalid @enderror" wire:model.blur="maxWeight" min="0">
                            <span class="input-group-text">KG</span>
                        </div>
                        @error('maxWeight') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('admin.shipping_rate_per_kg') }}</label>
                        <div class="input-group">
                            <input type="number" step="0.01" class="form-control @error('ratePerKg') is-invalid @enderror" wire:model.blur="ratePerKg" min="0">
                            <span class="input-group-text">TJS/KG</span>
                        </div>
                        @error('ratePerKg') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">{{ __('admin.status') }}</h5>
            </div>
            <div class="card-body">
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" id="isActive" wire:model="isActive">
                    <label class="form-check-label" for="isActive">{{ __('admin.active') }}</label>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save,update">{{ $submitLabel }}</span>
                        <span wire:loading wire:target="save,update">{{ __('admin.saving') }}...</span>
                    </button>
                    <a href="{{ $cancelUrl }}" class="btn btn-label-secondary">{{ __('admin.cancel') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>
