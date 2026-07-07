<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @property-read float $payment_amount_cny */
/** @property-read float $payment_amount_tjs */

class CustomerOrder extends Model
{
    public const PLATFORM_TAOBAO = 'taobao';

    public const PLATFORM_1688 = '1688';

    public const PAYMENT_METHOD_WALLET = 'wallet';

    public const PAYMENT_METHOD_ONLINE = 'online';

    protected $fillable = [
        'user_id',
        'warehouse_id',
        'user_address_id',
        'shipping_method_id',
        'platform_id',
        'platform',
        'elim_order_id',
        'status',
        'payment_status',
        'payment_method',
        'goods_subtotal_cny',
        'shipping_fee_cny',
        'cargo_shipping_fee_tjs',
        'cargo_shipping_fee_cny',
        'elim_service_fee_cny',
        'commission_slab_id',
        'commission_percentage',
        'commission_amount',
        'customer_total_cny',
        'exchange_rate',
        'customer_total_tjs',
        'receiver_address',
        'warehouse_snapshot',
        'address_snapshot',
        'remark',
        'parcel_tracking_id',
        'elim_preview_snapshot',
        'elim_create_snapshot',
        'is_demo_order',
        'elim_detail_snapshot',
        'paid_at',
        'wallet_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'goods_subtotal_cny' => 'decimal:2',
            'shipping_fee_cny' => 'decimal:2',
            'cargo_shipping_fee_tjs' => 'decimal:2',
            'cargo_shipping_fee_cny' => 'decimal:2',
            'elim_service_fee_cny' => 'decimal:2',
            'commission_percentage' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'customer_total_cny' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'customer_total_tjs' => 'decimal:2',
            'receiver_address' => 'array',
            'warehouse_snapshot' => 'array',
            'address_snapshot' => 'array',
            'elim_preview_snapshot' => 'array',
            'elim_create_snapshot' => 'array',
            'elim_detail_snapshot' => 'array',
            'is_demo_order' => 'boolean',
            'paid_at' => 'datetime',
        ];
    }

    public function paymentAmountCny(): float
    {
        return round(
            (float) $this->goods_subtotal_cny
            + (float) $this->shipping_fee_cny
            + (float) ($this->elim_service_fee_cny ?? 0)
            + (float) $this->commission_amount
            + (float) $this->cargo_shipping_fee_cny,
            2
        );
    }

    public function paymentAmountTjs(): float
    {
        if ($this->customer_total_tjs !== null) {
            return round((float) $this->customer_total_tjs, 2);
        }

        $rate = (float) ($this->exchange_rate ?? 0);

        if ($rate > 0) {
            return round($this->paymentAmountCny() * $rate, 2);
        }

        return $this->paymentAmountCny();
    }

    public function isCancellable(): bool
    {
        return $this->status === OrderStatus::CODE_PAID;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function userAddress(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
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

    public function isVisibleInChinaWarehousePanel(): bool
    {
        return $this->status !== OrderStatus::CODE_CANCELLED;
    }
}
