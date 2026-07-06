<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @property-read float $payment_amount_cny */

class CustomerOrder extends Model
{
    public const PLATFORM_TAOBAO = 'taobao';

    public const PLATFORM_1688 = '1688';

    protected $fillable = [
        'user_id',
        'platform_id',
        'platform',
        'elim_order_id',
        'status',
        'payment_status',
        'goods_subtotal_cny',
        'shipping_fee_cny',
        'elim_service_fee_cny',
        'commission_slab_id',
        'commission_percentage',
        'commission_amount',
        'customer_total_cny',
        'exchange_rate',
        'customer_total_tjs',
        'receiver_address',
        'remark',
        'elim_preview_snapshot',
        'elim_create_snapshot',
        'elim_detail_snapshot',
        'paid_at',
        'wallet_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'goods_subtotal_cny' => 'decimal:2',
            'shipping_fee_cny' => 'decimal:2',
            'elim_service_fee_cny' => 'decimal:2',
            'commission_percentage' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'customer_total_cny' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'customer_total_tjs' => 'decimal:2',
            'receiver_address' => 'array',
            'elim_preview_snapshot' => 'array',
            'elim_create_snapshot' => 'array',
            'elim_detail_snapshot' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function paymentAmountCny(): float
    {
        return round(
            (float) $this->goods_subtotal_cny
            + (float) $this->shipping_fee_cny
            + (float) ($this->elim_service_fee_cny ?? 0)
            + (float) $this->commission_amount,
            2
        );
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, ['creating', 'pending_payment'], true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function platformModel(): BelongsTo
    {
        return $this->belongsTo(Platform::class, 'platform_id');
    }

    public function commissionSlab(): BelongsTo
    {
        return $this->belongsTo(PlatformCommissionSlab::class, 'commission_slab_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomerOrderItem::class);
    }

    public function orderStatus(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'status', 'code');
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }
}
