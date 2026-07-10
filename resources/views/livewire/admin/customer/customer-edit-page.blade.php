<div class="container-xxl flex-grow-1 container-p-y">
    <form wire:submit="update">
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-1">{{ __('admin.customer_details') }}</h5>
                        <small class="text-body-secondary">{{ __('admin.customer_details_hint') }}</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label" for="name">{{ __('admin.full_name') }}</label>
                                <input id="name" type="text" wire:model.blur="name" class="form-control @error('name') is-invalid @enderror">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="phone">{{ __('admin.phone') }}</label>
                                <input id="phone" type="text" wire:model.blur="phone" class="form-control @error('phone') is-invalid @enderror">
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="email">{{ __('admin.email') }}</label>
                                <input id="email" type="email" wire:model.blur="email" class="form-control @error('email') is-invalid @enderror">
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="preferred_language">{{ __('admin.preferred_language_label') }}</label>
                                <select id="preferred_language" wire:model.blur="preferred_language" class="form-select @error('preferred_language') is-invalid @enderror">
                                    @foreach ($languages as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('preferred_language') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="password">{{ __('admin.new_password') }}</label>
                                <input id="password" type="password" wire:model.blur="password" class="form-control @error('password') is-invalid @enderror" placeholder="{{ __('admin.leave_blank_to_keep') }}">
                                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="password_confirmation">{{ __('admin.confirm_password') }}</label>
                                <input id="password_confirmation" type="password" wire:model.blur="password_confirmation" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="device_token">{{ __('admin.device_token') }}</label>
                                <textarea id="device_token" wire:model.blur="device_token" rows="3" class="form-control @error('device_token') is-invalid @enderror"></textarea>
                                @error('device_token') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">{{ __('admin.publish') }}</h5></div>
                    <div class="card-body">
                        <label class="form-label" for="status">{{ __('admin.status') }}</label>
                        <select id="status" wire:model.blur="status" class="form-select @error('status') is-invalid @enderror">
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror

                        <button type="submit" class="btn btn-primary w-100 mt-4">
                            <i class="icon-base ti tabler-device-floppy"></i>
                            {{ __('admin.update_customer') }}
                        </button>
                        <a href="{{ route('admin.customers.index') }}" class="btn btn-label-secondary w-100 mt-2">{{ __('admin.cancel') }}</a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h5 class="mb-0">{{ __('admin.profile_photo') }}</h5></div>
                    <div class="card-body">
                        @if ($profile_photo)
                            <img src="{{ $profile_photo->temporaryUrl() }}" class="rounded border object-fit-cover mb-3" style="width: 100%; height: 180px;" alt="Preview">
                        @elseif ($customer->profile_photo)
                            <img src="{{ app(\App\Services\FileManager::class)->url($customer->profile_photo) }}" class="rounded border object-fit-cover mb-3" style="width: 100%; height: 180px;" alt="{{ $customer->name }}">
                        @else
                            <div class="d-flex align-items-center justify-content-center bg-light rounded border mb-3" style="height: 180px;">
                                <i class="icon-base ti tabler-user" style="font-size: 56px"></i>
                            </div>
                        @endif
                        <input type="file" wire:model="profile_photo" class="form-control @error('profile_photo') is-invalid @enderror" accept="image/*">
                        @error('profile_photo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
