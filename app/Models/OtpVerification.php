<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpVerification extends Model
{
    protected $fillable = [
        'phone',
        'purpose',
        'context',
        'code',
        'expires_at',
        'verified_at',
        'attempts',
        'resend_count',
        'last_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'resend_count' => 'integer',
        ];
    }
}
