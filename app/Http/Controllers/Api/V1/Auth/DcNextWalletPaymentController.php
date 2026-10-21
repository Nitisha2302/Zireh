<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\ApiController;
use App\Models\DcNextPayment;
use App\Models\User;
use App\Services\DcNext\DcNextInvoiceService;
use App\Services\DcNext\DcNextPaymentSettler;
use App\Support\Alif\Money;
use App\Support\DcNext\DcNextConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class DcNextWalletPaymentController extends ApiController
{
    public function __construct(
        private readonly DcNextInvoiceService $dcNext,
        private readonly DcNextConfig $config,
        private readonly DcNextPaymentSettler $settler,
    ) {}

    public function init(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:150'],
        ]);

        $amount = Money::tryParse($validated['amount']);

        if (! $amount || ! $amount->isPositive()) {
            return $this->errorResponse(__('api.dc_next_amount_invalid'), ['amount' => [__('api.dc_next_amount_invalid')]], 422);
        }

        if (! $amount->isWithin($this->config->minAmount(), $this->config->maxAmount())) {
            return $this->errorResponse(__('api.dc_next_amount_out_of_range'), ['amount' => [__('api.dc_next_amount_out_of_range')]], 422);
        }

        if (! $this->config->credentialsAreConfigured() || ! $this->config->urlsAreConfigured()) {
            return $this->errorResponse(__('api.dc_next_not_configured'), [], 500);
        }

        /** @var User $customer */
        $customer = $request->user();
        $phone = DcNextInvoiceService::normalizePhone($customer->phone);

        if ($phone === '') {
            return $this->errorResponse(__('api.dc_next_phone_required'), ['phone' => [__('api.dc_next_phone_required')]], 422);
        }

        $orderId = 'wallet_'.$customer->id.'_'.time().'_'.Str::lower(Str::random(4));
        $description = $validated['description'] ?? 'Wallet top-up';

        try {
            $invoice = $this->dcNext->createInvoice(
                orderId: $orderId,
                amountTjs: (float) $amount->value(),
                phone: $phone,
                description: $description,
            );
        } catch (Throwable $exception) {
            Log::error('DC NEXT init failed', [
                'order_id' => $orderId,
                'user_id' => $customer->id,
                'message' => $exception->getMessage(),
            ]);

            return $this->errorResponse($exception->getMessage(), [], 502);
        }

        $payment = DcNextPayment::query()->create([
            'user_id' => $customer->id,
            'purpose' => DcNextPayment::PURPOSE_WALLET_TOPUP,
            'order_id' => $invoice['order_id'],
            'invoice_id' => $invoice['invoice_id'] !== '' ? $invoice['invoice_id'] : null,
            'amount_tjs' => $amount->value(),
            'status' => DcNextPayment::STATUS_PENDING,
        ]);

        return $this->successResponse([
            'order_id' => $payment->order_id,
            'invoice_id' => (string) ($payment->invoice_id ?? ''),
            'payment_url' => $invoice['payment_url'],
            'amount' => $payment->amount_tjs,
            'status' => $payment->status,
        ], __('api.dc_next_payment_initialized'));
    }

    public function status(Request $request, string $orderId): JsonResponse
    {
        /** @var User $customer */
        $customer = $request->user();

        $payment = DcNextPayment::query()
            ->where('user_id', $customer->id)
            ->where('order_id', $orderId)
            ->first();

        if ($payment === null) {
            return $this->errorResponse(__('api.dc_next_payment_not_found'), [], 404);
        }

        if (! $payment->isPaid()) {
            $this->refreshFromDcNext($payment);
            $payment->refresh();
        }

        return $this->successResponse($this->statusPayload($payment));
    }

    protected function refreshFromDcNext(DcNextPayment $payment): void
    {
        try {
            $check = $this->dcNext->checkInvoice($payment->order_id, (string) ($payment->invoice_id ?? ''));
            $remoteStatus = strtolower((string) ($check['status'] ?? ''));
            $message = mb_strtolower((string) ($check['message'] ?? ''));

            if ($remoteStatus === 'failed') {
                $this->settler->markFailed($payment, $check);

                return;
            }

            // DC /check "success" = API call OK, NOT "invoice paid".
            if ($this->checkMessageIndicatesPaid($message)) {
                $this->settler->markPaid($payment, $check);

                return;
            }

            if (in_array($remoteStatus, ['failed', 'canceled', 'cancelled', 'expired'], true)) {
                $this->settler->markFailed($payment, $check);
            }
        } catch (Throwable $exception) {
            Log::warning('DC NEXT status check failed', [
                'order_id' => $payment->order_id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Unpaid invoices still contain "<g>50.00</g>" (amount due) — never treat that as paid.
     */
    protected function checkMessageIndicatesPaid(string $messageLower): bool
    {
        if ($messageLower === '') {
            return false;
        }

        if (str_contains($messageLower, 'к оплате') || str_contains($messageLower, 'сумма к оплате')) {
            return false;
        }

        return str_contains($messageLower, 'оплачен')
            || str_contains($messageLower, 'успешно оплачен');
    }

    /**
     * @return array{order_id: string, status: string, amount: string, transaction_id: ?string}
     */
    protected function statusPayload(DcNextPayment $payment): array
    {
        return [
            'order_id' => $payment->order_id,
            'status' => $payment->status,
            'amount' => $payment->amount_tjs,
            'transaction_id' => $payment->payid,
        ];
    }
}