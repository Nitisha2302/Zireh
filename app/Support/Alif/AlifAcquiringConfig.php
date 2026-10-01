<?php

namespace App\Support\Alif;

use App\Helpers\SettingHelper;
use Illuminate\Support\Facades\Cache;

/**
 * Terminal credentials and checkout URLs for Alif Acquiring.
 *
 * Admin-saved settings win. Until those exist, values come from config/alif.php.
 */
class AlifAcquiringConfig
{
    public const CACHE_KEY = 'alif.acquiring_config';

    public const SETTING_TERMINAL_KEY = 'alif_terminal_key';

    public const SETTING_TERMINAL_PASSWORD = 'alif_terminal_password';

    public const SETTING_BASE_URL = 'alif_base_url';

    public const SETTING_GATE = 'alif_gate';

    public const SETTING_CALLBACK_URL = 'alif_callback_url';

    public const SETTING_RETURN_URL = 'alif_return_url';

    public const SETTING_MIN_AMOUNT = 'alif_min_amount';

    public const SETTING_MAX_AMOUNT = 'alif_max_amount';

    public const SETTING_CURRENCY = 'alif_currency';

    public function configuration(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn (): array => $this->resolveConfiguration());
    }

    public function terminalKey(): string
    {
        return trim((string) ($this->configuration()['terminal_key'] ?? ''));
    }

    public function terminalPassword(): string
    {
        return (string) ($this->configuration()['terminal_password'] ?? '');
    }

    public function baseUrl(): string
    {
        $url = rtrim(trim((string) ($this->configuration()['base_url'] ?? '')), '/');

        return $url !== '' ? $url : 'https://test-web.alif.tj';
    }

    public function gate(): string
    {
        $gate = trim((string) ($this->configuration()['gate'] ?? ''));

        return $gate !== '' ? $gate : 'korti_milli';
    }

    public function callbackUrl(): string
    {
        $url = trim((string) ($this->configuration()['callback_url'] ?? ''));

        return $url !== '' ? $url : rtrim((string) config('app.url'), '/').'/api/v1/payments/alif/callback';
    }

    public function returnUrl(): string
    {
        $url = trim((string) ($this->configuration()['return_url'] ?? ''));

        return $url !== '' ? $url : rtrim((string) config('app.url'), '/').'/payment/alif/return';
    }

    public function timeout(): int
    {
        return max(1, (int) config('alif.timeout', 30));
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
        return $this->terminalKey() !== '' && $this->terminalPassword() !== '';
    }

    public function urlsAreConfigured(): bool
    {
        return $this->callbackUrl() !== '' && $this->returnUrl() !== '';
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
            'terminal_key' => SettingHelper::get(self::SETTING_TERMINAL_KEY, config('alif.terminal_key')),
            'terminal_password' => SettingHelper::get(self::SETTING_TERMINAL_PASSWORD, config('alif.terminal_password')),
            'base_url' => SettingHelper::get(self::SETTING_BASE_URL, config('alif.base_url')),
            'gate' => SettingHelper::get(self::SETTING_GATE, config('alif.gate', 'korti_milli')),
            'callback_url' => SettingHelper::get(self::SETTING_CALLBACK_URL, config('alif.callback_url')),
            'return_url' => SettingHelper::get(self::SETTING_RETURN_URL, config('alif.return_url')),
            'min_amount' => SettingHelper::get(self::SETTING_MIN_AMOUNT, config('alif.min_amount', '1.00')),
            'max_amount' => SettingHelper::get(self::SETTING_MAX_AMOUNT, config('alif.max_amount', '20000.00')),
            'currency' => SettingHelper::get(self::SETTING_CURRENCY, config('alif.currency', 'TJS')),
        ];
    }
}
