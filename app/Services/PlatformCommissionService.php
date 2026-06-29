<?php

namespace App\Services;

use App\Models\Platform;
use App\Models\PlatformCommissionSlab;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class PlatformCommissionService
{
    private const CACHE_TTL_SECONDS = 3600;

    public function listForPlatform(int $platformId): array
    {
        return Cache::remember(
            $this->listCacheKey($platformId),
            self::CACHE_TTL_SECONDS,
            function () use ($platformId): array {
                $platform = Platform::query()
                    ->where('is_available', true)
                    ->find($platformId);

                if (! $platform) {
                    return [
                        'platform_id' => $platformId,
                        'platform_code' => null,
                        'slabs' => [],
                    ];
                }

                $slabs = PlatformCommissionSlab::query()
                    ->where('platform_id', $platformId)
                    ->where('is_active', true)
                    ->orderBy('min_amount')
                    ->get();

                return [
                    'platform_id' => $platform->id,
                    'platform_code' => $platform->code,
                    'slabs' => $slabs->map(fn (PlatformCommissionSlab $slab): array => [
                        'id' => $slab->id,
                        'min_amount' => (float) $slab->min_amount,
                        'max_amount' => $slab->max_amount !== null ? (float) $slab->max_amount : null,
                        'is_unlimited' => $slab->max_amount === null,
                        'commission_percentage' => (float) $slab->commission_percentage,
                    ])->values()->all(),
                ];
            }
        );
    }

    public function clearCache(?int $platformId = null): void
    {
        if ($platformId) {
            Cache::forget($this->listCacheKey($platformId));

            return;
        }

        Platform::query()->pluck('id')->each(fn (int $id) => Cache::forget($this->listCacheKey($id)));
    }

    protected function listCacheKey(int $platformId): string
    {
        return "api:platform-catalog:commission-slabs:{$platformId}";
    }

    public function findActiveSlab(Platform|int $platform, float $amount): PlatformCommissionSlab
    {
        $platformId = $platform instanceof Platform ? $platform->id : $platform;

        $slab = PlatformCommissionSlab::query()
            ->where('platform_id', $platformId)
            ->where('is_active', true)
            ->where('min_amount', '<=', $amount)
            ->where(function ($query) use ($amount) {
                $query->whereNull('max_amount')
                    ->orWhere('max_amount', '>=', $amount);
            })
            ->orderByDesc('min_amount')
            ->first();

        if (! $slab) {
            throw ValidationException::withMessages([
                'amount' => [__('api.commission_slab_not_found')],
            ]);
        }

        return $slab;
    }

    public function calculate(Platform|int $platform, float $amount): array
    {
        $slab = $this->findActiveSlab($platform, $amount);
        $commissionAmount = round($amount * ((float) $slab->commission_percentage / 100), 2);

        return [
            'slab_id' => $slab->id,
            'commission_percentage' => (float) $slab->commission_percentage,
            'commission_amount' => $commissionAmount,
            'net_amount' => round($amount - $commissionAmount, 2),
        ];
    }

    public function assertValidRange(
        int $platformId,
        float $minAmount,
        ?float $maxAmount,
        ?int $ignoreId = null
    ): void {
        if ($maxAmount !== null && $minAmount >= $maxAmount) {
            throw ValidationException::withMessages([
                'max_amount' => ['Maximum amount must be greater than minimum amount.'],
            ]);
        }

        if ($maxAmount === null) {
            $unlimitedExists = PlatformCommissionSlab::query()
                ->where('platform_id', $platformId)
                ->whereNull('max_amount')
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists();

            if ($unlimitedExists) {
                throw ValidationException::withMessages([
                    'is_unlimited' => ['Only one unlimited commission slab is allowed per platform.'],
                ]);
            }
        }

        $duplicateExists = PlatformCommissionSlab::query()
            ->where('platform_id', $platformId)
            ->where('min_amount', $minAmount)
            ->when(
                $maxAmount === null,
                fn ($query) => $query->whereNull('max_amount'),
                fn ($query) => $query->where('max_amount', $maxAmount)
            )
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'min_amount' => ['A commission slab with this amount range already exists.'],
            ]);
        }

        $newMax = $maxAmount ?? PHP_FLOAT_MAX;

        $existingSlabs = PlatformCommissionSlab::query()
            ->where('platform_id', $platformId)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->get();

        foreach ($existingSlabs as $slab) {
            $existingMax = $slab->max_amount !== null ? (float) $slab->max_amount : PHP_FLOAT_MAX;

            if ($minAmount <= $existingMax && (float) $slab->min_amount <= $newMax) {
                throw ValidationException::withMessages([
                    'min_amount' => ['This amount range overlaps with an existing commission slab.'],
                ]);
            }
        }
    }
}
