<?php

namespace App\Helpers;

use App\Models\Setting;
use App\Services\FileManager;
use Illuminate\Support\Facades\Cache;

class SettingHelper
{
    public const DEFAULT_COMPANY_NAME = 'ZirehCargo';

    protected static function cacheKey(): string
    {
        return 'settings.central';
    }

    protected static function companyCacheKey(): string
    {
        return 'settings.company';
    }

    /**
     * Get a single setting
     */
    public static function get(string $key, $default = null)
    {
        $settings = self::all();

        return $settings[$key] ?? $default;
    }

    /**
     * Get all settings (SAFE for file/database/redis)
     */
    public static function all(): array
    {
        return Cache::store(config('cache.default'))->rememberForever(
            self::cacheKey(),
            fn () => Setting::pluck('value', 'key')->toArray()
        );
    }

    /**
     * Cached company branding for sidebars, logins, and public pages.
     *
     * @return array{name: string, logo: ?string, logo_url: ?string}
     */
    public static function company(): array
    {
        return Cache::store(config('cache.default'))->rememberForever(
            self::companyCacheKey(),
            function (): array {
                $name = trim((string) self::get('company_name', self::DEFAULT_COMPANY_NAME));
                $logo = self::get('company_logo');
                $logoPath = filled($logo) ? (string) $logo : null;

                return [
                    'name' => $name !== '' ? $name : self::DEFAULT_COMPANY_NAME,
                    'logo' => $logoPath,
                    'logo_url' => $logoPath ? app(FileManager::class)->url($logoPath) : null,
                ];
            }
        );
    }

    public static function companyName(): string
    {
        return self::company()['name'];
    }

    public static function companyLogoUrl(): ?string
    {
        return self::company()['logo_url'];
    }

    /**
     * Clear settings caches (central + company branding).
     */
    public static function clearCache(): void
    {
        $store = Cache::store(config('cache.default'));
        $store->forget(self::cacheKey());
        $store->forget(self::companyCacheKey());
    }
}
