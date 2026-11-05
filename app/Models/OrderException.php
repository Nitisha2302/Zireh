<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderException extends Model
{
    public const TYPE_MISSING_BARCODE = 'missing_barcode';

    public const TYPE_API_ERROR = 'api_error';

    public const TYPE_DAMAGED_PARCEL = 'damaged_parcel';

    public const TYPE_UNIDENTIFIED_SHIPMENT = 'unidentified_shipment';

    /** @var list<string> */
    public const TYPES = [
        self::TYPE_MISSING_BARCODE,
        self::TYPE_API_ERROR,
        self::TYPE_DAMAGED_PARCEL,
        self::TYPE_UNIDENTIFIED_SHIPMENT,
    ];

    public const STATUS_OPEN = 'open';

    public const STATUS_IN_REVIEW = 'in_review';

    public const STATUS_RESOLVED = 'resolved';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_IN_REVIEW,
        self::STATUS_RESOLVED,
    ];

    protected $fillable = [
        'customer_order_id',
        'shipment_id',
        'warehouse_id',
        'type',
        'status',
        'origin_panel',
        'scanned_code',
        'summary',
        'details',
        'error_payload',
        'resolution_notes',
        'reported_by_admin_id',
        'resolved_by_admin_id',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'error_payload' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'customer_order_id');
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function reportedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reported_by_admin_id');
    }

    public function resolvedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'resolved_by_admin_id');
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    public function typeBadgeClass(): string
    {
        return match ($this->type) {
            self::TYPE_MISSING_BARCODE => 'bg-label-warning',
            self::TYPE_API_ERROR => 'bg-label-danger',
            self::TYPE_DAMAGED_PARCEL => 'bg-label-danger',
            self::TYPE_UNIDENTIFIED_SHIPMENT => 'bg-label-info',
            default => 'bg-label-secondary',
        };
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_MISSING_BARCODE => __('admin.exception_type_missing_barcode'),
            self::TYPE_API_ERROR => __('admin.exception_type_api_error'),
            self::TYPE_DAMAGED_PARCEL => __('admin.exception_type_damaged_parcel'),
            self::TYPE_UNIDENTIFIED_SHIPMENT => __('admin.exception_type_unidentified_shipment'),
            default => $this->type,
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'bg-label-danger',
            self::STATUS_IN_REVIEW => 'bg-label-warning',
            self::STATUS_RESOLVED => 'bg-label-success',
            default => 'bg-label-secondary',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => __('admin.exception_status_open'),
            self::STATUS_IN_REVIEW => __('admin.exception_status_in_review'),
            self::STATUS_RESOLVED => __('admin.exception_status_resolved'),
            default => $this->status,
        };
    }
}
