<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">{{ __('admin.my_profile') }}</h5></div>
                <div class="card-body">
                    <p class="mb-2"><strong>{{ __('admin.name') }}:</strong> {{ $admin->name }}</p>
                    <p class="mb-2"><strong>{{ __('admin.username') }}:</strong> {{ $admin->username }}</p>
                    <p class="mb-2"><strong>{{ __('admin.email') }}:</strong> {{ $admin->email }}</p>
                    <p class="mb-2"><strong>{{ __('admin.role') }}:</strong> {{ $roleLabel }}</p>
                    @if ($admin->warehouse)
                        <p class="mb-0"><strong>{{ __('admin.warehouse') }}:</strong> {{ $admin->warehouse->warehouse_name }}</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">{{ __('admin.change_password') }}</h5></div>
                <div class="card-body">
                    <form wire:submit="updatePassword" class="row g-3">
                        <div class="col-12">
                            <label class="form-label">{{ __('admin.current_password') }}</label>
                            <input type="password" class="form-control @error('currentPassword') is-invalid @enderror" wire:model="currentPassword">
                            @error('currentPassword') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('admin.new_password') }}</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" wire:model="password">
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('admin.confirm_password') }}</label>
                            <input type="password" class="form-control" wire:model="password_confirmation">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="updatePassword">{{ __('admin.update_password') }}</span>
                                <span wire:loading wire:target="updatePassword">{{ __('admin.saving') }}...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
