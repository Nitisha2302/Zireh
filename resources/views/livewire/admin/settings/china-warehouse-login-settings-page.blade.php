<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-1">{{ __('admin.china_warehouse_login_settings') }}</h4>
            <p class="mb-0 text-body-secondary">{{ __('admin.china_warehouse_login_settings_hint') }}</p>
        </div>
        <div class="card-body">
            <form wire:submit="save">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.login_username') }}</label>
                        <input type="text" class="form-control @error('login_username') is-invalid @enderror" wire:model="login_username">
                        @error('login_username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.login_email') }}</label>
                        <input type="email" class="form-control @error('login_email') is-invalid @enderror" wire:model="login_email">
                        @error('login_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.new_password') }}</label>
                        <input type="password" class="form-control @error('login_password') is-invalid @enderror" wire:model="login_password" placeholder="{{ __('admin.leave_blank_to_keep') }}">
                        @error('login_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.confirm_password') }}</label>
                        <input type="password" class="form-control" wire:model="login_password_confirmation">
                    </div>
                    <div class="col-12">
                        <p class="text-body-secondary small mb-0">{{ __('admin.china_warehouse_login_url_hint', ['url' => url('/china/login')]) }}</p>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove>{{ __('admin.save') }}</span>
                            <span wire:loading>{{ __('admin.saving') }}...</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
