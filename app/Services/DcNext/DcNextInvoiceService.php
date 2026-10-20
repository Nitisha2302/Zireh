<?php

namespace App\Services\DcNext;

use App\Support\DcNext\DcNextConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class DcNextInvoiceService
{
    public function __construct(
        private readonly DcNextConfig $config,
    ) {}

    public function createInvoice(
        string $orderId,
        float $amountTjs,
        string $phone,
        string $description,
    ): array {
        $merchant = $this->config->merchant();
        $secret = $this->config->secret();
        $callbackUrl = $this->config->callbackUrl();

        if ($merchant === '' || $secret === '') {
            throw new RuntimeException('DC NEXT is not configured.');
        }

        $payload = [
            'merchant' => $merchant,
            'order_id' => $orderId,
            'currency' => $this->config->currency(),
            'amount' => self::toMinorUnits($amountTjs),
            'exp_time' => $this->config->expMinutes(),
            'phone' => self::normalizePhone($phone),
            'description' => mb_substr($description, 0, 150),
            'callback_url' => $callbackUrl,
            'sign' => self::signCreateOrCheck($merchant, $orderId, $secret),
        ];

        $response = Http::timeout($this->config->timeout())
            ->acceptJson()
            ->post($this->config->createUrl(), $payload);

        $body = $response->json();
        if (! $response->successful() || ($body['status'] ?? '') !== 'success') {
            $message = is_array($body) ? ($body['message'] ?? $response->body()) : $response->body();

            Log::error('DC NEXT create invoice failed', [
                'order_id' => $orderId,
                'http_status' => $response->status(),
                'message' => $message,
            ]);

            throw new RuntimeException('DC NEXT create failed: '.$message);
        }

        return [
            'order_id' => (string) ($body['order_id'] ?? $orderId),
            'invoice_id' => (string) ($body['invoice_id'] ?? ''),
            'payment_url' => (string) ($body['invoice_url'] ?? $body['payment_url'] ?? ''),
            'exp_time' => $body['exp_time'] ?? null,
            'raw' => $body,
        ];
    }

    public function checkInvoice(string $orderId, string $invoiceId): array
    {
        $merchant = $this->config->merchant();
        $secret = $this->config->secret();

        $payload = [
            'merchant' => $merchant,
            'order_id' => $orderId,
            'invoice_id' => $invoiceId,
            'sign' => self::signCreateOrCheck($merchant, $orderId, $secret),
        ];

        $response = Http::timeout($this->config->timeout())
            ->acceptJson()
            ->post($this->config->checkUrl(), $payload);

        if (! $response->successful()) {
            Log::warning('DC NEXT check invoice HTTP error', [
                'order_id' => $orderId,
                'invoice_id' => $invoiceId,
                'http_status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        return $response->json() ?? [];
    }

    public function verifySign(string $orderId, string $providedSign): bool
    {
        $expected = self::signCreateOrCheck(
            $this->config->merchant(),
            $orderId,
            $this->config->secret(),
        );

        return hash_equals($expected, $providedSign);
    }

    public static function signCreateOrCheck(
        string $merchant,
        string $orderId,
        string $secret,
    ): string {
        return md5($merchant.$orderId.$secret);
    }

    /** TJS somoni → diram (API minor units). 1.01 TJS → 101 */
    public static function toMinorUnits(float $amountTjs): int
    {
        return (int) round($amountTjs * 100);
    }

    public static function fromMinorUnits(int $minor): float
    {
        return round($minor / 100, 2);
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
}
