<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-1">{{ __('admin.warehouse_basic_info') }}</h5>
                <small class="text-body-secondary">{{ __('admin.warehouse_basic_info_hint') }}</small>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label" for="warehouse_name">{{ __('admin.warehouse_name') }}</label>
                        <input id="warehouse_name" type="text" wire:model.blur="warehouse_name" class="form-control @error('warehouse_name') is-invalid @enderror">
                        @error('warehouse_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="warehouse_code">{{ __('admin.warehouse_code') }}</label>
                        <input id="warehouse_code" type="text" wire:model.blur="warehouse_code" class="form-control @error('warehouse_code') is-invalid @enderror" placeholder="DUS-TJ-01">
                        <small class="text-body-secondary">{{ __('admin.warehouse_code_hint') }}</small>
                        @error('warehouse_code') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="contact_person">{{ __('admin.contact_person') }}</label>
                        <input id="contact_person" type="text" wire:model.blur="contact_person" class="form-control @error('contact_person') is-invalid @enderror">
                        @error('contact_person') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="contact_number">{{ __('admin.contact_number') }}</label>
                        <input id="contact_number" type="text" wire:model.blur="contact_number" class="form-control @error('contact_number') is-invalid @enderror">
                        @error('contact_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">{{ __('admin.email') }}</label>
                        <input id="email" type="email" wire:model.blur="email" class="form-control @error('email') is-invalid @enderror">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-1">{{ __('admin.warehouse_panel_login') }}</h5>
                <small class="text-body-secondary">{{ __('admin.warehouse_panel_login_hint') }}</small>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label" for="login_username">{{ __('admin.login_username') }}</label>
                        <input id="login_username" type="text" wire:model.blur="login_username" class="form-control @error('login_username') is-invalid @enderror">
                        @error('login_username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="login_email">{{ __('admin.login_email') }}</label>
                        <input id="login_email" type="email" wire:model.blur="login_email" class="form-control @error('login_email') is-invalid @enderror">
                        @error('login_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="login_password">{{ ($isEdit ?? false) ? __('admin.new_password') : __('admin.password') }}</label>
                        <input id="login_password" type="password" wire:model="login_password" class="form-control @error('login_password') is-invalid @enderror" @if($isEdit ?? false) placeholder="{{ __('admin.leave_blank_to_keep') }}" @endif>
                        @error('login_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="login_password_confirmation">{{ __('admin.confirm_password') }}</label>
                        <input id="login_password_confirmation" type="password" wire:model="login_password_confirmation" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-1">{{ __('admin.warehouse_location') }}</h5>
                <small class="text-body-secondary">{{ __('admin.warehouse_location_hint') }}</small>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label" for="country">{{ __('admin.country') }}</label>
                        <input id="country" type="text" wire:model.blur="country" class="form-control @error('country') is-invalid @enderror">
                        @error('country') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="state">{{ __('admin.state_region') }}</label>
                        <input id="state" type="text" wire:model.blur="state" class="form-control @error('state') is-invalid @enderror">
                        @error('state') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="city">{{ __('admin.city') }}</label>
                        <input id="city" type="text" wire:model.blur="city" class="form-control @error('city') is-invalid @enderror">
                        @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="address">{{ __('admin.full_address') }}</label>
                        <textarea id="address" rows="3" wire:model.blur="address" class="form-control @error('address') is-invalid @enderror"></textarea>
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="postal_code">{{ __('admin.postal_code') }}</label>
                        <input id="postal_code" type="text" wire:model.blur="postal_code" class="form-control @error('postal_code') is-invalid @enderror">
                        @error('postal_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="latitude">{{ __('admin.latitude') }}</label>
                        <input id="latitude" type="number" step="any" wire:model.blur="latitude" class="form-control @error('latitude') is-invalid @enderror" placeholder="38.5598">
                        @error('latitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="longitude">{{ __('admin.longitude') }}</label>
                        <input id="longitude" type="number" step="any" wire:model.blur="longitude" class="form-control @error('longitude') is-invalid @enderror" placeholder="68.7870">
                        @error('longitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="mb-0">{{ __('admin.notes') }}</h5></div>
            <div class="card-body">
                <textarea id="notes" rows="4" wire:model.blur="notes" class="form-control @error('notes') is-invalid @enderror" placeholder="{{ __('admin.warehouse_notes_placeholder') }}"></textarea>
                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">{{ __('admin.warehouse_image') }}</h5></div>
            <div class="card-body">
                <input type="file" accept="image/*" wire:model="image" class="form-control @error('image') is-invalid @enderror">
                @error('image') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                <div wire:loading wire:target="image" class="small text-body-secondary mt-2">{{ __('admin.loading') }}...</div>
                <div class="mt-3">
                    @if ($image)
                        <img src="{{ $image->temporaryUrl() }}" alt="Preview" class="img-fluid rounded border">
                    @elseif (isset($warehouse) && $warehouse->image)
                        <img src="{{ app(\App\Services\FileManager::class)->url($warehouse->image) }}" alt="{{ $warehouse->warehouse_name }}" class="img-fluid rounded border">
                    @else
                        <div class="border rounded d-flex align-items-center justify-content-center bg-label-secondary" style="height: 180px;">
                            <i class="icon-base ti tabler-building-warehouse" style="font-size: 3rem;"></i>
                        </div>
                    @endif
                </div>
                <small class="text-body-secondary d-block mt-2">{{ __('admin.warehouse_image_hint') }}</small>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="mb-0">{{ __('admin.publish') }}</h5></div>
            <div class="card-body">
                <div class="mb-4">
                    <label class="form-label" for="status">{{ __('admin.status') }}</label>
                    <select id="status" wire:model="status" class="form-select @error('status') is-invalid @enderror">
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="icon-base ti tabler-device-floppy"></i>
                    {{ $submitLabel }}
                </button>
                <a href="{{ $cancelUrl }}" class="btn btn-label-secondary w-100 mt-2">{{ __('admin.cancel') }}</a>
            </div>
        </div>
    </div>
</div>
