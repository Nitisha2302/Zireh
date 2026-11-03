<?php

namespace App\Support\Elim;

use App\Helpers\SettingHelper;
use App\Services\Elim\ElimAuthService;
use Illuminate\Support\Facades\Cache;

class ElimApiConfig
{
    public const CACHE_KEY = 'elim.api_config';

    public const SETTING_BASE_URL = 'elim_base_url';

    public const SETTING_EMAIL = 'elim_email';

    public const SETTING_PASSWORD = 'elim_password';

    public const SETTING_API_KEY = 'elim_api_key';

    public const SETTING_WEBHOOK_SECRET = 'elim_webhook_secret';

    public const SETTING_WEBHOOK_ID = 'elim_webhook_id';

    public const SETTING_PAYMENT_MODE = 'elim_payment_mode';

    public const SETTING_DEMO_MODE = 'elim_demo_mode';

    public const PAYMENT_MODE_CONFIRM = 'confirm';

    public const PAYMENT_MODE_REQUEST = 'request';

    public function demoModeEnabled(): bool
    {
        $value = SettingHelper::get(self::SETTING_DEMO_MODE);

        if ($value === null) {
            $value = config('services.elim.demo_mode', false);
        }

        return $this->toBoolean($value);
    }

    public function configuration(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn (): array => $this->resolveConfiguration());
    }

    public function baseUrl(): string
    {
        return rtrim((string) ($this->configuration()['base_url'] ?? 'https://openapi.elim.asia'), '/');
    }

    public function email(): ?string
    {
        $email = $this->configuration()['email'] ?? null;

        return is_string($email) && $email !== '' ? $email : null;
    }

    public function password(): ?string
    {
        $password = $this->configuration()['password'] ?? null;

        return is_string($password) && $password !== '' ? $password : null;
    }

    public function apiKey(): ?string
    {
        $apiKey = $this->configuration()['api_key'] ?? null;

        return is_string($apiKey) && $apiKey !== '' ? $apiKey : null;
    }

    public function webhookSecret(): ?string
    {
        $secret = $this->configuration()['webhook_secret'] ?? null;

        return is_string($secret) && $secret !== '' ? $secret : null;
    }

    public function webhookId(): ?string
    {
        $id = $this->configuration()['webhook_id'] ?? null;

        return is_string($id) && $id !== '' ? $id : null;
    }

    public function paymentMode(): string
    {
        $mode = $this->configuration()['payment_mode'] ?? self::PAYMENT_MODE_CONFIRM;

        return in_array($mode, [self::PAYMENT_MODE_CONFIRM, self::PAYMENT_MODE_REQUEST], true)
            ? $mode
            : self::PAYMENT_MODE_CONFIRM;
    }

    public function credentialsFingerprint(): string
    {
        return substr(
            hash('sha256', $this->baseUrl().'|'.$this->email().'|'.$this->password().'|'.$this->apiKey()),
            0,
            12
        );
    }

    public function timeout(): int
    {
        return (int) config('services.elim.timeout', 20);
    }

    public function retries(): int
    {
        return (int) config('services.elim.retries', 2);
    }

    public function retrySleep(): int
    {
        return (int) config('services.elim.retry_sleep', 300);
    }

    public function tokenTtl(): int
    {
        return (int) config('services.elim.token_ttl', 3300);
    }

    public function defaultLang(): string
    {
        return (string) config('services.elim.default_lang', 'en');
    }

    public function defaultQuery(): string
    {
        return (string) config('services.elim.default_query', 'bag');
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function refreshAfterSettingsUpdate(): void
    {
        SettingHelper::clearCache();
        $this->clearCache();
        app(ElimAuthService::class)->forgetTokens();
    }

    protected function resolveConfiguration(): array
    {
        return [
            'base_url' => SettingHelper::get(self::SETTING_BASE_URL, config('services.elim.base_url')),
            'email' => SettingHelper::get(self::SETTING_EMAIL, config('services.elim.email')),
            'password' => SettingHelper::get(self::SETTING_PASSWORD, config('services.elim.password')),
            'api_key' => SettingHelper::get(self::SETTING_API_KEY, config('services.elim.api_key')),
            'webhook_secret' => SettingHelper::get(self::SETTING_WEBHOOK_SECRET, config('services.elim.webhook_secret')),
            'webhook_id' => SettingHelper::get(self::SETTING_WEBHOOK_ID, config('services.elim.webhook_id')),
            'payment_mode' => SettingHelper::get(self::SETTING_PAYMENT_MODE, config('services.elim.payment_mode', self::PAYMENT_MODE_CONFIRM)),
            'demo_mode' => $this->demoModeEnabled(),
        ];
    }

    protected function toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
