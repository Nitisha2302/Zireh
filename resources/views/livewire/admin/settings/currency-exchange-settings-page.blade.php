<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-1">{{ __('admin.currency_exchange_settings') }}</h4>
                    <p class="mb-0 text-body-secondary">{{ __('admin.currency_exchange_settings_description') }}</p>
                </div>
                <div class="card-body">
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('admin.from_currency') }}</label>
                            <div class="form-control bg-label-secondary">
                                Chinese Yuan (¥ / {{ \App\Models\CurrencyExchangeRate::FROM_CURRENCY }})
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('admin.to_currency') }}</label>
                            <div class="form-control bg-label-secondary">
                                Tajikistani Somoni ({{ \App\Models\CurrencyExchangeRate::TO_CURRENCY }})
                            </div>
                        </div>
                    </div>

                    <form wire:submit="save">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label" for="exchangeRate">{{ __('admin.current_exchange_rate') }}</label>
                                <input id="exchangeRate" type="number" step="0.000001" min="0.000001"
                                    class="form-control @error('exchangeRate') is-invalid @enderror"
                                    wire:model="exchangeRate">
                                @error('exchangeRate')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="text-body-secondary">{{ __('admin.exchange_rate_hint') }}</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('admin.last_updated') }}</label>
                                <div class="form-control bg-label-secondary">
                                    {{ $lastSyncedAt ? \Illuminate\Support\Carbon::parse($lastSyncedAt)->format('d M Y H:i') : __('admin.not_available') }}
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="autoRefreshEnabled" wire:model.live="autoRefreshEnabled">
                                    <label class="form-check-label" for="autoRefreshEnabled">{{ __('admin.enable_auto_exchange_rate') }}</label>
                                </div>
                            </div>

                            @if ($autoRefreshEnabled)
                                <div class="col-md-6">
                                    <label class="form-label" for="refreshIntervalHours">{{ __('admin.refresh_interval') }}</label>
                                    <select id="refreshIntervalHours" class="form-select @error('refreshIntervalHours') is-invalid @enderror" wire:model="refreshIntervalHours">
                                        @foreach ($intervalOptions as $hours)
                                            <option value="{{ $hours }}">{{ __('admin.every_n_hours', ['hours' => $hours]) }}</option>
                                        @endforeach
                                    </select>
                                    @error('refreshIntervalHours')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif
                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-4">
                            <button type="button" class="btn btn-label-primary" wire:click="refreshRate" wire:loading.attr="disabled" wire:target="refreshRate">
                                <span wire:loading.remove wire:target="refreshRate">
                                    <i class="icon-base ti tabler-refresh me-1"></i>{{ __('admin.refresh_exchange_rate') }}
                                </span>
                                <span wire:loading wire:target="refreshRate">
                                    <span class="spinner-border spinner-border-sm me-1"></span>{{ __('admin.refreshing_exchange_rate') }}
                                </span>
                            </button>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                                <i class="icon-base ti tabler-device-floppy me-1"></i>{{ __('admin.save_exchange_rate_settings') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">{{ __('admin.exchange_rate_info') }}</h5></div>
                <div class="card-body">
                    <p class="text-body-secondary">{{ __('admin.exchange_rate_info_body') }}</p>
                    <ul class="mb-0 ps-3">
                        <li class="mb-2">{{ __('admin.exchange_rate_info_products') }}</li>
                        <li class="mb-2">{{ __('admin.exchange_rate_info_cart') }}</li>
                        <li>{{ __('admin.exchange_rate_info_scheduler') }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
