<?php

declare(strict_types=1);

namespace App\Services\Elim;

use App\Models\CustomerOrder;
use App\Models\ElimApiLog;
use App\Models\Setting;
use App\Services\Order\CustomerOrderLifecycleService;
use App\Support\Elim\ElimApiConfig;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Throwable;

class ElimWebhookService
{
    public const SIGNATURE_TOLERANCE_SECONDS = 300;

    public function __construct(
        private readonly ElimApiClient $client,
        private readonly CustomerOrderLifecycleService $lifecycle,
        private readonly ElimApiLogger $logger,
    ) {}

    /**
     * Verify Elim HMAC-SHA256 webhook signature (`X-Elim-Signature` and `X-Elim-Timestamp`).
     */
    public function verifySignature(
        string $rawBody,
        string $timestamp,
        string $signature,
        ?string $secret = null,
    ): bool {
        $resolvedSecret = trim((string) ($secret ?? app(ElimApiConfig::class)->webhookSecret() ?? ''));
        if ($resolvedSecret === '' || trim($timestamp) === '' || trim($signature) === '') {
            return false;
        }

        if (! ctype_digit(trim($timestamp))) {
            return false;
        }

        $ts = (int) trim($timestamp);
        if (abs(time() - $ts) > self::SIGNATURE_TOLERANCE_SECONDS) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', "{$ts}.{$rawBody}", $resolvedSecret);

        return hash_equals($expected, trim($signature));
    }

    /**
     * Process an incoming Elim webhook payload (`order.updated` / `payment.updated`).
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $headers
     * @return array<string, mixed>
     */
    public function handleWebhook(array $payload, array $headers = []): array
    {
        $event = trim((string) (
            $payload['event']
            ?? $headers['X-Elim-Event']
            ?? $headers['x-elim-event']
            ?? ''
        ));
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $orderPayload = is_array($data['order'] ?? null) ? $data['order'] : $data;
        $elimOrderId = trim((string) ($orderPayload['id'] ?? $orderPayload['order_id'] ?? ''));

        if ($elimOrderId === '') {
            $isTestPing = in_array($event, ['webhook.test', 'ping', 'test'], true);
            $result = [
                'processed' => $isTestPing,
                'reason' => $isTestPing ? 'test_ping' : 'missing_order_id',
                'event' => $event,
            ];

            $this->logWebhook($event, $payload, $headers, 200, $result);

            return $result;
        }

        /** @var CustomerOrder|null $order */
        $order = CustomerOrder::query()
            ->where('elim_order_id', $elimOrderId)
            ->first();

        if ($order === null) {
            $result = [
                'processed' => false,
                'reason' => 'order_not_found',
                'event' => $event,
                'elim_order_id' => $elimOrderId,
            ];

            $this->logWebhook($event, $payload, $headers, 200, $result);

            return $result;
        }

        $incomingUpdatedAt = isset($orderPayload['updated_at']) && is_string($orderPayload['updated_at'])
            ? trim($orderPayload['updated_at'])
            : '';

        if ($incomingUpdatedAt !== '' && $this->isStaleWebhookDelivery($order, $incomingUpdatedAt)) {
            $result = [
                'processed' => false,
                'skipped' => true,
                'reason' => 'stale_webhook_delivery',
                'event' => $event,
                'order_id' => $order->id,
                'elim_order_id' => $elimOrderId,
            ];

            $this->logWebhook($event, $payload, $headers, 200, $result);

            return $result;
        }

        $hadRefundBefore = $order->cancellation_refund_transaction_id !== null;
        $updatedOrder = $this->lifecycle->applyWebhookOrderUpdate($order, $orderPayload, $payload);
        $refunded = ! $hadRefundBefore && $updatedOrder->cancellation_refund_transaction_id !== null;

        $result = [
            'processed' => true,
            'event' => $event,
            'order_id' => $updatedOrder->id,
            'elim_order_id' => $elimOrderId,
            'status' => $updatedOrder->status,
            'payment_status' => $updatedOrder->payment_status,
            'refunded' => $refunded,
        ];

        $this->logWebhook($event, $payload, $headers, 200, $result);

        return $result;
    }

