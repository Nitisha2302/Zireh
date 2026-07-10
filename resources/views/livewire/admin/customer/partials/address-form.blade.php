<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-1">{{ __('admin.address_details') }}</h5>
                <small class="text-body-secondary">{{ __('admin.address_details_hint') }}</small>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label" for="full_name">{{ __('admin.full_name') }}</label>
                        <input id="full_name" type="text" wire:model.blur="address.full_name" class="form-control @error('address.full_name') is-invalid @enderror">
                        @error('address.full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="phone">{{ __('admin.phone') }}</label>
                        <input id="phone" type="text" wire:model.blur="address.phone" class="form-control @error('address.phone') is-invalid @enderror">
                        @error('address.phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="alternate_phone">{{ __('admin.alternate_phone') }}</label>
                        <input id="alternate_phone" type="text" wire:model.blur="address.alternate_phone" class="form-control @error('address.alternate_phone') is-invalid @enderror">
                        @error('address.alternate_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="address_type">{{ __('admin.address_type') }}</label>
                        <select id="address_type" wire:model.blur="address.address_type" class="form-select @error('address.address_type') is-invalid @enderror">
                            @foreach ($addressTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('address.address_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="address_line_1">{{ __('admin.address_line_1') }}</label>
                        <input id="address_line_1" type="text" wire:model.blur="address.address_line_1" class="form-control @error('address.address_line_1') is-invalid @enderror">
                        @error('address.address_line_1') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="address_line_2">{{ __('admin.address_line_2') }}</label>
                        <input id="address_line_2" type="text" wire:model.blur="address.address_line_2" class="form-control @error('address.address_line_2') is-invalid @enderror">
                        @error('address.address_line_2') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="landmark">{{ __('admin.landmark') }}</label>
                        <input id="landmark" type="text" wire:model.blur="address.landmark" class="form-control @error('address.landmark') is-invalid @enderror">
                        @error('address.landmark') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="city">{{ __('admin.city') }}</label>
                        <input id="city" type="text" wire:model.blur="address.city" class="form-control @error('address.city') is-invalid @enderror">
                        @error('address.city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="state">{{ __('admin.state') }}</label>
                        <input id="state" type="text" wire:model.blur="address.state" class="form-control @error('address.state') is-invalid @enderror">
                        @error('address.state') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="country">{{ __('admin.country') }}</label>
                        <input id="country" type="text" wire:model.blur="address.country" class="form-control @error('address.country') is-invalid @enderror">
                        @error('address.country') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="postal_code">{{ __('admin.postal_code') }}</label>
                        <input id="postal_code" type="text" wire:model.blur="address.postal_code" class="form-control @error('address.postal_code') is-invalid @enderror">
                        @error('address.postal_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="latitude">{{ __('admin.latitude') }}</label>
                        <input id="latitude" type="text" wire:model.blur="address.latitude" class="form-control @error('address.latitude') is-invalid @enderror">
                        @error('address.latitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="longitude">{{ __('admin.longitude') }}</label>
                        <input id="longitude" type="text" wire:model.blur="address.longitude" class="form-control @error('address.longitude') is-invalid @enderror">
                        @error('address.longitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">{{ __('admin.publish') }}</h5></div>
            <div class="card-body">
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" id="is_default" wire:model="address.is_default">
                    <label class="form-check-label" for="is_default">{{ __('admin.is_default') }}</label>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="icon-base ti tabler-device-floppy"></i>
                    {{ isset($userAddress) ? __('admin.update_address') : __('admin.save_address') }}
                </button>
                <a href="{{ route('admin.customers.addresses.index', $customer) }}" class="btn btn-label-secondary w-100 mt-2">{{ __('admin.cancel') }}</a>
            </div>
        </div>
    </div>
</div>
