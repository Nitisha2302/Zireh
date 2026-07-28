<?php

namespace App\Services\RapidApi;

use App\Exceptions\RapidApi\RapidApiRequestException;
use App\Support\RapidApi\RapidApiConfig;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RapidApiClient
{
    public function __construct(
        private readonly RapidApiConfig $config,
    ) {}

    public function get(string $uri, array $query = []): array
    {
        $this->config->assertConfigured();

        $startedAt = microtime(true);
        $response = $this->request()->get($uri, $query);
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        if (! $response->successful()) {
            $this->throwRequestException('get', $uri, $response->status(), $response->json(), $durationMs);
        }

        $body = $response->json() ?? [];

        if (is_array($body) && array_key_exists('ok', $body) && $body['ok'] === false) {
            $this->throwRequestException('get', $uri, $response->status(), $body, $durationMs);
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
