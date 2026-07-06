<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h4 class="mb-1">{{ __('admin.elim_api_settings') }}</h4>
                    <p class="mb-0 text-body-secondary">{{ __('admin.elim_api_settings_description') }}</p>
                </div>
                <a href="{{ route('admin.settings.elim-api-logs.index') }}" class="btn btn-label-primary">
                    <i class="icon-base ti tabler-list-details me-1"></i>{{ __('admin.elim_api_view_logs') }}
                </a>
            </div>
        </div>
        <div class="card-body">
            <form wire:submit="save">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.base_url') }}</label>
                        <input type="url" class="form-control @error('elim_base_url') is-invalid @enderror" wire:model="elim_base_url">
                        @error('elim_base_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.elim_api_email') }}</label>
                        <input type="email" class="form-control @error('elim_email') is-invalid @enderror" wire:model="elim_email" autocomplete="off">
                        @error('elim_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.elim_api_password') }}</label>
                        <input
                            type="password"
                            class="form-control @error('elim_password') is-invalid @enderror"
                            wire:model="elim_password"
                            placeholder="{{ $passwordConfigured ? __('admin.elim_api_password_placeholder') : '' }}"
                            autocomplete="new-password"
                        >
                        @error('elim_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @if ($passwordConfigured)
                            <div class="form-text">{{ __('admin.elim_api_password_hint') }}</div>
                        @endif
                    </div>
                </div>
                <div class="mt-4 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save,testConnection">
                        {{ __('admin.save_elim_api_settings') }}
                    </button>
                    <button type="button" class="btn btn-label-secondary" wire:click="testConnection" wire:loading.attr="disabled" wire:target="testConnection">
                        <span wire:loading.remove wire:target="testConnection">{{ __('admin.test_elim_api_connection') }}</span>
                        <span wire:loading wire:target="testConnection">{{ __('admin.test_elim_api_connection_loading') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
