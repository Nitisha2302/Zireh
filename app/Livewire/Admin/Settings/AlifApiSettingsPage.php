<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Setting;
use App\Support\Alif\AlifAcquiringConfig;
use App\Support\Alif\Money;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'Alif Settings'])]
class AlifApiSettingsPage extends Component
{
    public string $alif_terminal_key = '';

    public string $alif_terminal_password = '';

    public string $alif_base_url = '';

    public string $alif_gate = 'korti_milli';

    public string $alif_callback_url = '';

    public string $alif_return_url = '';

    public string $alif_min_amount = '1.00';

    public string $alif_max_amount = '20000.00';

    public string $alif_currency = 'TJS';

    public bool $passwordConfigured = false;

    public function mount(AlifAcquiringConfig $config): void
    {
        $this->alif_terminal_key = $config->terminalKey();
        $this->alif_base_url = $config->baseUrl();
        $this->alif_gate = $config->gate();
        $this->alif_callback_url = $config->callbackUrl();
        $this->alif_return_url = $config->returnUrl();
        $this->alif_min_amount = $config->minAmount()->value();
        $this->alif_max_amount = $config->maxAmount()->value();
        $this->alif_currency = $config->currency();
        $this->passwordConfigured = $config->terminalPassword() !== '';
    }

    public function save(AlifAcquiringConfig $config): void
    {
        $rules = [
            'alif_terminal_key' => ['required', 'string', 'max:64'],
            'alif_base_url' => ['required', 'url', 'max:255'],
            'alif_gate' => ['required', 'string', 'in:korti_milli,wallet'],
            'alif_callback_url' => ['required', 'url', 'max:255'],
            'alif_return_url' => ['required', 'url', 'max:255'],
            'alif_min_amount' => ['required', 'string', 'regex:/^\d{1,12}(\.\d{1,2})?$/'],
            'alif_max_amount' => ['required', 'string', 'regex:/^\d{1,12}(\.\d{1,2})?$/'],
            'alif_currency' => ['required', 'string', 'size:3', 'regex:/^[A-Za-z]{3}$/'],
        ];

        if (! $this->passwordConfigured || $this->alif_terminal_password !== '') {
            $rules['alif_terminal_password'] = ['required', 'string', 'max:255'];
        }

        $this->validate($rules);

        $min = Money::tryParse($this->alif_min_amount);
        $max = Money::tryParse($this->alif_max_amount);

        if (! $min || ! $max || $min->isGreaterThan($max)) {
            $this->addError('alif_max_amount', __('admin.alif_api_amount_range_invalid'));

            return;
        }

        $pairs = [
            AlifAcquiringConfig::SETTING_TERMINAL_KEY => trim($this->alif_terminal_key),
            AlifAcquiringConfig::SETTING_BASE_URL => rtrim(trim($this->alif_base_url), '/'),
            AlifAcquiringConfig::SETTING_GATE => trim($this->alif_gate),
            AlifAcquiringConfig::SETTING_CALLBACK_URL => trim($this->alif_callback_url),
            AlifAcquiringConfig::SETTING_RETURN_URL => trim($this->alif_return_url),
            AlifAcquiringConfig::SETTING_MIN_AMOUNT => $min->value(),
            AlifAcquiringConfig::SETTING_MAX_AMOUNT => $max->value(),
            AlifAcquiringConfig::SETTING_CURRENCY => strtoupper(trim($this->alif_currency)),
        ];

        foreach ($pairs as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        if ($this->alif_terminal_password !== '') {
            Setting::updateOrCreate(
                ['key' => AlifAcquiringConfig::SETTING_TERMINAL_PASSWORD],
                ['value' => $this->alif_terminal_password]
            );
            $this->passwordConfigured = true;
            $this->alif_terminal_password = '';
        }

        $config->refreshAfterSettingsUpdate();

        $this->alif_min_amount = $min->value();
        $this->alif_max_amount = $max->value();
        $this->alif_currency = strtoupper(trim($this->alif_currency));

        flash()->success(__('admin.alif_api_settings_saved'));
    }

    public function render()
    {
        return view('livewire.admin.settings.alif-api-settings-page')
            ->title(__('admin.alif_api_settings'));
    }
}
