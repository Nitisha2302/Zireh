<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h4 class="mb-1">{{ __('admin.alif_api_settings') }}</h4>
                    <p class="mb-0 text-body-secondary">{{ __('admin.alif_api_settings_description') }}</p>
                </div>
                <a href="{{ route('admin.settings.alif-api-logs.index') }}" class="btn btn-label-primary">
                    <i class="icon-base ti tabler-list-details me-1"></i>{{ __('admin.alif_api_view_logs') }}
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="alert alert-info" role="alert">
                <div class="fw-semibold mb-1">{{ __('admin.alif_api_endpoint_label') }}</div>
                <code>{{ url('/api/alif') }}</code>
                <div class="small mt-2 mb-0">{{ __('admin.alif_api_endpoint_hint') }}</div>
            </div>
            <form wire:submit="save">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.alif_api_login') }}</label>
                        <input type="text" class="form-control @error('alif_login') is-invalid @enderror" wire:model="alif_login" autocomplete="off">
                        @error('alif_login') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.alif_api_password') }}</label>
                        <input
                            type="password"
                            class="form-control @error('alif_password') is-invalid @enderror"
                            wire:model="alif_password"
                            placeholder="{{ $passwordConfigured ? __('admin.alif_api_password_placeholder') : '' }}"
                            autocomplete="new-password"
                        >
                        @error('alif_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @if ($passwordConfigured)
                            <div class="form-text">{{ __('admin.alif_api_password_hint') }}</div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.alif_api_srv_id') }}</label>
                        <input type="text" class="form-control @error('alif_srv_id') is-invalid @enderror" wire:model="alif_srv_id" autocomplete="off">
                        @error('alif_srv_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">{{ __('admin.alif_api_srv_id_hint') }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.alif_api_currency') }}</label>
                        <input type="text" class="form-control @error('alif_currency') is-invalid @enderror" wire:model="alif_currency" maxlength="3" autocomplete="off">
                        @error('alif_currency') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.alif_api_min_amount') }}</label>
                        <input type="text" class="form-control @error('alif_min_amount') is-invalid @enderror" wire:model="alif_min_amount" autocomplete="off">
                        @error('alif_min_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.alif_api_max_amount') }}</label>
                        <input type="text" class="form-control @error('alif_max_amount') is-invalid @enderror" wire:model="alif_max_amount" autocomplete="off">
                        @error('alif_max_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                        {{ __('admin.save_alif_api_settings') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
