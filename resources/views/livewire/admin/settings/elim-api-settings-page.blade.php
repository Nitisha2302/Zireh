<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h4 class="mb-1">{{ __('admin.elim_api_settings') }}</h4>
                    <p class="mb-0 text-body-secondary">{{ __('admin.elim_api_settings_description') }}</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-label-info" wire:click="checkPlanAndWallet" wire:loading.attr="disabled" wire:target="checkPlanAndWallet">
                        <i class="icon-base ti tabler-Activity me-1"></i>{{ __('admin.elim_api_check_status') }}
                    </button>
                    <a href="{{ route('admin.settings.elim-api-logs.index') }}" class="btn btn-label-primary">
                        <i class="icon-base ti tabler-list-details me-1"></i>{{ __('admin.elim_api_view_logs') }}
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if ($planStatus !== null || $purchasingWallet !== null)
                <div class="row g-3 mb-4">
                    @if ($planStatus !== null)
                        <div class="col-md-6">
                            <div class="alert alert-info mb-0">
                                <h6 class="alert-heading mb-1">{{ __('admin.elim_plan_status') }}</h6>
                                <div class="small">
                                    <strong>Plan:</strong> {{ $planStatus['plan_name'] ?? $planStatus['plan'] ?? '—' }} |
                                    <strong>Status:</strong> {{ $planStatus['status'] ?? ($planStatus['is_active'] ?? false ? 'active' : '—') }}
                                </div>
                            </div>
                        </div>
                    @endif
                    @if ($purchasingWallet !== null)
                        <div class="col-md-6">
                            <div class="alert alert-success mb-0">
                                <h6 class="alert-heading mb-1">{{ __('admin.elim_purchasing_wallet') }}</h6>
                                <div class="small">
                                    <strong>Available:</strong> {{ $purchasingWallet['available_balance'] ?? $purchasingWallet['balance'] ?? '0.00' }} {{ $purchasingWallet['currency'] ?? 'CNY' }} |
                                    <strong>Pending:</strong> {{ $purchasingWallet['pending_amount'] ?? '0.00' }}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <form wire:submit="save">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.base_url') }}</label>
                        <input type="url" class="form-control @error('elim_base_url') is-invalid @enderror" wire:model="elim_base_url">
                        @error('elim_base_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.elim_api_key') }}</label>
                        <input
                            type="password"
                            class="form-control @error('elim_api_key') is-invalid @enderror"
                            wire:model="elim_api_key"
                            placeholder="{{ $apiKeyConfigured ? __('admin.elim_api_password_placeholder') : 'elim_live_...' }}"
                            autocomplete="new-password"
                        >
                        @error('elim_api_key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">{{ __('admin.elim_api_key_hint') }}</div>
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
                    <div class="col-md-4">
                        <label class="form-label">{{ __('admin.elim_payment_mode') }}</label>
                        <select class="form-select @error('elim_payment_mode') is-invalid @enderror" wire:model="elim_payment_mode">
                            <option value="confirm">{{ __('admin.elim_payment_mode_confirm') }}</option>
                            <option value="request">{{ __('admin.elim_payment_mode_request') }}</option>
                        </select>
                        @error('elim_payment_mode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('admin.elim_webhook_id') }}</label>
                        <input type="text" class="form-control @error('elim_webhook_id') is-invalid @enderror" wire:model="elim_webhook_id" placeholder="wh_...">
                        @error('elim_webhook_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('admin.elim_webhook_secret') }}</label>
                        <input
                            type="password"
                            class="form-control @error('elim_webhook_secret') is-invalid @enderror"
                            wire:model="elim_webhook_secret"
                            placeholder="{{ $webhookSecretConfigured ? __('admin.elim_api_password_placeholder') : 'whsec_...' }}"
                            autocomplete="new-password"
                        >
                        @error('elim_webhook_secret') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">Endpoint: <code>{{ route('webhooks.elim') }}</code></div>
                    </div>
                    <div class="col-12">
                        <div class="card bg-label-warning border-0">
                            <div class="card-body">
                                <div class="form-check form-switch mb-2">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        id="demo_mode_enabled"
                                        wire:model="demo_mode_enabled"
                                    >
                                    <label class="form-check-label fw-semibold" for="demo_mode_enabled">
                                        {{ __('admin.elim_demo_mode') }}
                                    </label>
                                </div>
                                <p class="mb-0 text-body-secondary small">{{ __('admin.elim_demo_mode_description') }}</p>
                            </div>
                        </div>
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
                    <button type="button" class="btn btn-label-info" wire:click="registerWebhook" wire:loading.attr="disabled" wire:target="registerWebhook">
                        {{ __('admin.elim_register_webhook') }}
                    </button>
                    @if ($elim_webhook_id !== '')
                        <button type="button" class="btn btn-label-success" wire:click="testWebhook" wire:loading.attr="disabled" wire:target="testWebhook">
                            {{ __('admin.elim_test_webhook') }}
                        </button>
                        <button type="button" class="btn btn-label-warning" wire:click="rotateWebhookSecret" wire:loading.attr="disabled" wire:target="rotateWebhookSecret">
                            {{ __('admin.elim_rotate_webhook_secret') }}
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
