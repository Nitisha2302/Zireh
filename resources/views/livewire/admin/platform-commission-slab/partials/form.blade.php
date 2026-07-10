@if ($errors->any())
    <div class="alert alert-danger mb-4">
        <div class="fw-semibold mb-2">{{ __('admin.fix_errors') }}</div>
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-1">{{ __('admin.slab_details') }}</h5>
                <small class="text-body-secondary">{{ __('admin.slab_details_hint') }}</small>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <label class="form-label" for="minAmount">{{ __('admin.minimum_amount') }}</label>
                    <input id="minAmount" type="number" step="0.01" min="0" wire:model.blur="minAmount"
                        class="form-control @error('minAmount') is-invalid @enderror" placeholder="1">
                    @error('minAmount')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input @error('isUnlimited') is-invalid @enderror" type="checkbox" id="isUnlimited" wire:model.live="isUnlimited">
                        <label class="form-check-label" for="isUnlimited">{{ __('admin.unlimited_maximum') }}</label>
                    </div>
                    @error('isUnlimited')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                @unless ($isUnlimited)
                    <div class="mb-4">
                        <label class="form-label" for="maxAmount">{{ __('admin.maximum_amount') }}</label>
                        <input id="maxAmount" type="number" step="0.01" min="0" wire:model.blur="maxAmount"
                            class="form-control @error('maxAmount') is-invalid @enderror" placeholder="1000">
                        @error('maxAmount')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                @else
                    @error('maxAmount')
                        <div class="alert alert-warning py-2 mb-4">{{ $message }}</div>
                    @enderror
                @endunless

                <div class="mb-0">
                    <label class="form-label" for="commissionPercentage">{{ __('admin.commission_percentage') }}</label>
                    <input id="commissionPercentage" type="number" step="0.01" min="0" max="100" wire:model.blur="commissionPercentage"
                        class="form-control @error('commissionPercentage') is-invalid @enderror" placeholder="10">
                    @error('commissionPercentage')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">{{ __('admin.publish') }}</h5></div>
            <div class="card-body">
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" id="isActive" wire:model="isActive">
                    <label class="form-check-label" for="isActive">{{ __('admin.active') }}</label>
                </div>
                @error('isActive')
                    <div class="text-danger small mb-3">{{ $message }}</div>
                @enderror

                <button type="submit" class="btn btn-primary w-100" wire:loading.attr="disabled">
                    <i class="icon-base ti tabler-device-floppy"></i>
                    {{ isset($slab) ? __('admin.update_slab') : __('admin.save_slab') }}
                </button>
                <a href="{{ route('admin.platforms.commission-slabs.index', $platform) }}" class="btn btn-label-secondary w-100 mt-2">{{ __('admin.cancel') }}</a>
            </div>
        </div>
    </div>
</div>
