<?php

namespace App\Http\Controllers\Alif;

use App\Http\Controllers\Controller;
use App\Http\Responses\AlifResponse;
use App\Services\Alif\Contracts\AlifPaymentServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The single provider endpoint Alif posts to. The request type lives in the
 * body's `action` field, so there is one route for check, pay and status.
 */
final class AlifProviderController extends Controller
{
    public function __construct(
        private readonly AlifPaymentServiceInterface $service,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        return AlifResponse::make($this->service->handle($this->payload($request)));
    }

    private function payload(Request $request): array
    {
        $payload = $request->json()->all();

        return is_array($payload) ? $payload : [];
    }
}
