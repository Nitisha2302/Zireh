<?php

namespace App\Services\Elim;

use App\Exceptions\Elim\ElimRequestException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ElimApiClient
{
    public function __construct(private readonly ElimAuthService $authService)
    {
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

        if (! $response->successful()) {
            $this->throwRequestException('post', $uri, $response->status(), $response->json());
        }

        return $response->json() ?? [];
    }

    private function send(string $method, string $uri, array $data = [], bool $authenticated = true): array
    {
        $response = $this->request($authenticated)->{$method}($uri, $data);

        if ($authenticated && $response->status() === 401) {
            $this->authService->refreshAccessToken();
            $response = $this->request(true)->{$method}($uri, $data);
        }

        if (! $response->successful()) {
            $this->throwRequestException($method, $uri, $response->status(), $response->json());
        }

        return $response->json() ?? [];
    }

    private function request(bool $authenticated): PendingRequest
    {
        $request = Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->timeout((int) config('services.elim.timeout', 20))
            ->retry((int) config('services.elim.retries', 2), (int) config('services.elim.retry_sleep', 300), throw: false);

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
        return rtrim((string) config('services.elim.base_url', 'https://openapi.elim.asia'), '/');
    }
}
