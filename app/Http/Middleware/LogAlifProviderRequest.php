<?php

namespace App\Http\Middleware;

use App\Services\Alif\AlifApiLogger;
use App\Support\Alif\AlifRequestContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Times and records every inbound Alif provider request.
 *
 * Registered ahead of the authorization middleware so rejected-credential and
 * malformed-body attempts are captured too — those are exactly the rows worth
 * having when Alif reports the integration is failing.
 *
 * Because the write happens after the response is built, it also sits outside
 * the payment transaction: it cannot be rolled back with a failed payment and
 * cannot hold a row lock open.
 */
class LogAlifProviderRequest
{
    public function __construct(
        private readonly AlifApiLogger $logger,
        private readonly AlifRequestContext $context,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = hrtime(true);

        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            $this->write($request, null, $this->elapsedMs($startedAt), $exception->getMessage());

            throw $exception;
        }

        $this->write($request, $response, $this->elapsedMs($startedAt));

        return $response;
    }

    protected function write(
        Request $request,
        ?Response $response,
        float $durationMs,
        ?string $errorMessage = null,
    ): void {
        try {
            $this->logger->log(
                payload: $this->payload($request),
                result: $this->context->result(),
                responseBody: $response?->getContent(),
                httpStatus: $response?->getStatusCode(),
                authorized: (bool) $request->attributes->get(
                    EnsureAlifProviderIsAuthorized::AUTHORIZED_ATTRIBUTE,
                    false
                ),
                durationMs: $durationMs,
                ipAddress: $request->ip(),
                errorMessage: $errorMessage,
            );
        } catch (Throwable $exception) {
            // Logging is diagnostics. It must never turn a credited wallet into
            // an error, so the failure is noted and swallowed.
            Log::channel((string) config('alif.log_channel', 'alif'))
                ->warning('Failed to persist Alif request log.', [
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);
        }
    }

    protected function payload(Request $request): array
    {
        try {
            $payload = $request->json()->all();
        } catch (Throwable) {
            return [];
        }

        return is_array($payload) ? $payload : [];
    }

    protected function elapsedMs(int $startedAt): float
    {
        return round((hrtime(true) - $startedAt) / 1_000_000, 3);
    }
}
