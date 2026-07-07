<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const DEFAULT_COUNTRY = 'Tajikistan';

    protected $fillable = [
        'warehouse_name',
        'warehouse_code',
        'image',
        'contact_person',
        'contact_number',
        'email',
        'country',
        'state',
        'city',
        'address',
        'postal_code',
        'latitude',
        'longitude',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function customerOrders(): HasMany
    {
        return $this->hasMany(CustomerOrder::class);
    }

    public function admins(): HasMany
    {
        return $this->hasMany(Admin::class);
    }

    public function tajikistanAccount(): HasOne
    {
        return $this->hasOne(Admin::class)->where('role', Admin::ROLE_TAJIKISTAN_WAREHOUSE);
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
        ];
    }
}
