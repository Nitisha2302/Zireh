<?php

namespace App\Services\Elim;

use App\Exceptions\Elim\ElimAuthenticationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ElimAuthService
{
    private const ACCESS_TOKEN_KEY = 'elim:auth:access_token';

    private const REFRESH_TOKEN_KEY = 'elim:auth:refresh_token';

    public function accessToken(): string
    {
        $token = Cache::get(self::ACCESS_TOKEN_KEY);

        if (is_string($token) && $token !== '') {
            return $token;
        }

        return $this->login()['access_token'];
    }

    public function refreshAccessToken(): string
    {
        $refreshToken = Cache::get(self::REFRESH_TOKEN_KEY);

        if (! is_string($refreshToken) || $refreshToken === '') {
            return $this->login()['access_token'];
        }

        try {
            $response = Http::baseUrl($this->baseUrl())
                ->acceptJson()
                ->withToken($refreshToken)
                ->timeout($this->timeout())
                ->post('/v1/auth/refresh');
        } catch (\Throwable $exception) {
            Log::warning('ELIM token refresh failed; falling back to login.', [
                'message' => $exception->getMessage(),
            ]);

            return $this->login()['access_token'];
        }

        if (! $response->successful()) {
            Log::warning('ELIM token refresh returned non-success response; falling back to login.', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return $this->login()['access_token'];
        }

        return $this->storeTokens($response->json())['access_token'];
    }

    public function forgetTokens(): void
    {
        Cache::forget(self::ACCESS_TOKEN_KEY);
        Cache::forget(self::REFRESH_TOKEN_KEY);
    }

    public function login(): array
    {
        $email = config('services.elim.email');
        $password = config('services.elim.password');

        if (! $email || ! $password) {
            throw new ElimAuthenticationException('ELIM credentials are not configured.');
        }

        $response = Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->timeout($this->timeout())
            ->retry($this->retries(), $this->retrySleep())
            ->post('/v1/auth/login', [
                'email' => $email,
                'password' => $password,
            ]);

        if (! $response->successful()) {
            throw new ElimAuthenticationException('Unable to authenticate with ELIM.', $response->status(), context: [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
        }

        return $this->storeTokens($response->json());
    }

    private function storeTokens(array $payload): array
    {
        $accessToken = $payload['access_token'] ?? null;
        $refreshToken = $payload['refresh_token'] ?? null;

        if (! is_string($accessToken) || $accessToken === '') {
            throw new ElimAuthenticationException('ELIM auth response did not include an access token.', context: [
                'payload' => $payload,
            ]);
        }

        $accessTtl = $this->tokenTtlSeconds($payload['expires_in'] ?? null);
        Cache::put(self::ACCESS_TOKEN_KEY, $accessToken, $accessTtl);

        if (is_string($refreshToken) && $refreshToken !== '') {
            Cache::put(self::REFRESH_TOKEN_KEY, $refreshToken, now()->addDays(14));
        }

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $payload['expires_in'] ?? null,
        ];
    }

    private function tokenTtlSeconds(mixed $expiresIn): int
    {
        if (is_numeric($expiresIn)) {
            $expiresAt = Carbon::createFromTimestampMs((int) $expiresIn);
            $seconds = now()->diffInSeconds($expiresAt, false) - 60;

            return max($seconds, 60);
        }

        return (int) config('services.elim.token_ttl', 3300);
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.elim.base_url', 'https://openapi.elim.asia'), '/');
    }

    private function timeout(): int
    {
        return (int) config('services.elim.timeout', 20);
    }

    private function retries(): int
    {
        return (int) config('services.elim.retries', 2);
    }

    private function retrySleep(): int
    {
        return (int) config('services.elim.retry_sleep', 300);
    }
}
