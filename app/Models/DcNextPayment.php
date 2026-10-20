<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DcNextPayment extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const PURPOSE_WALLET_TOPUP = 'wallet_topup';

    protected $fillable = [
        'user_id',
        'purpose',
        'order_id',
        'invoice_id',
        'amount_tjs',
        'status',
        'payid',
        'paid_at',
        'callback_payload',
        'wallet_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'amount_tjs' => 'decimal:2',
            'callback_payload' => 'array',
            'paid_at' => 'datetime',
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

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'success',
            self::STATUS_FAILED => 'danger',
            default => 'warning',
        };
    }
}
