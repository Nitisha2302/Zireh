<?php

namespace App\Services\Alif;

use App\Support\Alif\AlifAcquiringConfig;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class AlifAcquiringService
{
    public const ACTION_INIT = 'init';

    public const ACTION_CALLBACK = 'callback';

    public const ACTION_CHECKTXN = 'checktxn';

    public function __construct(
        private readonly AlifAcquiringConfig $config,
    ) {}

    public function generateToken(string $dataToSign): string
    {
        $secret = hash_hmac('sha256', $this->config->terminalPassword(), $this->config->terminalKey());

        return hash_hmac('sha256', $dataToSign, $secret);
    }

    public function initUrl(): string
    {
        return $this->config->baseUrl().'/v2/';
    }

    public function checktxnUrl(): string
    {
        return $this->config->baseUrl().'/checktxn';
    }

    /**
     * @return array{method: string, url: string, headers: array<string, string>, body: array<string, mixed>}
     */
    public function prepareInitRequest(
        string $orderId,
        string $amount,
        string $callbackUrl,
        string $returnUrl,
        string $phone,
        ?string $info = null,
        ?string $gate = null,
    ): array {
        $key = $this->config->terminalKey();
        $gate ??= $this->config->gate();
        $body = [
            'order_id' => $orderId,
            'amount' => $amount,
            'callback_url' => $callbackUrl,
            'return_url' => $returnUrl,
            'gate' => $gate,
            'phone' => $phone,
            'key' => $key,
            'token' => $this->generateToken($key.$orderId.$amount.$callbackUrl),
        ];

        if ($info !== null && $info !== '') {
            $body['info'] = $info;
        }

        return [
            'method' => 'POST',
            'url' => $this->initUrl(),
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'gate' => $gate,
            ],
            'body' => $body,
        ];
    }

    /**
     * @return array{method: string, url: string, headers: array<string, string>, body: array<string, mixed>}
     */
    public function prepareChecktxnRequest(string $orderId): array
    {
        $key = $this->config->terminalKey();

        return [
            'method' => 'POST',
            'url' => $this->checktxnUrl(),
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'body' => [
                'key' => $key,
                'orderId' => $orderId,
                'token' => $this->generateToken($key.$orderId),
            ],
        ];
    }

    /**
     * @return array{url: string, code: int, message: string}
     */
    public function initiatePayment(
        string $orderId,
        string $amount,
        string $callbackUrl,
        string $returnUrl,
        string $phone,
        ?string $info = null,
        ?string $gate = null,
    ): array {
        $request = $this->prepareInitRequest(
            $orderId,
            $amount,
            $callbackUrl,
            $returnUrl,
            $phone,
            $info,
            $gate,
        );

        $response = Http::timeout($this->config->timeout())
            ->withHeaders($request['headers'])
            ->post($request['url'], $request['body']);

        return $this->jsonOrFail(
            $response,
            'Invalid Alif response',
            requireInitSuccess: true,
            method: $request['method'],
            url: $request['url'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function checkTransactionStatus(string $orderId): array
    {
        $request = $this->prepareChecktxnRequest($orderId);

        $response = Http::timeout($this->config->timeout())
            ->withHeaders($request['headers'])
            ->post($request['url'], $request['body']);

        return $this->jsonOrFail(
            $response,
            'Invalid Alif status response',
            method: $request['method'],
            url: $request['url'],
        );
    }

    public function verifyPaymentCallbackToken(
        string $orderId,
        string $status,
        string $transactionId,
        string $providedToken,
    ): bool {
        $expected = $this->generateToken($orderId.$status.$transactionId);

        return hash_equals($expected, $providedToken);
    }

    public static function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if (str_starts_with($digits, '992')) {
            return $digits;
        }

        if (strlen($digits) === 9) {
            return '992'.$digits;
        }

        return $digits;
    }

    /**
     * @return array<string, mixed>
     */
    protected function jsonOrFail(
        Response $response,
        string $invalidMessage,
        bool $requireInitSuccess = false,
        string $method = 'POST',
        ?string $url = null,
    ): array {
        $status = $response->status();
        $raw = $response->body();
        $json = $response->json();

        if (! is_array($json)) {
            throw new AlifGatewayException(
                $invalidMessage,
                $status,
                $this->nonJsonSnapshot($response, $method, $url),
                $raw,
            );
        }

        if ($requireInitSuccess && ($status !== 200 || (int) ($json['code'] ?? 0) !== 200)) {
            throw new AlifGatewayException(
                (string) ($json['message'] ?? 'Alif payment init failed'),
                $status,
                $json,
                $raw,
            );
        }

        if (! $requireInitSuccess && ! $response->successful()) {
            throw new AlifGatewayException(
                (string) ($json['message'] ?? $invalidMessage),
                $status,
                $json,
                $raw,
            );
        }

        return $json;
    }

    /**
     * @return array<string, mixed>
     */
    protected function nonJsonSnapshot(Response $response, string $method, ?string $url): array
    {
        $raw = $response->body();
        $headers = [];

        foreach ($response->headers() as $name => $values) {
            $key = strtolower((string) $name);

            if (! in_array($key, ['allow', 'content-type', 'content-length', 'location', 'server', 'www-authenticate', 'x-request-id'], true)) {
                continue;
            }

            $headers[$key] = is_array($values) ? implode(', ', $values) : (string) $values;
        }

        return [
            'http_status' => $response->status(),
            'method' => $method,
            'url' => $url ?: (string) $response->effectiveUri(),
            'headers' => $headers,
            'body' => $raw === '' ? null : $raw,
            'note' => $raw === '' ? 'Empty Alif response' : 'Non-JSON Alif response',
        ];
    }
}
