<?php

namespace App\Models;

use App\Services\PlatformCommissionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformCommissionSlab extends Model
{
    protected $fillable = [
        'platform_id',
        'min_amount',
        'max_amount',
        'commission_percentage',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'commission_percentage' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class);
    }

    public function isUnlimited(): bool
    {
        return $this->max_amount === null;
    }

    protected static function booted(): void
    {
        $clearCache = function (PlatformCommissionSlab $slab): void {
            Platform::clearCatalogCache();
            app(PlatformCommissionService::class)->clearCache($slab->platform_id);
        };

        static::saved($clearCache);
        static::deleted($clearCache);
    }
}
