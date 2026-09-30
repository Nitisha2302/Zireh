<?php

namespace App\Support\Alif;

use App\Helpers\SettingHelper;
use Illuminate\Support\Facades\Cache;

/**
 * Login, password and protocol limits Alif uses against this provider.
 *
 * Admin-saved `settings` rows win. Until those exist, values come from
 * config/alif.php (.env). Logging channels stay in env on purpose.
 */
class AlifProviderConfig
{
    public const CACHE_KEY = 'alif.provider_config';

    public const SETTING_LOGIN = 'alif_login';

    public const SETTING_PASSWORD = 'alif_password';

    public const SETTING_SRV_ID = 'alif_srv_id';

    public const SETTING_MIN_AMOUNT = 'alif_min_amount';

    public const SETTING_MAX_AMOUNT = 'alif_max_amount';

    public const SETTING_CURRENCY = 'alif_currency';

    public function configuration(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn (): array => $this->resolveConfiguration());
    }

    public function login(): string
    {
        return trim((string) ($this->configuration()['login'] ?? ''));
    }

    public function password(): string
    {
        return (string) ($this->configuration()['password'] ?? '');
    }

    public function srvId(): string
    {
        return trim((string) ($this->configuration()['srv_id'] ?? ''));
    }

    public function currency(): string
    {
        $currency = strtoupper(trim((string) ($this->configuration()['currency'] ?? '')));

        return $currency !== '' ? $currency : 'TJS';
    }

    public function minAmount(): Money
    {
        return Money::tryParse($this->configuration()['min_amount'] ?? null) ?? Money::of('1.00');
    }

    public function maxAmount(): Money
    {
        return Money::tryParse($this->configuration()['max_amount'] ?? null) ?? Money::of('20000.00');
    }

    public function credentialsAreConfigured(): bool
    {
        return $this->login() !== '' && $this->password() !== '';
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function refreshAfterSettingsUpdate(): void
    {
        SettingHelper::clearCache();
        $this->clearCache();
    }

    protected function resolveConfiguration(): array
    {
        return [
            'login' => SettingHelper::get(self::SETTING_LOGIN, config('alif.login')),
            'password' => SettingHelper::get(self::SETTING_PASSWORD, config('alif.password')),
            'srv_id' => SettingHelper::get(self::SETTING_SRV_ID, config('alif.srv_id')),
            'min_amount' => SettingHelper::get(self::SETTING_MIN_AMOUNT, config('alif.min_amount', '1.00')),
            'max_amount' => SettingHelper::get(self::SETTING_MAX_AMOUNT, config('alif.max_amount', '20000.00')),
            'currency' => SettingHelper::get(self::SETTING_CURRENCY, config('alif.currency', 'TJS')),
        ];
    }
}
