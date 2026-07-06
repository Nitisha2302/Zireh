<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderStatus extends Model
{
    use SoftDeletes;

    public const CODE_CREATING = 'creating';

    public const CODE_PENDING_PAYMENT = 'pending_payment';

    public const CODE_PAID = 'paid';

    public const CODE_SHIPPED = 'shipped';

    public const CODE_COMPLETED = 'completed';

    public const CODE_CANCELLED = 'cancelled';

    /** @var list<string> */
    public const SYSTEM_CODES = [
        self::CODE_CREATING,
        self::CODE_PENDING_PAYMENT,
        self::CODE_PAID,
        self::CODE_SHIPPED,
        self::CODE_COMPLETED,
        self::CODE_CANCELLED,
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
}
