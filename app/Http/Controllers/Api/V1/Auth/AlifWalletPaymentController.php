<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\ApiController;
use App\Models\AlifPayment;
use App\Models\User;
use App\Services\Alif\AlifAcquiringService;
use App\Services\Alif\AlifApiLogger;
use App\Services\Alif\AlifGatewayException;
use App\Services\Alif\AlifPaymentSettler;
use App\Support\Alif\AlifAcquiringConfig;
use App\Support\Alif\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class AlifWalletPaymentController extends ApiController
{
    public function __construct(
        private readonly AlifAcquiringService $alif,
        private readonly AlifAcquiringConfig $config,
        private readonly AlifPaymentSettler $settler,
        private readonly AlifApiLogger $logger,
    ) {}

    public function init(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $amount = Money::tryParse($validated['amount']);

        if (! $amount || ! $amount->isPositive()) {
            return $this->errorResponse(__('api.alif_amount_invalid'), ['amount' => [__('api.alif_amount_invalid')]], 422);
        }

        if (! $amount->isWithin($this->config->minAmount(), $this->config->maxAmount())) {
            return $this->errorResponse(__('api.alif_amount_out_of_range'), ['amount' => [__('api.alif_amount_out_of_range')]], 422);
        }

        if (! $this->config->credentialsAreConfigured() || ! $this->config->urlsAreConfigured()) {
            return $this->errorResponse(__('api.alif_not_configured'), [], 500);
        }

        /** @var User $customer */
        $customer = $request->user();
        $phone = AlifAcquiringService::normalizePhone($customer->phone);

        if ($phone === '') {
            return $this->errorResponse(__('api.alif_phone_required'), ['phone' => [__('api.alif_phone_required')]], 422);
        }

        $orderId = 'WU-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));
        $info = $validated['description'] ?? 'Wallet top-up';
        $startedAt = hrtime(true);
        $requestPayload = $this->alif->prepareInitRequest(
            orderId: $orderId,
            amount: $amount->value(),
            callbackUrl: $this->config->callbackUrl(),
            returnUrl: $this->config->returnUrl(),
            phone: $phone,
            info: $info,
        );

        try {
            $alifResponse = $this->alif->initiatePayment(
                orderId: $orderId,
                amount: $amount->value(),
                callbackUrl: $this->config->callbackUrl(),
                returnUrl: $this->config->returnUrl(),
                phone: $phone,
                info: $info,
            );
        } catch (Throwable $exception) {
            $logFields = $this->logFieldsFromException($exception);

            $this->logSafely(
                action: AlifAcquiringService::ACTION_INIT,
                requestPayload: $requestPayload,
                responseBody: $logFields['responseBody'],
                httpStatus: $logFields['httpStatus'],
                successful: false,
                durationMs: $this->elapsedMs($startedAt),
                orderId: $orderId,
                account: $phone,
                amount: $amount->value(),
                responseCode: $logFields['responseCode'],
                ipAddress: $request->ip(),
                errorMessage: $logFields['errorMessage'],
            );

            Log::channel((string) config('alif.log_channel', 'alif'))
                ->error('Alif init failed', [
                    'message' => $exception->getMessage(),
                    'http_status' => $logFields['httpStatus'],
                ]);

            return $this->errorResponse($exception->getMessage(), [], 502);
        }

        $payment = AlifPayment::query()->create([
            'user_id' => $customer->id,
            'order_id' => $orderId,
            'purpose' => AlifPayment::PURPOSE_WALLET_TOPUP,
            'amount' => $amount->value(),
            'currency' => $this->config->currency(),
            'status' => AlifPayment::STATUS_PENDING,
            'gate' => $this->config->gate(),
            'payment_url' => $alifResponse['url'] ?? null,
        ]);

        $this->logSafely(
            action: AlifAcquiringService::ACTION_INIT,
            requestPayload: $requestPayload,
            responseBody: $alifResponse,
            httpStatus: 200,
            successful: true,
            durationMs: $this->elapsedMs($startedAt),
            orderId: $orderId,
            account: $phone,
            amount: $amount->value(),
            responseCode: (int) ($alifResponse['code'] ?? 200),
            ipAddress: $request->ip(),
            alifPaymentId: $payment->id,
        );

        return $this->successResponse([
            'order_id' => $orderId,
            'payment_url' => $alifResponse['url'] ?? '',
            'amount' => $amount->value(),
            'status' => AlifPayment::STATUS_PENDING,
        ], __('api.alif_payment_initialized'));
    }

    public function status(Request $request, string $orderId): JsonResponse
    {
        /** @var User $customer */
        $customer = $request->user();

        $payment = AlifPayment::query()
            ->where('order_id', $orderId)
            ->where('user_id', $customer->id)
            ->first();

        if ($payment === null) {
            return $this->errorResponse(__('api.alif_payment_not_found'), [], 404);
        }

        if (! $payment->isPaid()) {
            $this->refreshFromAlif($payment, $request);
            $payment->refresh();
        }

        return $this->successResponse($this->statusPayload($payment));
    }

    public function callback(Request $request): JsonResponse
    {
        $payload = $request->all();
        $startedAt = hrtime(true);

        $orderId = (string) ($payload['orderId'] ?? $payload['order_id'] ?? '');
        $status = strtolower((string) ($payload['status'] ?? ''));
        $transactionId = (string) ($payload['transactionId'] ?? $payload['transaction_id'] ?? '');
        $token = (string) ($payload['token'] ?? '');

        if ($orderId === '' || $status === '' || $transactionId === '' || $token === '') {
            $this->logSafely(
                action: AlifAcquiringService::ACTION_CALLBACK,
                requestPayload: $payload,
                responseBody: ['ok' => true],
                httpStatus: 200,
                successful: false,
                durationMs: $this->elapsedMs($startedAt),
                orderId: $orderId !== '' ? $orderId : null,
                ipAddress: $request->ip(),
                errorMessage: 'Incomplete callback payload.',
            );

            return response()->json(['ok' => true]);
        }

        if (! $this->alif->verifyPaymentCallbackToken($orderId, $status, $transactionId, $token)) {
            $this->logSafely(
                action: AlifAcquiringService::ACTION_CALLBACK,
                requestPayload: $payload,
                responseBody: ['ok' => false],
                httpStatus: 400,
                successful: false,
                durationMs: $this->elapsedMs($startedAt),
                orderId: $orderId,
                authorized: false,
                ipAddress: $request->ip(),
                errorMessage: 'Invalid callback token.',
            );

            Log::channel((string) config('alif.log_channel', 'alif'))
                ->warning('Alif callback token invalid', ['order_id' => $orderId]);

            return response()->json(['ok' => false], 400);
        }

        $payment = AlifPayment::query()->where('order_id', $orderId)->first();

        if ($payment === null) {
            $this->logSafely(
                action: AlifAcquiringService::ACTION_CALLBACK,
                requestPayload: $payload,
                responseBody: ['ok' => true],
                httpStatus: 200,
                successful: false,
                durationMs: $this->elapsedMs($startedAt),
                orderId: $orderId,
                ipAddress: $request->ip(),
                errorMessage: 'Unknown order id.',
            );

            return response()->json(['ok' => true]);
        }

        if ($status === 'ok') {
            $this->settler->markPaid($payment, $payload);
        } elseif (in_array($status, ['failed', 'canceled', 'cancelled'], true)) {
            $this->settler->markFailed($payment, $payload);
        }

        $this->logSafely(
            action: AlifAcquiringService::ACTION_CALLBACK,
            requestPayload: $payload,
            responseBody: ['ok' => true],
            httpStatus: 200,
            successful: $status === 'ok',
            durationMs: $this->elapsedMs($startedAt),
            orderId: $orderId,
            account: AlifAcquiringService::normalizePhone($payment->user?->phone),
            amount: $payment->amount,
            ipAddress: $request->ip(),
            alifPaymentId: $payment->id,
        );

        return response()->json(['ok' => true]);
    }

    protected function refreshFromAlif(AlifPayment $payment, Request $request): void
    {
        $startedAt = hrtime(true);
        $requestPayload = $this->alif->prepareChecktxnRequest($payment->order_id);

        try {
            $remote = $this->alif->checkTransactionStatus($payment->order_id);
            $remoteStatus = strtolower((string) ($remote['status'] ?? ''));

            if ($remoteStatus === 'ok') {
                $this->settler->markPaid($payment, $remote);
            } elseif (in_array($remoteStatus, ['failed', 'canceled', 'cancelled'], true)) {
                $this->settler->markFailed($payment, $remote);
            }

            $this->logSafely(
                action: AlifAcquiringService::ACTION_CHECKTXN,
                requestPayload: $requestPayload,
                responseBody: $remote,
                httpStatus: 200,
                successful: $remoteStatus === 'ok',
                durationMs: $this->elapsedMs($startedAt),
                orderId: $payment->order_id,
                amount: $payment->amount,
                ipAddress: $request->ip(),
                alifPaymentId: $payment->id,
            );
        } catch (Throwable $exception) {
            $logFields = $this->logFieldsFromException($exception);

            $this->logSafely(
                action: AlifAcquiringService::ACTION_CHECKTXN,
                requestPayload: $requestPayload,
                responseBody: $logFields['responseBody'],
                httpStatus: $logFields['httpStatus'],
                successful: false,
                durationMs: $this->elapsedMs($startedAt),
                orderId: $payment->order_id,
                amount: $payment->amount,
                responseCode: $logFields['responseCode'],
                ipAddress: $request->ip(),
                errorMessage: $logFields['errorMessage'],
                alifPaymentId: $payment->id,
            );

            Log::channel((string) config('alif.log_channel', 'alif'))
                ->warning('Alif status check failed', [
                    'order_id' => $payment->order_id,
                    'message' => $exception->getMessage(),
                    'http_status' => $logFields['httpStatus'],
                ]);
        }
    }

    /**
     * @return array{order_id: string, status: string, amount: string, transaction_id: ?string}
     */
    protected function statusPayload(AlifPayment $payment): array
    {
        return [
            'order_id' => $payment->order_id,
            'status' => $payment->status,
            'amount' => $payment->amount,
            'transaction_id' => $payment->alif_transaction_id,
        ];
    }

    protected function elapsedMs(int $startedAt): float
    {
        return round((hrtime(true) - $startedAt) / 1_000_000, 3);
    }

    /**
     * @return array{responseBody: mixed, httpStatus: int, responseCode: int, errorMessage: string}
     */
    protected function logFieldsFromException(Throwable $exception): array
    {
        if ($exception instanceof AlifGatewayException) {
            return [
                'responseBody' => $exception->body,
                'httpStatus' => $exception->httpStatusForLog(),
                'responseCode' => $exception->responseCodeForLog(),
                'errorMessage' => $exception->getMessage(),
            ];
        }

        return [
            'responseBody' => ['message' => $exception->getMessage()],
            'httpStatus' => 502,
            'responseCode' => 502,
            'errorMessage' => $exception->getMessage(),
        ];
    }

    protected function logSafely(
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
    ): void {
        try {
            $this->logger->record(
                action: $action,
                requestPayload: $requestPayload,
                responseBody: $responseBody,
                httpStatus: $httpStatus,
                successful: $successful,
                durationMs: $durationMs,
                orderId: $orderId,
                account: $account,
                amount: $amount,
                responseCode: $responseCode,
                authorized: $authorized,
                ipAddress: $ipAddress,
                errorMessage: $errorMessage,
                alifPaymentId: $alifPaymentId,
            );
        } catch (Throwable $exception) {
            Log::channel((string) config('alif.log_channel', 'alif'))
                ->warning('Failed to persist Alif request log.', [
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);
        }
    }
}
