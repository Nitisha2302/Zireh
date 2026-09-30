<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Setting;
use App\Support\Alif\AlifProviderConfig;
use App\Support\Alif\Money;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'Alif Settings'])]
class AlifApiSettingsPage extends Component
{
    public string $alif_login = '';

    public string $alif_password = '';

    public string $alif_srv_id = '';

    public string $alif_min_amount = '1.00';

    public string $alif_max_amount = '20000.00';

    public string $alif_currency = 'TJS';

    public bool $passwordConfigured = false;

    public function mount(AlifProviderConfig $config): void
    {
        $this->alif_login = $config->login();
        $this->alif_srv_id = $config->srvId();
        $this->alif_min_amount = $config->minAmount()->value();
        $this->alif_max_amount = $config->maxAmount()->value();
        $this->alif_currency = $config->currency();
        $this->passwordConfigured = $config->password() !== '';
    }

    public function save(AlifProviderConfig $config): void
    {
        $rules = [
            'alif_login' => ['required', 'string', 'max:64'],
            'alif_srv_id' => ['nullable', 'string', 'max:64'],
            'alif_min_amount' => ['required', 'string', 'regex:/^\d{1,12}(\.\d{1,2})?$/'],
            'alif_max_amount' => ['required', 'string', 'regex:/^\d{1,12}(\.\d{1,2})?$/'],
            'alif_currency' => ['required', 'string', 'size:3', 'regex:/^[A-Za-z]{3}$/'],
        ];

        if (! $this->passwordConfigured || $this->alif_password !== '') {
            $rules['alif_password'] = ['required', 'string', 'max:255'];
        }

        $this->validate($rules);

        $min = Money::tryParse($this->alif_min_amount);
        $max = Money::tryParse($this->alif_max_amount);

        if (! $min || ! $max || $min->isGreaterThan($max)) {
            $this->addError('alif_max_amount', __('admin.alif_api_amount_range_invalid'));

            return;
        }

        Setting::updateOrCreate(
            ['key' => AlifProviderConfig::SETTING_LOGIN],
            ['value' => trim($this->alif_login)]
        );
        Setting::updateOrCreate(
            ['key' => AlifProviderConfig::SETTING_SRV_ID],
            ['value' => trim($this->alif_srv_id)]
        );
        Setting::updateOrCreate(
            ['key' => AlifProviderConfig::SETTING_MIN_AMOUNT],
            ['value' => $min->value()]
        );
        Setting::updateOrCreate(
            ['key' => AlifProviderConfig::SETTING_MAX_AMOUNT],
            ['value' => $max->value()]
        );
        Setting::updateOrCreate(
            ['key' => AlifProviderConfig::SETTING_CURRENCY],
            ['value' => strtoupper(trim($this->alif_currency))]
        );

        if ($this->alif_password !== '') {
            Setting::updateOrCreate(
                ['key' => AlifProviderConfig::SETTING_PASSWORD],
                ['value' => $this->alif_password]
            );
            $this->passwordConfigured = true;
            $this->alif_password = '';
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
