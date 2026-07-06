<?php

namespace App\Services\Elim;

use App\Models\ElimApiLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ElimApiLogger
{
    private const MAX_RESPONSE_BYTES = 65536;

    private const REDACTED = '[REDACTED]';

    public function log(
        string $method,
        string $endpoint,
        ?array $requestPayload,
        mixed $responseBody,
        ?int $statusCode,
        bool $isSuccessful,
        ?int $durationMs = null,
        string $source = ElimApiLog::SOURCE_API,
        ?string $errorMessage = null,
    ): ElimApiLog {
        [$body, $truncated] = $this->prepareResponseBody($responseBody);

        return ElimApiLog::query()->create([
            'method' => strtoupper($method),
            'endpoint' => Str::limit($endpoint, 500, ''),
            'source' => $source,
            'status_code' => $statusCode,
            'is_successful' => $isSuccessful,
            'duration_ms' => $durationMs,
            'request_payload' => $requestPayload !== null ? $this->sanitize($requestPayload) : null,
            'response_body' => $body,
            'response_truncated' => $truncated,
            'error_message' => $errorMessage,
        ]);
    }

    public function listForAdmin(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->applyFilters(ElimApiLog::query()->latest('id'), $filters)
            ->paginate($perPage);
    }

    public function purgeOlderThanDays(int $days): int
    {
        return ElimApiLog::query()
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
    }

    public function sanitize(mixed $data): mixed
    {
        if (! is_array($data)) {
            return $data;
        }

        $sanitized = [];

        foreach ($data as $key => $value) {
            if ($this->shouldRedactKey((string) $key)) {
                $sanitized[$key] = self::REDACTED;

                continue;
            }

            $sanitized[$key] = is_array($value) ? $this->sanitize($value) : $value;
        }

        return $sanitized;
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when(! empty($filters['search']), function (Builder $q) use ($filters) {
                $search = $filters['search'];
                $q->where(function (Builder $inner) use ($search) {
                    $inner->where('endpoint', 'like', '%'.$search.'%')
                        ->orWhere('error_message', 'like', '%'.$search.'%');
                });
            })
            ->when(! empty($filters['method']), fn (Builder $q) => $q->where('method', strtoupper($filters['method'])))
            ->when(! empty($filters['source']), fn (Builder $q) => $q->where('source', $filters['source']))
            ->when(isset($filters['is_successful']) && $filters['is_successful'] !== '', function (Builder $q) use ($filters) {
                $q->where('is_successful', filter_var($filters['is_successful'], FILTER_VALIDATE_BOOLEAN));
            })
            ->when(! empty($filters['date_from']), fn (Builder $q) => $q->whereDate('created_at', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn (Builder $q) => $q->whereDate('created_at', '<=', $filters['date_to']));
    }

    protected function shouldRedactKey(string $key): bool
    {
        $normalized = strtolower($key);

        return in_array($normalized, [
            'password',
            'access_token',
            'refresh_token',
            'authorization',
            'api_key',
            'x-api-key',
        ], true) || str_contains($normalized, 'password') || str_contains($normalized, 'token');
    }

    /**
     * @return array{0: ?array, 1: bool}
     */
    protected function prepareResponseBody(mixed $responseBody): array
    {
        if ($responseBody === null) {
            return [null, false];
        }

        if (is_string($responseBody)) {
            $decoded = json_decode($responseBody, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $responseBody = $decoded;
            } else {
                return $this->truncateStringResponse($responseBody);
            }
        }

        if (! is_array($responseBody)) {
            return [['value' => $responseBody], false];
        }

        $encoded = json_encode($this->sanitize($responseBody));

        if ($encoded === false) {
            return [null, false];
        }

        if (strlen($encoded) <= self::MAX_RESPONSE_BYTES) {
            return [json_decode($encoded, true), false];
        }

        return [
            [
                '_truncated' => true,
                '_preview' => Str::limit($encoded, self::MAX_RESPONSE_BYTES, '...'),
            ],
            true,
        ];
    }

    /**
     * @return array{0: array, 1: bool}
     */
    protected function truncateStringResponse(string $response): array
    {
        $truncated = strlen($response) > self::MAX_RESPONSE_BYTES;

        return [
            ['_raw' => $truncated ? Str::limit($response, self::MAX_RESPONSE_BYTES, '...') : $response],
            $truncated,
        ];
    }
}
