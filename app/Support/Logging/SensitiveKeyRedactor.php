<?php

namespace App\Support\Logging;

/**
 * Replaces credential-bearing values in nested payloads before they are
 * persisted or logged.
 *
 * The same rules currently live inline in RapidApiLogger::shouldRedactKey().
 * They are extracted here so new loggers share one definition of "sensitive"
 * instead of each keeping their own copy.
 */
class SensitiveKeyRedactor
{
    public const REDACTED = '[REDACTED]';

    private const EXACT_KEYS = [
        'password',
        'access_token',
        'refresh_token',
        'authorization',
        'api_key',
        'api-key',
        'x-api-key',
        'x-rapidapi-key',
        'rapidapi_key',
        'secret',
        'client_secret',
    ];

    private const SUBSTRINGS = [
        'password',
        'token',
        'secret',
    ];

    public function redact(mixed $data): mixed
    {
        if (! is_array($data)) {
            return $data;
        }

        $redacted = [];

        foreach ($data as $key => $value) {
            if ($this->isSensitive((string) $key)) {
                $redacted[$key] = self::REDACTED;

                continue;
            }

            $redacted[$key] = is_array($value) ? $this->redact($value) : $value;
        }

        return $redacted;
    }

    public function isSensitive(string $key): bool
    {
        $normalized = strtolower($key);

        if (in_array($normalized, self::EXACT_KEYS, true)) {
            return true;
        }

        foreach (self::SUBSTRINGS as $needle) {
            if (str_contains($normalized, $needle)) {
                return true;
            }
        }

        return false;
    }
}
