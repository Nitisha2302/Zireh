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

    public const PICKUP_PAYMENT_STATUS_PENDING = 'pending';

    public const PICKUP_PAYMENT_STATUS_PAID = 'paid';

    public const PICKUP_CALCULATION_WEIGHT = 'weight';

    public const PICKUP_CALCULATION_VOLUME = 'volume';

    /** @var list<string> */
    public const PRE_PICKUP_STATUSES = [
        OrderStatus::CODE_ARRIVED_IN_TAJIKISTAN,
        OrderStatus::CODE_SORTING_CENTER,
        OrderStatus::CODE_SENT_TO_SELECTED_WAREHOUSE,
    ];

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
        'package_length_cm',
        'package_width_cm',
        'package_height_cm',
        'package_weight_kg',
        'package_volume_m3',
        'pickup_shipping_fee_tjs',
        'pickup_shipping_weight_fee_tjs',
        'pickup_shipping_volume_fee_tjs',
        'pickup_shipping_calculation_method',
        'pickup_shipping_snapshot',
        'pickup_payment_status',
        'pickup_payment_method',
        'pickup_paid_at',
        'pickup_wallet_transaction_id',
        'pickup_token',
        'pickup_confirmed_at',
        'pickup_confirmed_by',
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
            'package_length_cm' => 'decimal:2',
            'package_width_cm' => 'decimal:2',
            'package_height_cm' => 'decimal:2',
            'package_weight_kg' => 'decimal:2',
            'package_volume_m3' => 'decimal:6',
            'pickup_shipping_fee_tjs' => 'decimal:2',
            'pickup_shipping_weight_fee_tjs' => 'decimal:2',
            'pickup_shipping_volume_fee_tjs' => 'decimal:2',
            'pickup_shipping_snapshot' => 'array',
            'pickup_paid_at' => 'datetime',
            'pickup_confirmed_at' => 'datetime',
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
            + (float) $this->commission_amount,
            2
        );
    }

    public function pickupPaymentAmountTjs(): float
    {
        return round((float) ($this->pickup_shipping_fee_tjs ?? 0), 2);
    }

    public function isReadyForPickup(): bool
    {
        return $this->status === OrderStatus::CODE_READY_FOR_PICKUP;
    }

    public function isPickupShippingPaid(): bool
    {
        return $this->pickup_payment_status === self::PICKUP_PAYMENT_STATUS_PAID;
    }

    public function pickupQrPayload(): ?string
    {
        if ($this->pickup_token === null) {
            return null;
        }

        return 'cargo-pickup:'.$this->pickup_token;
    }

    public function pickupConfirmedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'pickup_confirmed_by');
    }

    public function pickupWalletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class, 'pickup_wallet_transaction_id');
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
