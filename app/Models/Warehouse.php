<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Warehouse extends Model
{
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const DEFAULT_COUNTRY = 'Tajikistan';

    public const AVAILABILITY_TIMEZONE = 'Asia/Dushanbe';

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

    public function workingHours(): HasMany
    {
        return $this->hasMany(WarehouseWorkingHour::class)->orderBy('day_of_week');
    }

    public function isOpenNow(?CarbonInterface $at = null): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        $now = Carbon::parse($at ?? now())->timezone(self::AVAILABILITY_TIMEZONE);
        $hours = $this->workingHoursForDay($now->isoWeekday());

        return $hours?->isOpenAt($now) ?? false;
    }

    public function workingHoursForDay(int $dayOfWeek): ?WarehouseWorkingHour
    {
        if ($this->relationLoaded('workingHours')) {
            return $this->workingHours->firstWhere('day_of_week', $dayOfWeek);
        }

        return $this->workingHours()->where('day_of_week', $dayOfWeek)->first();
    }

    public function workingHoursPayload(): array
    {
        $byDay = $this->workingHours->keyBy('day_of_week');
        $payload = [];

        foreach (WarehouseWorkingHour::dayNames() as $day => $name) {
            $row = $byDay->get($day);

            $payload[] = $row?->toPayload() ?? [
                'day_of_week' => $day,
                'day_name' => $name,
                'is_closed' => true,
                'opens_at' => null,
                'closes_at' => null,
                'break_starts_at' => null,
                'break_ends_at' => null,
            ];
        }

        return $payload;
    }

    public function customerOrders(): HasMany
    {
        return $this->hasMany(CustomerOrder::class);
    }

    public function shippingRates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
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
