<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class LocaleManager
{
    public function supportedLocales(): array
    {
        return config('localization.supported', ['en' => 'English']);
    }

    public function resolve(?string $value, ?string $fallback = null): string
    {
        $supported = array_keys($this->supportedLocales());
        $fallback ??= config('localization.default', config('app.locale', 'en'));

        if (! $value) {
            return in_array($fallback, $supported, true) ? $fallback : $supported[0];
        }

        foreach (explode(',', $value) as $part) {
            $segment = trim(explode(';', $part)[0]);
            if ($segment === '') {
                continue;
            }

            $candidate = strtolower(str_replace('_', '-', $segment));
            $exact = strtok($candidate, '-');

            if (in_array($exact, $supported, true)) {
                return $exact;
            }
        }

        return in_array($fallback, $supported, true) ? $fallback : Arr::first($supported, default: 'en');
    }

    public function resolveFromRequest(Request $request, ?string $fallback = null): string
    {
        return $this->resolve($request->header('Accept-Language'), $fallback);
    }

    public function isSupported(string $locale): bool
    {
        return array_key_exists($locale, $this->supportedLocales());
    }
}
