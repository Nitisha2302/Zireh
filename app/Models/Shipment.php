<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    /** @var list<string> */
    public const TAJIKISTAN_RECEIVED_STATUSES = [
        OrderStatus::CODE_ARRIVED_IN_TAJIKISTAN,
        OrderStatus::CODE_SORTING_CENTER,
        OrderStatus::CODE_SENT_TO_SELECTED_WAREHOUSE,
        OrderStatus::CODE_READY_FOR_PICKUP,
        OrderStatus::CODE_DELIVERED_TO_CUSTOMER,
    ];

    protected $fillable = [
        'shipment_code',
        'status',
        'notes',
        'created_by_admin_id',
        'received_in_tajikistan_at',
        'received_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'received_in_tajikistan_at' => 'datetime',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(CustomerOrder::class);
    }

    public function orderStatus(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'status', 'code');
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function receivedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'received_by_admin_id');
    }

    public function isReceivedInTajikistan(): bool
    {
        return $this->received_in_tajikistan_at !== null
            || in_array($this->status, self::TAJIKISTAN_RECEIVED_STATUSES, true);
    }

    public function isStatusUpdateLocked(): bool
    {
        return $this->isReceivedInTajikistan();
    }
}
