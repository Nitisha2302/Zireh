<?php

namespace App\Services\Elim;

use App\Exceptions\Elim\ElimRequestException;

class ElimOrderApiService
{
    public function __construct(
        private readonly ElimApiClient $client,
    ) {}

    public function detail(string $elimOrderId): array
    {
        return $this->client->get('/v1/orders/'.urlencode($elimOrderId));
    }

    public function cancel(string $elimOrderId): array
    {
        return $this->client->post('/v1/orders/'.urlencode($elimOrderId).'/cancel');
    }

    public function logisticDetail(int $packageId): array
    {
        return $this->client->get('/v1/orders/'.$packageId.'/logistic-detail');
    }

    public function purchasingWallet(): array
    {
        return $this->client->get('/v1/purchasing/wallet');
    }

    public function confirmPayment(string $elimOrderId): array
    {
        return $this->client->post('/v1/purchasing/orders/'.urlencode($elimOrderId).'/confirm');
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
     * @return array{status: ?string, payment_status: ?string, data: array}
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
            'data' => is_array($data) ? $data : [],
        ];
    }

    public function isInsufficientBalanceError(ElimRequestException $exception): bool
    {
        $body = $exception->context()['body'] ?? [];

        return is_array($body) && ($body['error'] ?? '') === 'insufficient_balance';
    }

    /**
     * @return array{deficit: float, current_balance: float, required: float}
     */
    public function insufficientBalancePayload(ElimRequestException $exception): array
    {
        $body = $exception->context()['body'] ?? [];

        return [
            'deficit' => (float) ($body['deficit'] ?? 0),
            'current_balance' => (float) ($body['current_balance'] ?? 0),
            'required' => (float) ($body['required'] ?? 0),
        ];
    }
}