    /**
     * GET /v1/webhooks — List registered webhooks (max 5 per user).
     *
     * @return array<string, mixed>
     */
    public function list(): array
    {
        return $this->client->get('/v1/webhooks');
    }

    /**
     * POST /v1/webhooks — Register a webhook endpoint and store its secret if returned.
     *
     * @param  list<string>  $events
     * @return array<string, mixed>
     */
    public function create(
        string $url,
        array $events = ['order.updated', 'payment.updated'],
        bool $isActive = true,
    ): array {
        $response = $this->client->post('/v1/webhooks', [
            'url' => trim($url),
            'events' => array_values($events),
            'is_active' => $isActive,
        ]);

        $data = is_array($response['data'] ?? null) ? $response['data'] : $response;

        if (! empty($data['id'])) {
            Setting::setValue(ElimApiConfig::SETTING_WEBHOOK_ID, (string) $data['id']);
        }

        if (! empty($data['secret']) && is_string($data['secret'])) {
            Setting::setValue(ElimApiConfig::SETTING_WEBHOOK_SECRET, $data['secret']);
        }

        return $response;
    }

    /**
     * PATCH /v1/webhooks/{id} — Update webhook URL, events, or active state.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function update(string|int $id, array $payload): array
    {
        return $this->client->patch('/v1/webhooks/'.rawurlencode((string) $id), $payload);
    }

    /**
     * DELETE /v1/webhooks/{id} — Delete a webhook.
     *
     * @return array<string, mixed>
     */
    public function delete(string|int $id): array
    {
        return $this->client->delete('/v1/webhooks/'.rawurlencode((string) $id));
    }

    /**
     * POST /v1/webhooks/{id}/rotate-secret — Generate a new signing secret and persist it locally.
     *
     * @return array<string, mixed>
     */
    public function rotateSecret(string|int $id): array
    {
        $response = $this->client->post('/v1/webhooks/'.rawurlencode((string) $id).'/rotate-secret');

        $data = is_array($response['data'] ?? null) ? $response['data'] : $response;
        $secret = $data['secret'] ?? Arr::get($response, 'data.secret');

        if (is_string($secret) && trim($secret) !== '') {
            Setting::setValue(ElimApiConfig::SETTING_WEBHOOK_SECRET, trim($secret));
        }

        return $response;
    }

    /**
     * GET /v1/webhooks/{id}/deliveries — Paginated delivery logs.
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function deliveries(string|int $id, array $query = []): array
    {
        return $this->client->get('/v1/webhooks/'.rawurlencode((string) $id).'/deliveries', array_filter(
            $query,
            static fn ($v): bool => $v !== null && $v !== ''
        ));
    }

    /**
     * POST /v1/webhooks/{id}/test — Send a test ping to the webhook URL.
     *
     * @return array<string, mixed>
     */
    public function test(string|int $id): array
    {
        return $this->client->post('/v1/webhooks/'.rawurlencode((string) $id).'/test');
    }

    private function isStaleWebhookDelivery(CustomerOrder $order, string $incomingUpdatedAt): bool
    {
        $snapshot = is_array($order->elim_detail_snapshot) ? $order->elim_detail_snapshot : [];
        $lastUpdatedAt = Arr::get($snapshot, '_last_webhook.order_updated_at')
            ?? Arr::get($snapshot, 'data.updated_at')
            ?? Arr::get($snapshot, 'updated_at');

        if (! is_string($lastUpdatedAt) || trim($lastUpdatedAt) === '') {
            return false;
        }

        try {
            $incomingTime = Carbon::parse($incomingUpdatedAt);
            $previousTime = Carbon::parse($lastUpdatedAt);

            return $incomingTime->lt($previousTime);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $headers
     * @param  array<string, mixed>  $result
     */
    private function logWebhook(string $event, array $payload, array $headers, int $status, array $result): void
    {
        $this->logger->log(
            method: 'POST',
            endpoint: '/api/v1/webhooks/elim',
            requestPayload: [
                'event' => $event,
                'headers' => $headers,
                'body' => $payload,
            ],
            responseBody: $result,
            statusCode: $status,
            isSuccessful: $status >= 200 && $status < 400,
            durationMs: 0,
            source: ElimApiLog::SOURCE_WEBHOOK,
        );
    }
}
