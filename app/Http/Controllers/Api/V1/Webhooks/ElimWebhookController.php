<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Elim\ElimWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ElimWebhookController extends Controller
{
    public function __construct(
        private readonly ElimWebhookService $webhookService,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $rawBody = (string) $request->getContent();
        $timestamp = (string) $request->header('X-Elim-Timestamp', '');
        $signature = (string) $request->header('X-Elim-Signature', '');

        if (! $this->webhookService->verifySignature($rawBody, $timestamp, $signature)) {
            return response()->json([
                'success' => false,
                'message' => __('api.elim.webhook_invalid_signature'),
                'error' => 'invalid_signature',
            ], 401);
        }

        $payload = json_decode($rawBody, true);
        if (! is_array($payload)) {
            $payload = $request->all();
        }

        $result = $this->webhookService->handleWebhook($payload, [
            'X-Elim-Timestamp' => $timestamp,
            'X-Elim-Signature' => $signature,
            'X-Elim-Event' => (string) $request->header('X-Elim-Event', ''),
        ]);

        return response()->json([
            'success' => true,
            'message' => __('api.elim.webhook_received'),
            'data' => $result,
        ], 200);
    }
}
