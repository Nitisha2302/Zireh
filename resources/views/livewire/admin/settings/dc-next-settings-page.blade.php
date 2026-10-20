<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header">
            <div>
                <h4 class="mb-1">{{ __('admin.dc_next_settings') }}</h4>
                <p class="mb-0 text-body-secondary">{{ __('admin.dc_next_settings_description') }}</p>
            </div>
        </div>
        <div class="card-body">
            <form wire:submit="save">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.dc_next_merchant') }}</label>
                        <input type="text" class="form-control @error('dc_next_merchant') is-invalid @enderror" wire:model="dc_next_merchant" autocomplete="off">
                        @error('dc_next_merchant') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.dc_next_secret') }}</label>
                        <input
                            type="password"
                            class="form-control @error('dc_next_secret') is-invalid @enderror"
                            wire:model="dc_next_secret"
                            placeholder="{{ $secretConfigured ? __('admin.dc_next_secret_placeholder') : '' }}"
                            autocomplete="new-password"
                        >
                        @error('dc_next_secret') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @if ($secretConfigured)
                            <div class="form-text">{{ __('admin.dc_next_secret_hint') }}</div>
                        @endif
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">{{ __('admin.dc_next_callback_url') }}</label>
                        <input type="url" class="form-control @error('dc_next_callback_url') is-invalid @enderror" wire:model="dc_next_callback_url" autocomplete="off">
                        @error('dc_next_callback_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.dc_next_create_url') }}</label>
                        <input type="url" class="form-control @error('dc_next_create_url') is-invalid @enderror" wire:model="dc_next_create_url" autocomplete="off">
                        @error('dc_next_create_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.dc_next_check_url') }}</label>
                        <input type="url" class="form-control @error('dc_next_check_url') is-invalid @enderror" wire:model="dc_next_check_url" autocomplete="off">
                        @error('dc_next_check_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('admin.dc_next_min_amount') }}</label>
                        <input type="text" class="form-control @error('dc_next_min_amount') is-invalid @enderror" wire:model="dc_next_min_amount" autocomplete="off">
                        @error('dc_next_min_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('admin.dc_next_max_amount') }}</label>
                        <input type="text" class="form-control @error('dc_next_max_amount') is-invalid @enderror" wire:model="dc_next_max_amount" autocomplete="off">
                        @error('dc_next_max_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('admin.dc_next_currency') }}</label>
                        <input type="text" class="form-control @error('dc_next_currency') is-invalid @enderror" wire:model="dc_next_currency" maxlength="3" autocomplete="off">
                        @error('dc_next_currency') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('admin.dc_next_exp_minutes') }}</label>
                        <input type="number" min="1" max="1440" class="form-control @error('dc_next_exp_minutes') is-invalid @enderror" wire:model="dc_next_exp_minutes" autocomplete="off">
                        @error('dc_next_exp_minutes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                        {{ __('admin.save_dc_next_settings') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
