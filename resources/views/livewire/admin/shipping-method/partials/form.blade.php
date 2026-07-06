<div class="row g-4">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">{{ __('admin.shipping_method_details') }}</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-4">
                    <i class="icon-base ti tabler-route me-1"></i>
                    {{ __('admin.shipping_route_fixed', ['from' => \App\Models\ShippingMethod::ROUTE_FROM, 'to' => \App\Models\ShippingMethod::ROUTE_TO]) }}
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.shipping_method_name') }}</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" wire:model.blur="name" placeholder="Cargo">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.shipping_method_code') }}</label>
                        <input type="text" class="form-control @error('code') is-invalid @enderror" wire:model.blur="code" placeholder="cargo">
                        <small class="text-body-secondary">{{ __('admin.shipping_method_code_hint') }}</small>
                        @error('code') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.shipping_volumetric_divisor') }}</label>
                        <input type="number" class="form-control @error('volumetricDivisor') is-invalid @enderror" wire:model.blur="volumetricDivisor" min="1">
                        @error('volumetricDivisor') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.shipping_minimum_charge') }}</label>
                        <div class="input-group">
                            <input type="number" step="0.01" class="form-control @error('minimumCharge') is-invalid @enderror" wire:model.blur="minimumCharge" min="0">
                            <span class="input-group-text">TJS</span>
                        </div>
                        @error('minimumCharge') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
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
