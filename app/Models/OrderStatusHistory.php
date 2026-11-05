<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    public const SOURCE_ELIM_API = 'elim_api';

    public const SOURCE_BARCODE_SCAN = 'barcode_scan';

    public const SOURCE_MANUAL = 'manual';

    /** @var list<string> */
    public const SOURCES = [
        self::SOURCE_ELIM_API,
        self::SOURCE_BARCODE_SCAN,
        self::SOURCE_MANUAL,
    ];

    protected $fillable = [
        'customer_order_id',
        'from_status',
        'to_status',
        'source',
        'admin_id',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'customer_order_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function fromOrderStatus(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'from_status', 'code');
    }

    public function toOrderStatus(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'to_status', 'code');
    }

    public function sourceBadgeClass(): string
    {
        return match ($this->source) {
            self::SOURCE_ELIM_API => 'bg-label-info',
            self::SOURCE_BARCODE_SCAN => 'bg-label-success',
            default => 'bg-label-primary',
        };
    }

    public function sourceLabel(): string
    {
        return match ($this->source) {
            self::SOURCE_ELIM_API => __('admin.audit_source_elim_api'),
            self::SOURCE_BARCODE_SCAN => __('admin.audit_source_barcode_scan'),
            default => __('admin.audit_source_manual'),
        };
    }
}
