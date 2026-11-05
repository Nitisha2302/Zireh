<?php

namespace App\Models;

use App\Services\Order\OrderAuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** @property-read float $payment_amount_cny */
/** @property-read float $payment_amount_tjs */
class CustomerOrder extends Model
{
    protected static function booted(): void
    {
        static::creating(function (CustomerOrder $order): void {
            if (
                empty($order->parcel_code)
                || self::query()->where('parcel_code', $order->parcel_code)->exists()
            ) {
                $order->parcel_code = self::generateUniqueParcelCode();
            }
        });

        static::created(function (CustomerOrder $order): void {
            if (! empty($order->status)) {
                $logger = app(OrderAuditLogger::class);
                $defaultSource = $logger->currentContext()['source']
                    ?? OrderStatusHistory::SOURCE_ELIM_API;

                $logger->recordTransition(
                    $order,
                    null,
                    (string) $order->status,
                    $defaultSource
                );
            }
        });

        static::updated(function (CustomerOrder $order): void {
            if ($order->wasChanged('status')) {
                $fromStatus = $order->getOriginal('status');
                $toStatus = $order->status;

                app(OrderAuditLogger::class)->recordTransition(
                    $order,
                    $fromStatus !== null ? (string) $fromStatus : null,
                    (string) $toStatus
                );
            }
        });
    }

    public static function generateUniqueParcelCode(): string
    {
        $year = date('Y');

        do {
            $candidate = sprintf('PCL-%s-%s', $year, strtoupper(Str::random(8)));
        } while (self::query()->where('parcel_code', $candidate)->exists());

        return $candidate;
    }

    /**
     * Never copy per-parcel unique identifiers when duplicating an order.
     */
    public function replicate(?array $except = null): static
    {
        return parent::replicate(array_values(array_unique(array_merge(
            $except ?? [],
            ['parcel_code', 'pickup_token']
        ))));
    }

    public function parcelQrPayload(): string
    {
        return 'cargo-parcel:'.($this->parcel_code ?? $this->id);
    }

    public const PLATFORM_TAOBAO = 'taobao';

    public const PLATFORM_1688 = '1688';

    public const PLATFORM_JD = 'jd';

    public const PAYMENT_METHOD_WALLET = 'wallet';

    public const PAYMENT_METHOD_ONLINE = 'online';

    public const PAYMENT_STATUS_UNPAID = 'unpaid';

    public const PAYMENT_STATUS_REQUESTED = 'requested';

    public const PAYMENT_STATUS_APPROVED = 'approved';

    public const PAYMENT_STATUS_PAID = 'paid';

    public const PAYMENT_STATUS_REJECTED = 'rejected';

    public const PAYMENT_STATUS_REFUNDED = 'refunded';

    public const PICKUP_PAYMENT_STATUS_PENDING = 'pending';

    public const PICKUP_PAYMENT_STATUS_PAID = 'paid';

    public const PICKUP_CALCULATION_WEIGHT = 'weight';

    public const PICKUP_CALCULATION_VOLUME = 'volume';

    public const PICKUP_CALCULATION_BOTH = 'both';

    /** @var list<string> */
    public const PRE_PICKUP_STATUSES = [
        OrderStatus::CODE_ARRIVED_IN_TAJIKISTAN,
        OrderStatus::CODE_SORTING_CENTER,
        OrderStatus::CODE_SENT_TO_SELECTED_WAREHOUSE,
    ];

    protected $fillable = [
        'user_id',
        'warehouse_id',
        'shipment_id',
        'user_address_id',
        'shipping_method_id',
        'platform_id',
        'platform',
        'elim_order_id',
        'parcel_code',
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
        'final_amount_tjs',
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
        'cancellation_refund_transaction_id',
        'cancelled_by_admin_id',
        'cancelled_at',
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
            'final_amount_tjs' => 'decimal:2',
            'receiver_address' => 'array',
            'warehouse_snapshot' => 'array',
            'address_snapshot' => 'array',
            'elim_preview_snapshot' => 'array',
            'elim_create_snapshot' => 'array',
            'elim_detail_snapshot' => 'array',
            'is_demo_order' => 'boolean',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
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

    public function isPickupBilledByWeight(): bool
    {
        return $this->pickup_shipping_calculation_method === 'weight';
    }

    public function isPickupBilledByVolume(): bool
    {
        return $this->pickup_shipping_calculation_method === 'volume';
    }

    public function isPickupBilledByBoth(): bool
    {
        return $this->pickup_shipping_calculation_method === self::PICKUP_CALCULATION_BOTH;
    }

    public function packageWeightGrams(): ?float
    {
        if ($this->package_weight_kg === null) {
            return null;
        }

        return round((float) $this->package_weight_kg * 1000, 2);
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
        return round((float) ($this->final_amount_tjs ?? $this->customer_total_tjs ?? 0), 2);
    }

    public function isCancellable(): bool
    {
        return $this->status === OrderStatus::CODE_PAID
            && $this->cancellation_refund_transaction_id === null
            && $this->cancelled_at === null;
    }

    public function hasBeenRefundedOnCancellation(): bool
    {
        return $this->cancellation_refund_transaction_id !== null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cancelledByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'cancelled_by_admin_id');
    }

    public function cancellationRefundTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class, 'cancellation_refund_transaction_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function hasShipment(): bool
    {
        return $this->shipment_id !== null;
    }

    public function isManagedByShipment(): bool
    {
        if ($this->shipment_id === null) {
            return false;
        }

        $shipment = $this->relationLoaded('shipment')
            ? $this->shipment
            : $this->shipment()->first();

        if (! $shipment) {
            return false;
        }

        return ! $shipment->isReceivedInTajikistan();
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

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class, 'customer_order_id')
            ->latest('id');
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(OrderException::class, 'customer_order_id')
            ->latest('id');
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
