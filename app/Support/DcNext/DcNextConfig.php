<?php

namespace App\Support\DcNext;

use App\Helpers\SettingHelper;
use App\Support\Alif\Money;
use Illuminate\Support\Facades\Cache;

/**
 * Merchant credentials and invoice URLs for DC NEXT Acquiring.
 *
 * Admin-saved settings win. Until those exist, values come from config/dc_next.php.
 */
class DcNextConfig
{
    public const CACHE_KEY = 'dc_next.acquiring_config';

    public const SETTING_MERCHANT = 'dc_next_merchant';

    public const SETTING_SECRET = 'dc_next_secret';

    public const SETTING_CALLBACK_URL = 'dc_next_callback_url';

    public const SETTING_CREATE_URL = 'dc_next_create_url';

    public const SETTING_CHECK_URL = 'dc_next_check_url';

    public const SETTING_CURRENCY = 'dc_next_currency';

    public const SETTING_EXP_MINUTES = 'dc_next_exp_minutes';

    public const SETTING_MIN_AMOUNT = 'dc_next_min_amount';

    public const SETTING_MAX_AMOUNT = 'dc_next_max_amount';

    public function configuration(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn (): array => $this->resolveConfiguration());
    }

    public function merchant(): string
    {
        return trim((string) ($this->configuration()['merchant'] ?? ''));
    }

    public function secret(): string
    {
        return (string) ($this->configuration()['secret'] ?? '');
    }

    public function callbackUrl(): string
    {
        $url = trim((string) ($this->configuration()['callback_url'] ?? ''));

        return $url !== '' ? $url : rtrim((string) config('app.url'), '/').'/api/v1/webhooks/dc-next/invoice';
    }

    public function createUrl(): string
    {
        $url = trim((string) ($this->configuration()['create_url'] ?? ''));

        return $url !== '' ? $url : 'https://invoice.dc.tj/v1/create';
    }

    public function checkUrl(): string
    {
        $url = trim((string) ($this->configuration()['check_url'] ?? ''));

        return $url !== '' ? $url : 'https://invoice.dc.tj/v1/check';
    }

    public function currency(): string
    {
        $currency = strtoupper(trim((string) ($this->configuration()['currency'] ?? '')));

        return $currency !== '' ? $currency : 'TJS';
    }

    public function expMinutes(): int
    {
        return max(1, (int) ($this->configuration()['exp_minutes'] ?? 40));
    }

    public function timeout(): int
    {
        return max(1, (int) config('dc_next.timeout', 30));
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
        return $this->merchant() !== '' && $this->secret() !== '';
    }

    public function urlsAreConfigured(): bool
    {
        return $this->callbackUrl() !== '' && $this->createUrl() !== '' && $this->checkUrl() !== '';
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
            'merchant' => SettingHelper::get(self::SETTING_MERCHANT, config('dc_next.merchant')),
            'secret' => SettingHelper::get(self::SETTING_SECRET, config('dc_next.secret')),
            'callback_url' => SettingHelper::get(self::SETTING_CALLBACK_URL, config('dc_next.callback_url')),
            'create_url' => SettingHelper::get(self::SETTING_CREATE_URL, config('dc_next.create_url', 'https://invoice.dc.tj/v1/create')),
            'check_url' => SettingHelper::get(self::SETTING_CHECK_URL, config('dc_next.check_url', 'https://invoice.dc.tj/v1/check')),
            'currency' => SettingHelper::get(self::SETTING_CURRENCY, config('dc_next.default_currency', 'TJS')),
            'exp_minutes' => SettingHelper::get(self::SETTING_EXP_MINUTES, config('dc_next.invoice_exp_minutes', 40)),
            'min_amount' => SettingHelper::get(self::SETTING_MIN_AMOUNT, config('dc_next.min_amount', '1.00')),
            'max_amount' => SettingHelper::get(self::SETTING_MAX_AMOUNT, config('dc_next.max_amount', '20000.00')),
        ];
    }
}
