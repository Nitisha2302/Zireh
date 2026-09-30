<?php

namespace App\Http\Middleware;

use App\Http\Responses\AlifResponse;
use App\Support\Alif\AlifProviderConfig;
use App\Support\Alif\AlifResponseCode;
use App\Support\Alif\AlifResult;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Authorizes Alif provider requests.
 *
 * Alif sends `Authorization: BASE64("Login:Password")` — bare base64, with no
 * scheme prefix. The header is compared with hash_equals and is never logged,
 * stored, or echoed back.
 */
class EnsureAlifProviderIsAuthorized
{
    public const AUTHORIZED_ATTRIBUTE = 'alif_authorized';

    public function handle(Request $request, Closure $next): Response
    {
        $config = app(AlifProviderConfig::class);
        $login = $config->login();
        $password = $config->password();

        if ($login === '' || $password === '') {
            return $this->reject($request);
        }

        $provided = $this->providedCredentials($request);
        $expected = base64_encode($login.':'.$password);

        if ($provided === null || ! hash_equals($expected, $provided)) {
            return $this->reject($request);
        }

        $request->attributes->set(self::AUTHORIZED_ATTRIBUTE, true);

        return $next($request);
    }

    protected function providedCredentials(Request $request): ?string
    {
        $header = trim((string) $request->header('Authorization', ''));

        if ($header === '') {
            return null;
        }

        // The documented format has no scheme, but tolerate "Basic " so a
        // standards-following client still authenticates.
        if (Str::startsWith(strtolower($header), 'basic ')) {
            $header = trim(substr($header, strlen('basic ')));
        }

        return $header !== '' ? $header : null;
    }

    protected function reject(Request $request): Response
    {
        return AlifResponse::make(AlifResult::failure(
            AlifResponseCode::UNAUTHORIZED,
            $this->requestedPaymentId($request),
            'Authorization failed.',
        ));
    }

    /**
     * Echo back the payment id when the body is parseable, so Alif can
     * correlate the rejection with its own record.
     */
    protected function requestedPaymentId(Request $request): mixed
    {
        try {
            $id = $request->json('id');
        } catch (Throwable) {
            return null;
        }

        return is_string($id) || is_int($id) ? $id : null;
    }
}
