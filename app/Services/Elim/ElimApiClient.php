<?php

namespace App\Services\Elim;

use App\Exceptions\Elim\ElimRequestException;
use App\Models\ElimApiLog;
use App\Support\Elim\ElimApiConfig;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ElimApiClient
{
    public function __construct(
        private readonly ElimAuthService $authService,
        private readonly ElimApiConfig $config,
        private readonly ElimApiLogger $logger,
    ) {
    }

    public function get(string $uri, array $query = [], bool $authenticated = true): array
    {
        return $this->send('get', $uri, $query, $authenticated);
    }

    public function post(string $uri, array $payload = [], bool $authenticated = true): array
    {
        return $this->send('post', $uri, $payload, $authenticated);
    }

    public function upload(string $uri, UploadedFile $file, array $payload = []): array
    {
        $startedAt = microtime(true);
        $request = $this->request(true)
            ->attach('file', fopen($file->getRealPath(), 'r'), $file->getClientOriginalName());

        foreach ($payload as $key => $value) {
            $request = $request->asMultipart()->attach($key, (string) $value);
        }

        $response = $request->post($uri);

        if ($response->status() === 401) {
            $this->authService->refreshAccessToken();

            $request = $this->request(true)
                ->attach('file', fopen($file->getRealPath(), 'r'), $file->getClientOriginalName());

            foreach ($payload as $key => $value) {
                $request = $request->asMultipart()->attach($key, (string) $value);
            }

            $response = $request->post($uri);
        }

        $this->recordResponse(
            'post',
            $uri,
            array_merge($payload, ['file' => $file->getClientOriginalName()]),
            $response,
            $startedAt,
            ElimApiLog::SOURCE_UPLOAD
        );

        if (! $response->successful()) {
            $this->throwRequestException('post', $uri, $response->status(), $response->json());
        }

        return $response->json() ?? [];
    }

    private function send(string $method, string $uri, array $data = [], bool $authenticated = true): array
    {
        $startedAt = microtime(true);
        $response = $this->request($authenticated)->{$method}($uri, $data);

        if ($authenticated && $response->status() === 401) {
            $this->authService->refreshAccessToken();
            $response = $this->request(true)->{$method}($uri, $data);
        }

        $this->recordResponse($method, $uri, $data, $response, $startedAt);

        if (! $response->successful()) {
            $this->throwRequestException($method, $uri, $response->status(), $response->json());
        }

        return $response->json() ?? [];
    }

    private function recordResponse(
        string $method,
        string $uri,
        ?array $requestPayload,
        Response $response,
        float $startedAt,
        string $source = ElimApiLog::SOURCE_API,
    ): void {
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        try {
            $this->logger->log(
                method: $method,
                endpoint: $uri,
                requestPayload: $requestPayload,
                responseBody: $response->json() ?? $response->body(),
                statusCode: $response->status(),
                isSuccessful: $response->successful(),
                durationMs: $durationMs,
                source: $source,
                errorMessage: $response->successful() ? null : $this->extractErrorMessage($response),
            );
        } catch (\Throwable $exception) {
            Log::warning('Failed to persist Elim API log.', [
                'endpoint' => $uri,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function extractErrorMessage(Response $response): ?string
    {
        $body = $response->json();

        if (! is_array($body)) {
            return $response->body() ?: null;
        }

        foreach (['message', 'error'] as $key) {
            if (! empty($body[$key]) && is_string($body[$key])) {
                return $body[$key];
            }
        }

        return null;
    }

    private function request(bool $authenticated): PendingRequest
    {
        $request = Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->timeout($this->config->timeout())
            ->retry($this->config->retries(), $this->config->retrySleep(), throw: false);

        if ($authenticated) {
            $request = $request->withToken($this->authService->accessToken());
        }

        return $request;
    }

    private function throwRequestException(string $method, string $uri, int $status, mixed $body): never
    {
        Log::warning('ELIM API request failed.', [
            'method' => strtoupper($method),
            'uri' => $uri,
            'status' => $status,
            'body' => $body,
        ]);

        throw new ElimRequestException('ELIM API request failed.', $status, context: [
            'method' => strtoupper($method),
            'uri' => $uri,
            'status' => $status,
            'body' => $body,
        ]);
    }

    private function baseUrl(): string
    {
        return $this->config->baseUrl();
    }
}
