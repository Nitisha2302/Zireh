<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlifPayment extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'payment_id',
        'response_id',
        'account',
        'user_id',
        'amount',
        'currency',
        'status',
        'code',
        'srv_id',
        'is_commercial',
        'paid_at',
        'wallet_transaction_id',
        'request_payload',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_commercial' => 'boolean',
            'paid_at' => 'datetime',
            'request_payload' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    /**
     * Every Alif call made against this payment id, so retries are visible together.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(AlifApiLog::class, 'payment_id', 'payment_id');
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'success',
            self::STATUS_REJECTED => 'danger',
            default => 'warning',
        };
    }
}
