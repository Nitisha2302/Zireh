<?php

namespace App\Services;

use App\Models\Platform;
use App\Models\PlatformCategory;
use Illuminate\Support\Facades\Cache;

class PlatformCategoryService
{
    public function listForPlatformKey(string $platformKey, string|null $lang = null): array
    {
        $language = $this->lang($lang);
        $locale = $this->resolveLocale($language);

        return Cache::remember(
            $this->cacheKey($platformKey, $language),
            $this->categoryTtl(),
            function () use ($platformKey, $language, $locale): array {
                $platform = Platform::query()->where('code', $platformKey)->first();

                $items = $platform
                    ? PlatformCategory::query()
                    ->where('platform_id', $platform->id)
                    ->where('is_active', true)
                    ->orderBy('name->en')
                    ->get()
                    ->map(fn(PlatformCategory $category): array => [
                        'id' => $category->keyword,
                        'name' => $category->getTranslation('name', $locale),
                    ])
                    ->values()
                    ->all()
                    : [];

                return [
                    'platform' => $platformKey,
                    'language' => $language,
                    'items' => $items,
                    'source' => 'database',
                ];
            }
        );
    }

    public function clearCache(string|null $platformKey = null): void
    {
        $platformKeys = $platformKey ? [$platformKey] : ['taobao', '1688'];

        foreach ($platformKeys as $key) {
            foreach (['vi', 'en'] as $lang) {
                Cache::forget($this->cacheKey($key, $lang));
            }
        }
    }

    public function keywordForPlatform(string $platformKey, string $categoryId): ?string
    {
        $platform = Platform::query()->where('code', $platformKey)->first();

        if (! $platform) {
            return null;
        }

        return PlatformCategory::query()
            ->where('platform_id', $platform->id)
            ->where('keyword', $categoryId)
            ->where('is_active', true)
            ->value('keyword');
    }

    protected function cacheKey(string $platformKey, string $language): string
    {
        $payload = ['lang' => $language];
        ksort($payload);

        return 'elim:' . $platformKey . ':categories:' . md5(json_encode($payload));
    }

    protected function lang(string|null $lang): string
    {
        return in_array($lang, ['vi', 'en'], true) ? $lang : (string) config('services.elim.default_lang', 'en');
    }

    protected function resolveLocale(string $lang): string
    {
        $supported = array_keys(config('localization.supported', ['en' => 'English']));

        if (in_array($lang, $supported, true)) {
            return $lang;
        }

        return (string) config('localization.default', 'en');
    }

    protected function categoryTtl(): int
    {
        return (int) config('services.elim.cache.categories_ttl', 86400);
    }
}
