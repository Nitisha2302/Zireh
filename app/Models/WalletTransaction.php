<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WalletTransaction extends Model
{
    public const TYPE_CREDIT = 'credit';

    public const TYPE_DEBIT = 'debit';

    public const SOURCE_ADMIN_DEPOSIT = 'admin_deposit';

    public const SOURCE_ADMIN_REVERT = 'admin_revert';

    public const SOURCE_ADMIN_DEDUCT = 'admin_deduct';

    public const SOURCE_ORDER_PAYMENT = 'order_payment';

    public const SOURCE_ORDER_REFUND = 'order_refund';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_REVERTED = 'reverted';

    protected $fillable = [
        'user_id',
        'admin_id',
        'type',
        'source',
        'amount',
        'balance_before',
        'balance_after',
        'currency',
        'status',
        'description',
        'reference_type',
        'reference_id',
        'reverts_transaction_id',
        'reverted_by_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function revertsTransaction(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverts_transaction_id');
    }

    public function revertedByTransaction(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverted_by_transaction_id');
    }

    public function isRevertable(): bool
    {
        return $this->source === self::SOURCE_ADMIN_DEPOSIT
            && $this->status === self::STATUS_COMPLETED
            && $this->reverted_by_transaction_id === null;
    }

    public function signedAmount(): float
    {
        return $this->type === self::TYPE_CREDIT
            ? (float) $this->amount
            : -(float) $this->amount;
    }
}
