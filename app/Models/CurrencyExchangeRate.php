<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyExchangeRate extends Model
{
    public const FROM_CURRENCY = 'CNY';

    public const TO_CURRENCY = 'TJS';

    public const INTERVAL_OPTIONS = [1, 2, 3];

    protected $fillable = [
        'from_currency',
        'to_currency',
        'exchange_rate',
        'auto_refresh_enabled',
        'refresh_interval_hours',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'exchange_rate' => 'decimal:6',
            'auto_refresh_enabled' => 'boolean',
            'refresh_interval_hours' => 'integer',
            'last_synced_at' => 'datetime',
        ];
    }

    public function isDueForRefresh(): bool
    {
        if (! $this->auto_refresh_enabled) {
            return false;
        }

        if ($this->last_synced_at === null) {
            return true;
        }

        return $this->last_synced_at->addHours($this->refresh_interval_hours)->isPast();
    }
}
