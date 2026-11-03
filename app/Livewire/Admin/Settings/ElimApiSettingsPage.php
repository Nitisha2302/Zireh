<?php

namespace App\Livewire\Admin\Settings;

use App\Exceptions\Elim\ElimAuthenticationException;
use App\Helpers\SettingHelper;
use App\Models\Setting;
use App\Services\Elim\ElimAuthService;
use App\Services\Elim\ElimOrderApiService;
use App\Services\Elim\ElimWebhookService;
use App\Support\Elim\ElimApiConfig;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts::admin', ['title' => 'Elim API Settings'])]
class ElimApiSettingsPage extends Component
{
    public string $elim_base_url = '';

    public string $elim_email = '';

    public string $elim_password = '';

    public string $elim_api_key = '';

    public string $elim_webhook_secret = '';

    public string $elim_webhook_id = '';

    public string $elim_payment_mode = ElimApiConfig::PAYMENT_MODE_CONFIRM;

    public bool $passwordConfigured = false;

    public bool $apiKeyConfigured = false;

    public bool $webhookSecretConfigured = false;

    public bool $demo_mode_enabled = false;

    /** @var array<string, mixed>|null */
    public ?array $planStatus = null;

    /** @var array<string, mixed>|null */
    public ?array $purchasingWallet = null;

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
        $this->apiKeyConfigured = (bool) SettingHelper::get(
            ElimApiConfig::SETTING_API_KEY,
            config('services.elim.api_key')
        );
        $this->webhookSecretConfigured = (bool) SettingHelper::get(
            ElimApiConfig::SETTING_WEBHOOK_SECRET,
            config('services.elim.webhook_secret')
        );
        $this->elim_webhook_id = (string) SettingHelper::get(
            ElimApiConfig::SETTING_WEBHOOK_ID,
            config('services.elim.webhook_id', '')
        );
        $this->elim_payment_mode = app(ElimApiConfig::class)->paymentMode();
        $this->demo_mode_enabled = app(ElimApiConfig::class)->demoModeEnabled();
    }

    public function save(): void
    {
        $hasApiKey = $this->apiKeyConfigured || trim($this->elim_api_key) !== '';

        $rules = [
            'elim_base_url' => ['required', 'url'],
            'elim_email' => $hasApiKey ? ['nullable', 'email'] : ['required', 'email'],
            'elim_payment_mode' => ['required', 'in:confirm,request'],
        ];

        if (! $hasApiKey && (! $this->passwordConfigured || $this->elim_password !== '')) {
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

        if (trim($this->elim_api_key) !== '') {
            Setting::updateOrCreate(
                ['key' => ElimApiConfig::SETTING_API_KEY],
                ['value' => trim($this->elim_api_key)]
            );
            $this->apiKeyConfigured = true;
            $this->elim_api_key = '';
        }

        if (trim($this->elim_webhook_secret) !== '') {
            Setting::updateOrCreate(
                ['key' => ElimApiConfig::SETTING_WEBHOOK_SECRET],
                ['value' => trim($this->elim_webhook_secret)]
            );
            $this->webhookSecretConfigured = true;
            $this->elim_webhook_secret = '';
        }

        if (trim($this->elim_webhook_id) !== '') {
            Setting::updateOrCreate(
                ['key' => ElimApiConfig::SETTING_WEBHOOK_ID],
                ['value' => trim($this->elim_webhook_id)]
            );
        }

        Setting::updateOrCreate(
            ['key' => ElimApiConfig::SETTING_PAYMENT_MODE],
            ['value' => $this->elim_payment_mode]
        );

        Setting::updateOrCreate(
            ['key' => ElimApiConfig::SETTING_DEMO_MODE],
            ['value' => $this->demo_mode_enabled ? '1' : '0']
        );

        app(ElimApiConfig::class)->refreshAfterSettingsUpdate();

        flash()->success(__('admin.elim_api_settings_saved'));
    }

    public function testConnection(ElimAuthService $authService): void
    {
        $apiKey = $this->resolveApiKey();

        if ($apiKey !== null && trim($this->elim_email) === '') {
            $this->validate([
                'elim_base_url' => ['required', 'url'],
            ]);

            try {
                $authService->testApiKey($this->elim_base_url, $apiKey);
            } catch (ElimAuthenticationException $exception) {
                flash()->error($exception->getMessage());

                return;
            }

            flash()->success(__('admin.elim_api_test_success'));

            return;
        }

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

    public function checkPlanAndWallet(ElimAuthService $authService, ElimOrderApiService $orderApi): void
    {
        try {
            $planResp = $authService->planStatus();
            $this->planStatus = is_array($planResp['data'] ?? null) ? $planResp['data'] : $planResp;

            $walletResp = $orderApi->purchasingWallet();
            $this->purchasingWallet = is_array($walletResp['data'] ?? null) ? $walletResp['data'] : $walletResp;

            flash()->success(__('admin.elim_api_status_loaded'));
        } catch (Throwable $exception) {
            flash()->error($exception->getMessage());
        }
    }

    public function registerWebhook(ElimWebhookService $webhookService): void
    {
        try {
            $url = route('webhooks.elim');
            $response = $webhookService->create($url, ['order.updated', 'payment.updated'], true);
            $data = is_array($response['data'] ?? null) ? $response['data'] : $response;

            if (! empty($data['id'])) {
                $this->elim_webhook_id = (string) $data['id'];
            }
            if (! empty($data['secret'])) {
                $this->webhookSecretConfigured = true;
            }

            app(ElimApiConfig::class)->refreshAfterSettingsUpdate();
            flash()->success(__('admin.elim_webhook_registered'));
        } catch (Throwable $exception) {
            flash()->error($exception->getMessage());
        }
    }

    public function testWebhook(ElimWebhookService $webhookService): void
    {
        if (trim($this->elim_webhook_id) === '') {
            flash()->error(__('admin.elim_webhook_id_required'));

            return;
        }

        try {
            $webhookService->test($this->elim_webhook_id);
            flash()->success(__('admin.elim_webhook_test_sent'));
        } catch (Throwable $exception) {
            flash()->error($exception->getMessage());
        }
    }

    public function rotateWebhookSecret(ElimWebhookService $webhookService): void
    {
        if (trim($this->elim_webhook_id) === '') {
            flash()->error(__('admin.elim_webhook_id_required'));

            return;
        }

        try {
            $webhookService->rotateSecret($this->elim_webhook_id);
            $this->webhookSecretConfigured = true;
            app(ElimApiConfig::class)->refreshAfterSettingsUpdate();
            flash()->success(__('admin.elim_webhook_secret_rotated'));
        } catch (Throwable $exception) {
            flash()->error($exception->getMessage());
        }
    }

    protected function resolvePassword(): ?string
    {
        if ($this->elim_password !== '') {
            return $this->elim_password;
        }

        $stored = SettingHelper::get(ElimApiConfig::SETTING_PASSWORD, config('services.elim.password'));

        return is_string($stored) && $stored !== '' ? $stored : null;
    }

    protected function resolveApiKey(): ?string
    {
        if (trim($this->elim_api_key) !== '') {
            return trim($this->elim_api_key);
        }

        $stored = SettingHelper::get(ElimApiConfig::SETTING_API_KEY, config('services.elim.api_key'));

        return is_string($stored) && trim($stored) !== '' ? trim($stored) : null;
    }

    public function render()
    {
        return view('livewire.admin.settings.elim-api-settings-page')
            ->title(__('admin.elim_api_settings'));
    }
}
