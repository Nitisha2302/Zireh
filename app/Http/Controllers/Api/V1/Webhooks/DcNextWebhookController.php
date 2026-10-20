<?php

namespace App\Http\Controllers\Api\V1\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\DcNextPayment;
use App\Services\DcNext\DcNextInvoiceService;
use App\Services\DcNext\DcNextPaymentSettler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DcNextWebhookController extends Controller
{
    public function __construct(
        private readonly DcNextInvoiceService $dcNext,
        private readonly DcNextPaymentSettler $settler,
    ) {}

    public function invoice(Request $request): JsonResponse
    {
        $payload = $request->all();
        Log::info('DC NEXT callback', $payload);

        $orderId = (string) ($payload['order_id'] ?? '');
        $sign = (string) ($payload['sign'] ?? '');

        if ($orderId === '') {
            return response()->json(['success' => false], 400);
        }

        if ($sign !== '' && ! $this->dcNext->verifySign($orderId, $sign)) {
            Log::warning('DC NEXT callback sign mismatch', ['order_id' => $orderId]);

            return response()->json(['success' => false, 'message' => 'Invalid sign'], 401);
        }

        $payment = DcNextPayment::query()->where('order_id', $orderId)->first();
        if (! $payment) {
            return response()->json(['success' => true, 'message' => 'Ignored']);
        }

        if ($payment->isPaid()) {
            return response()->json(['success' => true, 'message' => 'Already processed']);
        }

        $this->settler->markPaid($payment, $payload);

        return response()->json(['success' => true]);
    }
}
