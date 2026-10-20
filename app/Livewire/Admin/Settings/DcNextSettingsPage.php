<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Setting;
use App\Support\Alif\Money;
use App\Support\DcNext\DcNextConfig;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'DC NEXT Settings'])]
class DcNextSettingsPage extends Component
{
    public string $dc_next_merchant = '';

    public string $dc_next_secret = '';

    public string $dc_next_callback_url = '';

    public string $dc_next_create_url = '';

    public string $dc_next_check_url = '';

    public string $dc_next_currency = 'TJS';

    public int $dc_next_exp_minutes = 40;

    public string $dc_next_min_amount = '1.00';

    public string $dc_next_max_amount = '20000.00';

    public bool $secretConfigured = false;

    public function mount(DcNextConfig $config): void
    {
        $this->dc_next_merchant = $config->merchant();
        $this->dc_next_callback_url = $config->callbackUrl();
        $this->dc_next_create_url = $config->createUrl();
        $this->dc_next_check_url = $config->checkUrl();
        $this->dc_next_currency = $config->currency();
        $this->dc_next_exp_minutes = $config->expMinutes();
        $this->dc_next_min_amount = $config->minAmount()->value();
        $this->dc_next_max_amount = $config->maxAmount()->value();
        $this->secretConfigured = $config->secret() !== '';
    }

    public function save(DcNextConfig $config): void
    {
        $rules = [
            'dc_next_merchant' => ['required', 'string', 'max:64'],
            'dc_next_callback_url' => ['required', 'url', 'max:255'],
            'dc_next_create_url' => ['required', 'url', 'max:255'],
            'dc_next_check_url' => ['required', 'url', 'max:255'],
            'dc_next_currency' => ['required', 'string', 'size:3', 'regex:/^[A-Za-z]{3}$/'],
            'dc_next_exp_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'dc_next_min_amount' => ['required', 'string', 'regex:/^\d{1,12}(\.\d{1,2})?$/'],
            'dc_next_max_amount' => ['required', 'string', 'regex:/^\d{1,12}(\.\d{1,2})?$/'],
        ];

        if (! $this->secretConfigured || $this->dc_next_secret !== '') {
            $rules['dc_next_secret'] = ['required', 'string', 'max:255'];
        }

        $this->validate($rules);

        $min = Money::tryParse($this->dc_next_min_amount);
        $max = Money::tryParse($this->dc_next_max_amount);

        if (! $min || ! $max || $min->isGreaterThan($max)) {
            $this->addError('dc_next_max_amount', __('admin.dc_next_amount_range_invalid'));

            return;
        }

        $pairs = [
            DcNextConfig::SETTING_MERCHANT => trim($this->dc_next_merchant),
            DcNextConfig::SETTING_CALLBACK_URL => trim($this->dc_next_callback_url),
            DcNextConfig::SETTING_CREATE_URL => trim($this->dc_next_create_url),
            DcNextConfig::SETTING_CHECK_URL => trim($this->dc_next_check_url),
            DcNextConfig::SETTING_CURRENCY => strtoupper(trim($this->dc_next_currency)),
            DcNextConfig::SETTING_EXP_MINUTES => (string) max(1, (int) $this->dc_next_exp_minutes),
            DcNextConfig::SETTING_MIN_AMOUNT => $min->value(),
            DcNextConfig::SETTING_MAX_AMOUNT => $max->value(),
        ];

        foreach ($pairs as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        if ($this->dc_next_secret !== '') {
            Setting::updateOrCreate(
                ['key' => DcNextConfig::SETTING_SECRET],
                ['value' => $this->dc_next_secret]
            );
            $this->secretConfigured = true;
            $this->dc_next_secret = '';
        }

        $config->refreshAfterSettingsUpdate();

        $this->dc_next_min_amount = $min->value();
        $this->dc_next_max_amount = $max->value();
        $this->dc_next_currency = strtoupper(trim($this->dc_next_currency));

        flash()->success(__('admin.dc_next_settings_saved'));
    }

    public function render()
    {
        return view('livewire.admin.settings.dc-next-settings-page')
            ->title(__('admin.dc_next_settings'));
    }
}
