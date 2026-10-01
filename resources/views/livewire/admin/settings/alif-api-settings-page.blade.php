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
            <form wire:submit="save">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.alif_api_terminal_key') }}</label>
                        <input type="text" class="form-control @error('alif_terminal_key') is-invalid @enderror" wire:model="alif_terminal_key" autocomplete="off">
                        @error('alif_terminal_key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.alif_api_terminal_password') }}</label>
                        <input
                            type="password"
                            class="form-control @error('alif_terminal_password') is-invalid @enderror"
                            wire:model="alif_terminal_password"
                            placeholder="{{ $passwordConfigured ? __('admin.alif_api_password_placeholder') : '' }}"
                            autocomplete="new-password"
                        >
                        @error('alif_terminal_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @if ($passwordConfigured)
                            <div class="form-text">{{ __('admin.alif_api_password_hint') }}</div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.alif_api_base_url') }}</label>
                        <input type="url" class="form-control @error('alif_base_url') is-invalid @enderror" wire:model="alif_base_url" autocomplete="off">
                        @error('alif_base_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">{{ __('admin.alif_api_base_url_hint') }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.alif_api_gate') }}</label>
                        <select class="form-select @error('alif_gate') is-invalid @enderror" wire:model="alif_gate">
                            <option value="korti_milli">korti_milli</option>
                            <option value="wallet">wallet</option>
                        </select>
                        @error('alif_gate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">{{ __('admin.alif_api_gate_hint') }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.alif_api_callback_url') }}</label>
                        <input type="url" class="form-control @error('alif_callback_url') is-invalid @enderror" wire:model="alif_callback_url" autocomplete="off">
                        @error('alif_callback_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.alif_api_return_url') }}</label>
                        <input type="url" class="form-control @error('alif_return_url') is-invalid @enderror" wire:model="alif_return_url" autocomplete="off">
                        @error('alif_return_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('admin.alif_api_min_amount') }}</label>
                        <input type="text" class="form-control @error('alif_min_amount') is-invalid @enderror" wire:model="alif_min_amount" autocomplete="off">
                        @error('alif_min_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('admin.alif_api_max_amount') }}</label>
                        <input type="text" class="form-control @error('alif_max_amount') is-invalid @enderror" wire:model="alif_max_amount" autocomplete="off">
                        @error('alif_max_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('admin.alif_api_currency') }}</label>
                        <input type="text" class="form-control @error('alif_currency') is-invalid @enderror" wire:model="alif_currency" maxlength="3" autocomplete="off">
                        @error('alif_currency') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
