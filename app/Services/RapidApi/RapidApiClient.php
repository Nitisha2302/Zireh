<?php

namespace App\Services\RapidApi;

use App\Exceptions\RapidApi\RapidApiRequestException;
use App\Models\RapidApiLog;
use App\Support\RapidApi\RapidApiConfig;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RapidApiClient
{
    public function __construct(
        private readonly RapidApiConfig $config,
        private readonly RapidApiLogger $logger,
    ) {}

    public function get(string $uri, array $query = []): array
    {
        $this->config->assertConfigured();

        $startedAt = microtime(true);
        $response = $this->request()->get($uri, $query);
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        $body = $response->json() ?? $response->body();
        $businessFailure = is_array($body) && array_key_exists('ok', $body) && $body['ok'] === false;
        $isSuccessful = $response->successful() && ! $businessFailure;

        $this->recordResponse('get', $uri, $query, $response, $startedAt, $isSuccessful, $body);

        if (! $response->successful() || $businessFailure) {
            $this->throwRequestException('get', $uri, $response->status(), is_array($body) ? $body : $response->json(), $durationMs);
        }

        Log::info('RapidAPI request succeeded.', [
            'method' => 'GET',
            'uri' => $uri,
            'status' => $response->status(),
            'duration_ms' => $durationMs,
            'query' => $query,
        ]);

        return is_array($body) ? $body : [];
    }

    private function recordResponse(
        string $method,
        string $uri,
        ?array $requestPayload,
        Response $response,
        float $startedAt,
        bool $isSuccessful,
        mixed $responseBody,
        string $source = RapidApiLog::SOURCE_API,
    ): void {
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        try {
            $this->logger->log(
                method: $method,
                endpoint: $uri,
                requestPayload: $requestPayload,
                responseBody: $responseBody,
                statusCode: $response->status(),
                isSuccessful: $isSuccessful,
                durationMs: $durationMs,
                source: $source,
                errorMessage: $isSuccessful ? null : $this->extractErrorMessage($responseBody, $response),
            );
        } catch (\Throwable $exception) {
            Log::warning('Failed to persist RapidAPI log.', [
                'endpoint' => $uri,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function extractErrorMessage(mixed $body, Response $response): ?string
    {
        if (is_array($body)) {
            foreach (['message', 'error'] as $key) {
                if (! empty($body[$key]) && is_string($body[$key])) {
                    return $body[$key];
                }
            }
        }

        if (is_string($body) && $body !== '') {
            return $body;
        }

        return $response->body() ?: null;
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl($this->config->baseUrl())
            ->acceptJson()
            ->timeout($this->config->timeout())
            ->retry($this->config->retries(), $this->config->retrySleep(), throw: false)
            ->withHeaders([
                'X-RapidAPI-Key' => $this->config->key(),
                'X-RapidAPI-Host' => $this->config->host(),
            ]);
    }

    private function throwRequestException(
        string $method,
        string $uri,
        int $status,
        mixed $body,
        int $durationMs,
    ): never {
        Log::warning('RapidAPI request failed.', [
            'method' => strtoupper($method),
            'uri' => $uri,
            'status' => $status,
            'duration_ms' => $durationMs,
            'body' => $body,
        ]);

        throw new RapidApiRequestException('RapidAPI request failed.', $status ?: 502, context: [
            'method' => strtoupper($method),
            'uri' => $uri,
            'status' => $status,
            'body' => $body,
        ]);
    }
}
