<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElimApiLog extends Model
{
    public const SOURCE_API = 'api';

    public const SOURCE_AUTH = 'auth';

    public const SOURCE_AUTH_TEST = 'auth_test';

    public const SOURCE_UPLOAD = 'upload';

    protected $fillable = [
        'method',
        'endpoint',
        'source',
        'status_code',
        'is_successful',
        'duration_ms',
        'request_payload',
        'response_body',
        'response_truncated',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'is_successful' => 'boolean',
            'request_payload' => 'array',
            'response_body' => 'array',
            'response_truncated' => 'boolean',
        ];
    }

    public function statusBadgeClass(): string
    {
        if (! $this->is_successful) {
            return 'danger';
        }

        return match (true) {
            $this->status_code >= 200 && $this->status_code < 300 => 'success',
            $this->status_code >= 300 && $this->status_code < 400 => 'info',
            default => 'warning',
        };
    }
}
