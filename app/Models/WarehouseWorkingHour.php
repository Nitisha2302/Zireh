<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseWorkingHour extends Model
{
    public const MONDAY = 1;

    public const SUNDAY = 7;

    protected $fillable = [
        'warehouse_id',
        'day_of_week',
        'is_closed',
        'opens_at',
        'closes_at',
        'break_starts_at',
        'break_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_closed' => 'boolean',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public static function dayNames(): array
    {
        return [
            1 => 'Mon',
            2 => 'Tue',
            3 => 'Wed',
            4 => 'Thu',
            5 => 'Fri',
            6 => 'Sat',
            7 => 'Sun',
        ];
    }

    public function dayName(): string
    {
        return self::dayNames()[$this->day_of_week] ?? '';
    }

    public static function defaultWeek(): array
    {
        $days = [];

        foreach (self::dayNames() as $day => $name) {
            $closed = $day === self::SUNDAY;

            $days[$day] = [
                'day_of_week' => $day,
                'day_name' => $name,
                'is_closed' => $closed,
                'opens_at' => $closed ? '' : '09:00',
                'closes_at' => $closed ? '' : '19:00',
                'break_starts_at' => $closed ? '' : '12:40',
                'break_ends_at' => $closed ? '' : '14:00',
            ];
        }

        return $days;
    }

    public function formattedTime(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return substr($value, 0, 5);
    }

    public function isOpenAt(CarbonInterface|string $time): bool
    {
        if ($this->is_closed || $this->opens_at === null || $this->closes_at === null) {
            return false;
        }

        $current = $this->normalizeTime($time);
        $opens = $this->normalizeTime($this->opens_at);
        $closes = $this->normalizeTime($this->closes_at);

        if ($current < $opens || $current >= $closes) {
            return false;
        }

        if ($this->break_starts_at && $this->break_ends_at) {
            $breakStarts = $this->normalizeTime($this->break_starts_at);
            $breakEnds = $this->normalizeTime($this->break_ends_at);

            if ($current >= $breakStarts && $current < $breakEnds) {
                return false;
            }
        }

        return true;
    }

    public function toPayload(): array
    {
        $closed = $this->is_closed;

        return [
            'day_of_week' => $this->day_of_week,
            'day_name' => $this->dayName(),
            'is_closed' => $closed,
            'opens_at' => $closed ? null : $this->formattedTime($this->opens_at),
            'closes_at' => $closed ? null : $this->formattedTime($this->closes_at),
            'break_starts_at' => $closed ? null : $this->formattedTime($this->break_starts_at),
            'break_ends_at' => $closed ? null : $this->formattedTime($this->break_ends_at),
        ];
    }

    protected function normalizeTime(CarbonInterface|string $time): string
    {
        if ($time instanceof CarbonInterface) {
            return $time->format('H:i');
        }

        return substr($time, 0, 5);
    }
}
