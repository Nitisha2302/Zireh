<?php

namespace App\Livewire\Admin\Warehouse\Concerns;

use App\Models\Warehouse;
use App\Models\WarehouseWorkingHour;
use Illuminate\Validation\Validator;

trait ManagesWarehouseWorkingHours
{
    public array $workingHours = [];

    public function initializeManagesWarehouseWorkingHours(): void
    {
        if ($this->workingHours === []) {
            $this->workingHours = WarehouseWorkingHour::defaultWeek();
        }
    }

    public function fillWorkingHoursFromWarehouse(Warehouse $warehouse): void
    {
        $defaults = WarehouseWorkingHour::defaultWeek();
        $existing = $warehouse->workingHours()->get()->keyBy('day_of_week');

        if ($existing->isEmpty()) {
            $this->workingHours = $defaults;

            return;
        }

        foreach ($defaults as $day => $row) {
            $saved = $existing->get((int) $row['day_of_week']);

            if (! $saved) {
                $this->workingHours[$day] = $row;

                continue;
            }

            $this->workingHours[$day] = [
                'day_of_week' => $day,
                'day_name' => $row['day_name'],
                'is_closed' => (bool) $saved->is_closed,
                'opens_at' => $saved->formattedTime($saved->opens_at) ?? '',
                'closes_at' => $saved->formattedTime($saved->closes_at) ?? '',
                'break_starts_at' => $saved->formattedTime($saved->break_starts_at) ?? '',
                'break_ends_at' => $saved->formattedTime($saved->break_ends_at) ?? '',
            ];
        }
    }

    protected function normalizeWorkingHourTimes(): void
    {
        foreach ($this->workingHours as $day => $row) {
            foreach (['opens_at', 'closes_at', 'break_starts_at', 'break_ends_at'] as $field) {
                $value = $row[$field] ?? '';

                if ($value !== '') {
                    $this->workingHours[$day][$field] = substr((string) $value, 0, 5);
                }
            }

            $this->workingHours[$day]['is_closed'] = filter_var($row['is_closed'] ?? false, FILTER_VALIDATE_BOOLEAN);
        }
    }

    protected function workingHoursRules(): array
    {
        return [
            'workingHours' => ['required', 'array'],
            'workingHours.*.is_closed' => ['required', 'boolean'],
            'workingHours.*.opens_at' => ['nullable', 'date_format:H:i'],
            'workingHours.*.closes_at' => ['nullable', 'date_format:H:i'],
            'workingHours.*.break_starts_at' => ['nullable', 'date_format:H:i'],
            'workingHours.*.break_ends_at' => ['nullable', 'date_format:H:i'],
        ];
    }

    protected function validateWorkingHoursConsistency(Validator $validator): void
    {
        foreach ($this->workingHours as $day => $row) {
            if (filter_var($row['is_closed'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                continue;
            }

            $opens = $row['opens_at'] ?? '';
            $closes = $row['closes_at'] ?? '';

            if ($opens === '' || $closes === '') {
                if ($opens === '') {
                    $validator->errors()->add("workingHours.{$day}.opens_at", __('admin.warehouse_hours_open_required'));
                }

                if ($closes === '') {
                    $validator->errors()->add("workingHours.{$day}.closes_at", __('admin.warehouse_hours_close_required'));
                }

                continue;
            }

            if ($opens >= $closes) {
                $validator->errors()->add("workingHours.{$day}.closes_at", __('admin.warehouse_hours_close_after_open'));
            }

            $breakStarts = $row['break_starts_at'] ?? '';
            $breakEnds = $row['break_ends_at'] ?? '';

            if (($breakStarts === '') !== ($breakEnds === '')) {
                $field = $breakStarts === '' ? 'break_starts_at' : 'break_ends_at';
                $validator->errors()->add("workingHours.{$day}.{$field}", __('admin.warehouse_hours_break_both_required'));

                continue;
            }

            if ($breakStarts === '' && $breakEnds === '') {
                continue;
            }

            if ($breakStarts >= $breakEnds) {
                $validator->errors()->add("workingHours.{$day}.break_ends_at", __('admin.warehouse_hours_break_end_after_start'));
            }

            if ($breakStarts <= $opens || $breakEnds >= $closes) {
                $validator->errors()->add("workingHours.{$day}.break_starts_at", __('admin.warehouse_hours_break_inside_hours'));
            }
        }
    }

    protected function syncWorkingHours(Warehouse $warehouse): void
    {
        foreach ($this->workingHours as $day => $row) {
            $closed = filter_var($row['is_closed'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $warehouse->workingHours()->updateOrCreate(
                ['day_of_week' => (int) ($row['day_of_week'] ?? $day)],
                [
                    'is_closed' => $closed,
                    'opens_at' => $closed ? null : ($this->nullableTime($row['opens_at'] ?? null)),
                    'closes_at' => $closed ? null : ($this->nullableTime($row['closes_at'] ?? null)),
                    'break_starts_at' => $closed ? null : ($this->nullableTime($row['break_starts_at'] ?? null)),
                    'break_ends_at' => $closed ? null : ($this->nullableTime($row['break_ends_at'] ?? null)),
                ]
            );
        }
    }

    protected function nullableTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
