<?php

namespace App\Support\RapidApi;

class RapidApiConfig
{
    public function key(): ?string
    {
        $key = config('services.rapidapi.key');

        return is_string($key) && $key !== '' ? $key : null;
    }

    public function host(): string
    {
        return (string) config('services.rapidapi.host', 'china-e-commerce-data-api.p.rapidapi.com');
    }

    public function baseUrl(): string
    {
        return rtrim((string) config('services.rapidapi.base_url', 'https://china-e-commerce-data-api.p.rapidapi.com'), '/');
    }

    public function timeout(): int
    {
        return (int) config('services.rapidapi.timeout', 20);
    }

    public function retries(): int
    {
        return (int) config('services.rapidapi.retries', 2);
    }

    public function retrySleep(): int
    {
        return (int) config('services.rapidapi.retry_sleep', 300);
    }

    public function defaultQuery(): string
    {
        return (string) config('services.rapidapi.default_query', '手机');
    }

    public function productsCacheTtl(): int
    {
        return (int) config('services.rapidapi.cache.products_ttl', 900);
    }

    public function credentialsFingerprint(): string
    {
        return substr(hash('sha256', $this->baseUrl().'|'.$this->host().'|'.$this->key()), 0, 12);
    }

    public function assertConfigured(): void
    {
        if ($this->key() === null) {
            throw new \App\Exceptions\RapidApi\RapidApiAuthenticationException(
                'RapidAPI key is not configured.',
                401,
                context: ['rapidapi_key' => ['RAPIDAPI_KEY is required.']]
            );
        }
    }
}
