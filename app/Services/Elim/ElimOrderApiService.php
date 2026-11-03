<?php

namespace App\Services\Elim;

use App\Exceptions\Elim\ElimRequestException;

class ElimOrderApiService
{
    public function __construct(
        private readonly ElimApiClient $client,
    ) {}

    public function previewOrder(array $payload): array
    {
        return $this->client->post('/v1/orders/preview', $payload);
    }

    public function createOrder(array $payload): array
    {
        return $this->client->post('/v1/orders', $payload);
    }

    public function listOrders(array $query = []): array
    {
        return $this->client->get('/v1/orders', $query);
    }

    public function orderStats(array $query = []): array
    {
        return $this->client->get('/v1/orders/stats', $query);
    }

    public function detail(string $elimOrderId): array
    {
        return $this->client->get('/v1/orders/'.urlencode($elimOrderId));
    }

    public function refreshOrder(string $elimOrderId): array
    {
        return $this->client->post('/v1/orders/'.urlencode($elimOrderId).'/refresh');
    }

    public function cancel(string $elimOrderId): array
    {
        return $this->client->post('/v1/orders/'.urlencode($elimOrderId).'/cancel');
    }

    public function logisticDetail(string|int $elimOrderId, int|string|null $packageId = null, ?string $lang = null): array
    {
        if ($packageId === null && (is_int($elimOrderId) || ! str_starts_with((string) $elimOrderId, 'ORD'))) {
            return $this->client->get('/v1/orders/'.urlencode((string) $elimOrderId).'/logistic-detail');
        }

        $preferredUri = '/v1/orders/'.urlencode((string) $elimOrderId).'/logistic-detail';
        $fallbackUri = $packageId !== null
            ? '/v1/orders/'.urlencode((string) $packageId).'/logistic-detail'
            : $preferredUri;

        $resolvedUri = $this->client->resolveUriWithLegacyFallback($preferredUri, $fallbackUri);

        if ($resolvedUri === $fallbackUri && $fallbackUri !== $preferredUri) {
            return $this->client->get($fallbackUri);
        }

        $query = array_filter([
            'package_id' => $packageId !== null ? (string) $packageId : null,
            'lang' => $lang,
        ], fn (mixed $v): bool => $v !== null && $v !== '');

        return $this->client->get($preferredUri, $query);
    }

    public function purchasingWallet(): array
    {
        return $this->client->get('/v1/purchasing/wallet');
    }

    public function requestPayment(string $elimOrderId): array
    {
        return $this->client->post('/v1/purchasing/orders/'.urlencode($elimOrderId).'/request-payment');
    }

    public function cancelPaymentRequest(string $elimOrderId): array
    {
        return $this->client->post('/v1/purchasing/orders/'.urlencode($elimOrderId).'/cancel-payment-request');
    }

    public function confirmPayment(string $elimOrderId): array
    {
        return $this->client->post('/v1/purchasing/orders/'.urlencode($elimOrderId).'/confirm');
    }

    public function paymentDetail(string $elimOrderId): array
    {
        return $this->client->get('/v1/purchasing/orders/'.urlencode($elimOrderId).'/payment');
    }

    public function purchasingTransactions(array $query = []): array
    {
        return $this->client->get('/v1/purchasing/wallet/transactions', $query);
    }

    public function exchangeRates(): array
    {
        return $this->client->get('/v1/purchasing/exchange-rates');
    }

    /**
     * @return array{
     *     status: ?string,
     *     payment_status: ?string,
     *     platform_payment_status: ?string,
     *     rejection_reason: ?string,
     *     updated_at: ?string,
     *     data: array
     * }
     */
    public function parseDetailResponse(array $response): array
    {
        $data = $response['data'] ?? $response;

        return [
            'status' => isset($response['status'])
                ? (string) $response['status']
                : (isset($data['status']) ? (string) $data['status'] : null),
            'payment_status' => isset($data['payment_status'])
                ? (string) $data['payment_status']
                : (isset($response['payment_status']) ? (string) $response['payment_status'] : null),
            'platform_payment_status' => isset($data['platform_payment_status'])
                ? (string) $data['platform_payment_status']
                : (isset($response['platform_payment_status']) ? (string) $response['platform_payment_status'] : null),
            'rejection_reason' => isset($data['rejection_reason'])
                ? (string) $data['rejection_reason']
                : (isset($response['rejection_reason']) ? (string) $response['rejection_reason'] : null),
            'updated_at' => isset($data['updated_at'])
                ? (string) $data['updated_at']
                : (isset($response['updated_at']) ? (string) $response['updated_at'] : null),
            'data' => is_array($data) ? $data : [],
        ];
    }

    public function isInsufficientBalanceError(ElimRequestException $exception): bool
    {
        $body = $exception->context()['body'] ?? [];

        return is_array($body) && ($body['error'] ?? '') === 'insufficient_balance';
    }

    /**
     * @return array{deficit: float, current_balance: float, frozen_balance: float, available_balance: float, required: float}
     */
    public function insufficientBalancePayload(ElimRequestException $exception): array
    {
        $body = $exception->context()['body'] ?? [];

        return [
            'deficit' => (float) ($body['deficit'] ?? 0),
            'current_balance' => (float) ($body['current_balance'] ?? 0),
            'frozen_balance' => (float) ($body['frozen_balance'] ?? 0),
            'available_balance' => (float) ($body['available_balance'] ?? 0),
            'required' => (float) ($body['required'] ?? 0),
        ];
    }
}
