<?php

namespace App\Livewire\Admin\Settings;

use App\Models\CurrencyExchangeRate;
use App\Services\Currency\CurrencyExchangeService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'Currency Exchange Rate'])]
class CurrencyExchangeSettingsPage extends Component
{
    public string $exchangeRate = '';

    public bool $autoRefreshEnabled = false;

    public int $refreshIntervalHours = 1;

    public ?string $lastSyncedAt = null;

    public function mount(CurrencyExchangeService $currencyExchangeService): void
    {
        $config = $currencyExchangeService->getActive();

        $this->exchangeRate = (string) $config->exchange_rate;
        $this->autoRefreshEnabled = $config->auto_refresh_enabled;
        $this->refreshIntervalHours = $config->refresh_interval_hours;
        $this->lastSyncedAt = $config->last_synced_at?->toDateTimeString();
    }

    public function refreshRate(CurrencyExchangeService $currencyExchangeService): void
    {
        try {
            $config = $currencyExchangeService->refresh(manual: true);
        } catch (ValidationException $exception) {
            flash()->error(collect($exception->errors())->flatten()->first() ?? 'Unable to refresh exchange rate.');

            return;
        }

        $this->syncFromConfig($config);
        flash()->success(__('admin.exchange_rate_refreshed'));
    }

    public function save(CurrencyExchangeService $currencyExchangeService): void
    {
        $this->validate([
            'exchangeRate' => ['required', 'numeric', 'gt:0'],
            'autoRefreshEnabled' => ['boolean'],
            'refreshIntervalHours' => ['required', 'integer', Rule::in(CurrencyExchangeRate::INTERVAL_OPTIONS)],
        ]);

        $config = $currencyExchangeService->saveSettings([
            'exchange_rate' => (float) $this->exchangeRate,
            'auto_refresh_enabled' => $this->autoRefreshEnabled,
            'refresh_interval_hours' => $this->refreshIntervalHours,
        ]);

        $this->syncFromConfig($config);
        flash()->success(__('admin.exchange_rate_settings_saved'));
    }

    protected function syncFromConfig(CurrencyExchangeRate $config): void
    {
        $this->exchangeRate = (string) $config->exchange_rate;
        $this->autoRefreshEnabled = $config->auto_refresh_enabled;
        $this->refreshIntervalHours = $config->refresh_interval_hours;
        $this->lastSyncedAt = $config->last_synced_at?->toDateTimeString();
    }

    public function render()
    {
        return view('livewire.admin.settings.currency-exchange-settings-page', [
            'intervalOptions' => CurrencyExchangeRate::INTERVAL_OPTIONS,
        ])->title(__('admin.currency_exchange_settings'));
    }
}
