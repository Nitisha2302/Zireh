<?php

namespace App\Models;

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
}
