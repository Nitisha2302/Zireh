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
        $key = $this->config->terminalKey();
        $gate ??= $this->config->gate();
        $token = $this->generateToken($key.$orderId.$amount.$callbackUrl);

        $body = [
            'order_id' => $orderId,
            'amount' => $amount,
            'callback_url' => $callbackUrl,
            'return_url' => $returnUrl,
            'gate' => $gate,
            'phone' => $phone,
            'key' => $key,
            'token' => $token,
        ];

        if ($info !== null && $info !== '') {
            $body['info'] = $info;
        }

        $response = Http::timeout($this->config->timeout())
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'gate' => $gate,
            ])
            ->post($this->config->baseUrl().'/v2/', $body);

        return $this->jsonOrFail(
            $response,
            'Invalid Alif response',
            requireInitSuccess: true,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function checkTransactionStatus(string $orderId): array
    {
        $key = $this->config->terminalKey();
        $token = $this->generateToken($key.$orderId);

        $response = Http::timeout($this->config->timeout())
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->post($this->config->baseUrl().'/checktxn', [
                'key' => $key,
                'orderId' => $orderId,
                'token' => $token,
            ]);

        return $this->jsonOrFail($response, 'Invalid Alif status response');
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
    protected function jsonOrFail(Response $response, string $invalidMessage, bool $requireInitSuccess = false): array
    {
        $status = $response->status();
        $raw = $response->body();
        $json = $response->json();

        if (! is_array($json)) {
            throw new AlifGatewayException($invalidMessage, $status, $raw, $raw);
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
}
