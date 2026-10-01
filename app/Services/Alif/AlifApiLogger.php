<?php

namespace App\Services\Alif;

use App\Models\AlifApiLog;
use App\Support\Alif\Money;
use App\Support\Logging\SensitiveKeyRedactor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class AlifApiLogger
{
    private const MAX_RESPONSE_BYTES = 65536;

    public function __construct(
        private readonly SensitiveKeyRedactor $redactor,
    ) {}

    /**
     * @param  array<string, mixed>  $requestPayload
     */
    public function record(
        string $action,
        array $requestPayload,
        mixed $responseBody,
        ?int $httpStatus,
        bool $successful,
        ?float $durationMs = null,
        ?string $orderId = null,
        ?string $account = null,
        ?string $amount = null,
        ?int $responseCode = null,
        bool $authorized = true,
        ?string $ipAddress = null,
        ?string $errorMessage = null,
        ?int $alifPaymentId = null,
    ): AlifApiLog {
        [$body, $truncated] = $this->prepareBody($responseBody);

        return AlifApiLog::query()->create([
            'action' => $this->normalizedAction($action),
            'payment_id' => $this->scalarString($orderId ?? ($requestPayload['order_id'] ?? $requestPayload['orderId'] ?? null), 64),
            'account' => $this->scalarString($account, 64),
            'amount' => Money::tryParse($amount ?? ($requestPayload['amount'] ?? null))?->value(),
            'response_code' => $responseCode ?? $httpStatus,
            'http_status' => $httpStatus,
            'authorized' => $authorized,
            'is_successful' => $successful,
            'duration_ms' => $durationMs,
            'ip_address' => $ipAddress,
            'request_payload' => $this->redactor->redact($requestPayload),
            'response_body' => $body,
            'response_truncated' => $truncated,
            'error_message' => $errorMessage,
            'alif_payment_id' => $alifPaymentId,
        ]);
    }

    public function listForAdmin(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->applyFilters(AlifApiLog::query()->with('payment')->latest('id'), $filters)
            ->paginate($perPage);
    }

    public function purgeOlderThanDays(int $days): int
    {
        return AlifApiLog::query()
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when(! empty($filters['search']), function (Builder $q) use ($filters) {
                $search = $filters['search'];
                $q->where(function (Builder $inner) use ($search) {
                    $inner->where('payment_id', 'like', '%'.$search.'%')
                        ->orWhere('account', 'like', '%'.$search.'%')
                        ->orWhere('error_message', 'like', '%'.$search.'%');
                });
            })
            ->when(! empty($filters['action']), fn (Builder $q) => $q->where('action', $filters['action']))
            ->when(! empty($filters['response_code']), fn (Builder $q) => $q->where('response_code', (int) $filters['response_code']))
            ->when(isset($filters['is_successful']) && $filters['is_successful'] !== '', function (Builder $q) use ($filters) {
                $q->where('is_successful', filter_var($filters['is_successful'], FILTER_VALIDATE_BOOLEAN));
            })
            ->when(! empty($filters['date_from']), fn (Builder $q) => $q->whereDate('created_at', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn (Builder $q) => $q->whereDate('created_at', '<=', $filters['date_to']));
    }

    protected function normalizedAction(mixed $action): ?string
    {
        if (! is_string($action)) {
            return null;
        }

        $value = strtolower(trim($action));

        return $value === '' ? null : Str::limit($value, 20, '');
    }

    protected function scalarString(mixed $value, int $limit): ?string
    {
        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : Str::limit($string, $limit, '');
    }

    /**
     * @return array{0: ?array, 1: bool}
     */
    protected function prepareBody(mixed $body): array
    {
        if ($body === null) {
            return [null, false];
        }

        if (is_string($body)) {
            $decoded = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $truncated = strlen($body) > self::MAX_RESPONSE_BYTES;

                return [
                    ['_raw' => $truncated ? Str::limit($body, self::MAX_RESPONSE_BYTES, '...') : $body],
                    $truncated,
                ];
            }

            $body = $decoded;
        }

        if (! is_array($body)) {
            return [['value' => $body], false];
        }

        $encoded = json_encode($this->redactor->redact($body));

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
}
