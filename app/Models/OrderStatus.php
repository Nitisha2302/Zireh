<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class OrderStatus extends Model
{
    use HasTranslations;
    use SoftDeletes;

    public const CODE_PAID = 'paid';

    public const CODE_RECEIVED_AT_CHINA_WAREHOUSE = 'received_at_china_warehouse';

    public const CODE_PREPARING_FOR_SHIPMENT = 'preparing_for_shipment';

    public const CODE_SHIPPED_TO_TAJIKISTAN = 'shipped_to_tajikistan';

    public const CODE_ARRIVED_IN_TAJIKISTAN = 'arrived_in_tajikistan';

    public const CODE_SORTING_CENTER = 'sorting_center';

    public const CODE_SENT_TO_SELECTED_WAREHOUSE = 'sent_to_selected_warehouse';

    public const CODE_READY_FOR_PICKUP = 'ready_for_pickup';

    public const CODE_DELIVERED_TO_CUSTOMER = 'delivered_to_customer';

    public const CODE_CANCELLED = 'cancelled';

    /** Aliases for the simplified 7-step lifecycle. */
    public const CODE_ORDER_CREATED = self::CODE_PAID;

    public const CODE_IN_TRANSIT = self::CODE_SHIPPED_TO_TAJIKISTAN;

    public const CODE_CUSTOMS_CLEARANCE = self::CODE_ARRIVED_IN_TAJIKISTAN;

    public const CODE_SORTING = self::CODE_SORTING_CENTER;

    public const CODE_DELIVERED = self::CODE_DELIVERED_TO_CUSTOMER;

    /** @var list<string> */
    public const SYSTEM_CODES = [
        self::CODE_PAID,
        self::CODE_RECEIVED_AT_CHINA_WAREHOUSE,
        self::CODE_SHIPPED_TO_TAJIKISTAN,
        self::CODE_ARRIVED_IN_TAJIKISTAN,
        self::CODE_SORTING_CENTER,
        self::CODE_READY_FOR_PICKUP,
        self::CODE_DELIVERED_TO_CUSTOMER,
        self::CODE_CANCELLED,
        // Legacy codes kept for historical orders; seeded inactive.
        self::CODE_PREPARING_FOR_SHIPMENT,
        self::CODE_SENT_TO_SELECTED_WAREHOUSE,
    ];

    /** @var list<string> */
    public const FULFILLMENT_CODES = [
        self::CODE_PAID,
        self::CODE_RECEIVED_AT_CHINA_WAREHOUSE,
        self::CODE_SHIPPED_TO_TAJIKISTAN,
        self::CODE_ARRIVED_IN_TAJIKISTAN,
        self::CODE_SORTING_CENTER,
        self::CODE_READY_FOR_PICKUP,
        self::CODE_DELIVERED_TO_CUSTOMER,
    ];

    /** @var list<string> */
    public const LEGACY_INACTIVE_CODES = [
        self::CODE_PREPARING_FOR_SHIPMENT,
        self::CODE_SENT_TO_SELECTED_WAREHOUSE,
    ];

    /** @var list<string> */
    public const COLOR_OPTIONS = [
        'primary',
        'secondary',
        'success',
        'danger',
        'warning',
        'info',
    ];

    protected $fillable = [
        'name',
        'code',
        'color',
        'description',
        'is_system',
        'is_active',
        'sort_order',
    ];

    public array $translatable = [
        'name',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(CustomerOrder::class, 'status', 'code');
    }

    public function isSystem(): bool
    {
        return (bool) $this->is_system;
    }

    public function badgeClass(): string
    {
        return 'bg-label-'.($this->color ?: 'secondary');
    }

    public function label(?string $locale = null): string
    {
        return $this->getTranslation('name', $locale ?? app()->getLocale())
            ?: $this->getTranslation('name', 'en')
            ?: $this->code;
    }

    public static function mapFromElimStatus(?string $elimStatus): ?string
    {
        if ($elimStatus === null || $elimStatus === '') {
            return null;
        }

        return match ($elimStatus) {
            'creating', 'pending_payment', 'paid' => self::CODE_PAID,
            'shipped' => self::CODE_SHIPPED_TO_TAJIKISTAN,
            'completed' => self::CODE_DELIVERED_TO_CUSTOMER,
            'cancelled' => self::CODE_CANCELLED,
            default => null,
        };
    }
}
