<?php

namespace App\Livewire\Admin\Settings;

use App\Exceptions\Elim\ElimAuthenticationException;
use App\Helpers\SettingHelper;
use App\Models\Setting;
use App\Services\Elim\ElimAuthService;
use App\Support\Elim\ElimApiConfig;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'Elim API Settings'])]
class ElimApiSettingsPage extends Component
{
    public string $elim_base_url = '';

    public string $elim_email = '';

    public string $elim_password = '';

    public bool $passwordConfigured = false;

    public bool $demo_mode_enabled = false;

    public function mount(): void
    {
        $this->elim_base_url = (string) SettingHelper::get(
            ElimApiConfig::SETTING_BASE_URL,
            config('services.elim.base_url', 'https://openapi.elim.asia')
        );
        $this->elim_email = (string) SettingHelper::get(
            ElimApiConfig::SETTING_EMAIL,
            config('services.elim.email', '')
        );
        $this->passwordConfigured = (bool) SettingHelper::get(
            ElimApiConfig::SETTING_PASSWORD,
            config('services.elim.password')
        );
        $this->demo_mode_enabled = app(ElimApiConfig::class)->demoModeEnabled();
    }

    public function save(): void
    {
        $rules = [
            'elim_base_url' => ['required', 'url'],
            'elim_email' => ['required', 'email'],
        ];

        if (! $this->passwordConfigured || $this->elim_password !== '') {
            $rules['elim_password'] = ['required', 'string'];
        }

        $this->validate($rules);

        Setting::updateOrCreate(
            ['key' => ElimApiConfig::SETTING_BASE_URL],
            ['value' => $this->elim_base_url]
        );
        Setting::updateOrCreate(
            ['key' => ElimApiConfig::SETTING_EMAIL],
            ['value' => $this->elim_email]
        );

        if ($this->elim_password !== '') {
            Setting::updateOrCreate(
                ['key' => ElimApiConfig::SETTING_PASSWORD],
                ['value' => $this->elim_password]
            );
            $this->passwordConfigured = true;
            $this->elim_password = '';
        }

        Setting::updateOrCreate(
            ['key' => ElimApiConfig::SETTING_DEMO_MODE],
            ['value' => $this->demo_mode_enabled ? '1' : '0']
        );

        app(ElimApiConfig::class)->refreshAfterSettingsUpdate();

        flash()->success(__('admin.elim_api_settings_saved'));
    }

    public function testConnection(ElimAuthService $authService): void
    {
        $this->validate([
            'elim_base_url' => ['required', 'url'],
            'elim_email' => ['required', 'email'],
        ]);

        $password = $this->resolvePassword();

        if ($password === null || $password === '') {
            $this->addError('elim_password', __('admin.elim_api_password_required_for_test'));

            return;
        }

        try {
            $authService->testCredentials($this->elim_base_url, $this->elim_email, $password);
        } catch (ElimAuthenticationException $exception) {
            flash()->error($exception->getMessage());

            return;
        }

        flash()->success(__('admin.elim_api_test_success'));
    }

    protected function resolvePassword(): ?string
    {
        if ($this->elim_password !== '') {
            return $this->elim_password;
        }

        $stored = SettingHelper::get(ElimApiConfig::SETTING_PASSWORD, config('services.elim.password'));

        return is_string($stored) && $stored !== '' ? $stored : null;
    }

    public function render()
    {
        return view('livewire.admin.settings.elim-api-settings-page')
            ->title(__('admin.elim_api_settings'));
    }
}
