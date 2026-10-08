<?php

namespace App\Models;

use App\Support\Alif\AlifAcquiringConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlifApiLog extends Model
{
    protected $fillable = [
        'action',
        'payment_id',
        'account',
        'amount',
        'response_code',
        'http_status',
        'authorized',
        'is_successful',
        'duration_ms',
        'ip_address',
        'request_payload',
        'response_body',
        'response_truncated',
        'error_message',
        'alif_payment_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'duration_ms' => 'decimal:3',
            'authorized' => 'boolean',
            'is_successful' => 'boolean',
            'response_truncated' => 'boolean',
            'request_payload' => 'array',
            'response_body' => 'array',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(AlifPayment::class, 'alif_payment_id');
    }

    public function responseCode(): ?int
    {
        return $this->response_code === null ? null : (int) $this->response_code;
    }

    public function codeLabel(): ?string
    {
        if ($this->is_successful) {
            return __('admin.success');
        }

        return $this->error_message ?: __('admin.failed');
    }

    public function codeBadgeClass(): string
    {
        return $this->is_successful ? 'success' : 'danger';
    }

    /**
     * Trailing zeros are trimmed so a sub-millisecond handler reads as
     * "0.42 ms" rather than "0.420 ms" or, worse, "0 ms".
     */
    public function formattedDuration(): ?string
    {
        if ($this->duration_ms === null) {
            return null;
        }

        $value = rtrim(rtrim(number_format((float) $this->duration_ms, 3, '.', ''), '0'), '.');

        return ($value === '' ? '0' : $value).' ms';
    }

    public function formattedResponseBody(): ?string
    {
        $body = $this->response_body;

        if (is_array($body) && array_key_exists('_raw', $body) && count($body) === 1) {
            $raw = (string) $body['_raw'];

            if (trim($raw) !== '') {
                return $raw;
            }

            $body = null;
        }

        if ($body === null || $body === [] || $body === '') {
            if ($this->http_status === null && ($this->error_message === null || $this->error_message === '')) {
                return null;
            }

            $body = array_filter([
                'http_status' => $this->http_status,
                'error' => $this->error_message,
                'note' => 'Empty Alif response',
            ], fn (mixed $value) => $value !== null && $value !== '');
        }

        $encoded = json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded === false ? null : $encoded;
    }

    public function formattedCurl(): ?string
    {
        $parts = $this->curlParts();

        if ($parts === null) {
            return null;
        }

        $jsonFlags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        $body = json_encode($parts['body'], $jsonFlags);

        if ($body === false) {
            $body = '{}';
        }

        $lines = ['curl -i -X '.$parts['method'].' '.$this->shellQuote($parts['url']).' \\'];

        foreach ($parts['headers'] as $name => $value) {
            $lines[] = '  -H '.$this->shellQuote($name.': '.$value).' \\';
        }

        $lines[] = '  --data-raw '.$this->shellQuote($body);
        $lines[] = '';
        $lines[] = '# Response'.($this->http_status !== null ? ' HTTP '.$this->http_status : '');
        $lines[] = $this->formattedResponseBody() ?: '(empty)';

        return implode("\n", $lines);
    }

    /**
     * @return array{method: string, url: string, headers: array<string, string>, body: array<string, mixed>}|null
     */
    protected function curlParts(): ?array
    {
        $payload = is_array($this->request_payload) ? $this->request_payload : [];
        $response = is_array($this->response_body) ? $this->response_body : [];

        $url = (string) ($payload['url'] ?? $response['url'] ?? $this->inferredGatewayUrl() ?? '');

        if ($url === '') {
            return null;
        }

        $headers = is_array($payload['headers'] ?? null) ? $payload['headers'] : $this->defaultCurlHeaders($payload);
        $body = is_array($payload['body'] ?? null)
            ? $payload['body']
            : array_diff_key($payload, array_flip(['method', 'url', 'headers', 'body']));

        return [
            'method' => strtoupper((string) ($payload['method'] ?? $response['method'] ?? 'POST')),
            'url' => $url,
            'headers' => $headers,
            'body' => $body,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, string>
     */
    protected function defaultCurlHeaders(array $payload): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $gate = $payload['gate'] ?? (is_array($payload['body'] ?? null) ? ($payload['body']['gate'] ?? null) : null);

        if (is_string($gate) && $gate !== '') {
            $headers['gate'] = $gate;
        }

        return $headers;
    }

    protected function inferredGatewayUrl(): ?string
    {
        $base = rtrim(app(AlifAcquiringConfig::class)->baseUrl(), '/');

        return match ($this->action) {
            'init' => $base.'/v2/',
            'checktxn' => $base.'/checktxn',
            default => null,
        };
    }

    protected function shellQuote(string $value): string
    {
        return "'".str_replace("'", "'\\''", $value)."'";
    }
}
