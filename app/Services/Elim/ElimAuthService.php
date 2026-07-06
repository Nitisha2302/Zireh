<?php

namespace App\Services\Elim;

use App\Exceptions\Elim\ElimAuthenticationException;
use App\Models\ElimApiLog;
use App\Support\Elim\ElimApiConfig;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ElimAuthService
{
    private const ACCESS_TOKEN_KEY = 'elim:auth:access_token';

    private const REFRESH_TOKEN_KEY = 'elim:auth:refresh_token';

    public function __construct(
        private readonly ElimApiConfig $config,
        private readonly ElimApiLogger $logger,
    ) {
    }

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
            $startedAt = microtime(true);
            $response = Http::baseUrl($this->baseUrl())
                ->acceptJson()
                ->withToken($refreshToken)
                ->timeout($this->timeout())
                ->post('/v1/auth/refresh');

            $this->recordAuthResponse('post', '/v1/auth/refresh', null, $response, $startedAt);
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
        $email = $this->config->email();
        $password = $this->config->password();

        if (! $email || ! $password) {
            throw new ElimAuthenticationException('ELIM credentials are not configured.');
        }

        $startedAt = microtime(true);
        $payload = [
            'email' => $email,
            'password' => $password,
        ];

        $response = Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->timeout($this->timeout())
            ->retry($this->retries(), $this->retrySleep())
            ->post('/v1/auth/login', $payload);

        $this->recordAuthResponse('post', '/v1/auth/login', $payload, $response, $startedAt);

        if (! $response->successful()) {
            throw new ElimAuthenticationException('Unable to authenticate with ELIM.', $response->status(), context: [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
        }

        return $this->storeTokens($response->json());
    }

    public function testCredentials(string $baseUrl, string $email, string $password): void
    {
        if ($email === '' || $password === '') {
            throw new ElimAuthenticationException(__('admin.elim_api_password_required_for_test'));
        }

        try {
            $startedAt = microtime(true);
            $payload = [
                'email' => $email,
                'password' => $password,
            ];

            $response = Http::baseUrl(rtrim($baseUrl, '/'))
                ->acceptJson()
                ->timeout($this->timeout())
                ->post('/v1/auth/login', $payload);

            $this->recordAuthResponse(
                'post',
                '/v1/auth/login',
                $payload,
                $response,
                $startedAt,
                ElimApiLog::SOURCE_AUTH_TEST
            );
        } catch (\Throwable $exception) {
            throw new ElimAuthenticationException(
                __('admin.elim_api_test_unreachable', ['message' => $exception->getMessage()]),
                context: ['message' => $exception->getMessage()]
            );
        }

        if (! $response->successful()) {
            $body = $response->json();
            $message = is_array($body) ? ($body['message'] ?? $body['error'] ?? null) : null;

            throw new ElimAuthenticationException(
                is_string($message) && $message !== '' ? $message : __('admin.elim_api_test_invalid_credentials'),
                $response->status(),
                context: [
                    'status' => $response->status(),
                    'body' => $body,
                ]
            );
        }

        $accessToken = $response->json('access_token');

        if (! is_string($accessToken) || $accessToken === '') {
            throw new ElimAuthenticationException(__('admin.elim_api_test_invalid_response'));
        }
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

        return $this->config->tokenTtl();
    }

    private function baseUrl(): string
    {
        return $this->config->baseUrl();
    }

    private function timeout(): int
    {
        return $this->config->timeout();
    }

    private function retries(): int
    {
        return $this->config->retries();
    }

    private function retrySleep(): int
    {
        return $this->config->retrySleep();
    }

    private function recordAuthResponse(
        string $method,
        string $endpoint,
        ?array $requestPayload,
        Response $response,
        float $startedAt,
        string $source = ElimApiLog::SOURCE_AUTH,
    ): void {
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        try {
            $body = $response->json() ?? $response->body();
            $sanitizedBody = is_array($body) ? $body : ['_raw' => $body];

            if (is_array($sanitizedBody) && $response->successful()) {
                $sanitizedBody = array_merge($sanitizedBody, [
                    'access_token' => isset($sanitizedBody['access_token']) ? '[REDACTED]' : null,
                    'refresh_token' => isset($sanitizedBody['refresh_token']) ? '[REDACTED]' : null,
                ]);
            }

            $this->logger->log(
                method: $method,
                endpoint: $endpoint,
                requestPayload: $requestPayload,
                responseBody: $sanitizedBody,
                statusCode: $response->status(),
                isSuccessful: $response->successful(),
                durationMs: $durationMs,
                source: $source,
                errorMessage: $response->successful() ? null : (is_array($body) ? ($body['message'] ?? $body['error'] ?? null) : null),
            );
        } catch (\Throwable $exception) {
            Log::warning('Failed to persist Elim auth API log.', [
                'endpoint' => $endpoint,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
