<?php

namespace App\Services\DcNext;

use App\Support\DcNext\DcNextConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class DcNextInvoiceService
{
    public function createInvoice(
        string $orderId,
        float $amountTjs,
        string $phone,
        string $description,
    ): array {
        $merchant = config('dc_next.merchant');
        $secret = config('dc_next.secret');
        $callbackUrl = config('dc_next.callback_url');

        if ($merchant === '' || $secret === '') {
            throw new RuntimeException('DC NEXT is not configured.');
        }

        $payload = [
            'merchant' => $merchant,
            'order_id' => $orderId,
            'currency' => config('dc_next.default_currency', 'TJS'),
            'amount' => self::toMinorUnits($amountTjs),
            'exp_time' => config('dc_next.invoice_exp_minutes', 40),
            'phone' => self::normalizePhone($phone),
            'description' => mb_substr($description, 0, 150),
            'callback_url' => $callbackUrl,
            'sign' => self::signCreateOrCheck($merchant, $orderId, $secret),
        ];

        $response = Http::acceptJson()
            ->post(config('dc_next.create_url'), $payload);

        $body = $response->json();
        if (!$response->successful() || ($body['status'] ?? '') !== 'success') {
            $message = $body['message'] ?? $response->body();
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
        $merchant = config('dc_next.merchant');
        $secret = config('dc_next.secret');

        $payload = [
            'merchant' => $merchant,
            'order_id' => $orderId,
            'invoice_id' => $invoiceId,
            'sign' => self::signCreateOrCheck($merchant, $orderId, $secret),
        ];

        $response = Http::acceptJson()
            ->post(config('dc_next.check_url'), $payload);

        return $response->json() ?? [];
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

    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($digits, '992')) {
            return $digits;
        }
        if (strlen($digits) === 9) {
            return '992'.$digits;
        }

        return $digits;
    }

    /**
     * Parses /v1/check message text. Do NOT treat "<g>amount</g>" as paid —
     * unpaid invoices include that tag for the amount still due.
     */
    public static function interpretCheckAsPaid(array $checkResponse): bool
    {
        if (($checkResponse['status'] ?? '') === 'failed') {
            return false;
        }

        $message = mb_strtolower((string) ($checkResponse['message'] ?? ''));

        if ($message === '') {
            return false;
        }

        // Unpaid: "Сумма к оплате" / amount still to pay
        if (str_contains($message, 'к оплате') || str_contains($message, 'сумма к оплате')) {
            return false;
        }

        // Paid (heuristic until bank documents status codes)
        if (str_contains($message, 'оплачен') || str_contains($message, 'успешно оплачен')) {
            return true;
        }

        return false;
    }
}
