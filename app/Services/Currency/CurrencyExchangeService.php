<?php

namespace App\Services\Currency;

use App\Exceptions\ExchangeRateFetchException;
use App\Models\CurrencyExchangeRate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CurrencyExchangeService
{
    public const CACHE_KEY = 'currency.exchange_rate.active';

    public const LOCK_KEY = 'currency.exchange_rate.refresh';

    public function __construct(
        protected ExchangeRateService $exchangeRateService,
    ) {}

    public function getActive(): CurrencyExchangeRate
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): CurrencyExchangeRate {
            return CurrencyExchangeRate::query()->firstOrCreate(
                [
                    'from_currency' => CurrencyExchangeRate::FROM_CURRENCY,
                    'to_currency' => CurrencyExchangeRate::TO_CURRENCY,
                ],
                [
                    'exchange_rate' => (float) config('services.exchange_rate.default_rate', 1.5),
                    'auto_refresh_enabled' => false,
                    'refresh_interval_hours' => 1,
                ]
            );
        });
    }

    public function getRate(): float
    {
        return (float) $this->getActive()->exchange_rate;
    }

    public function convertCnyToTjs(?float $amount): ?float
    {
        if ($amount === null) {
            return null;
        }

        return round($amount * $this->getRate(), 2);
    }

    public function meta(): array
    {
        $config = $this->getActive();

        return [
            'from_currency' => $config->from_currency,
            'to_currency' => $config->to_currency,
            'exchange_rate' => (float) $config->exchange_rate,
            'last_synced_at' => $config->last_synced_at,
        ];
    }

    public function refresh(bool $manual = false): CurrencyExchangeRate
    {
        $lock = Cache::lock(self::LOCK_KEY, 120);

        if (! $lock->get()) {
            throw ValidationException::withMessages([
                'exchange_rate' => ['Exchange rate refresh is already in progress. Please try again shortly.'],
            ]);
        }

        try {
            $rate = $this->exchangeRateService->fetchRate(
                CurrencyExchangeRate::FROM_CURRENCY,
                CurrencyExchangeRate::TO_CURRENCY
            );

            return $this->persistRate($rate, $manual ? 'manual' : 'automatic');
        } catch (ExchangeRateFetchException $exception) {
            Log::error('Exchange rate refresh failed.', [
                'trigger' => $manual ? 'manual' : 'automatic',
                'message' => $exception->getMessage(),
                'context' => $exception->context,
            ]);

            throw ValidationException::withMessages([
                'exchange_rate' => [$exception->getMessage()],
            ]);
        } finally {
            $lock->release();
        }
    }

    public function refreshIfDue(): bool
    {
        $config = CurrencyExchangeRate::query()->first();

        if (! $config || ! $config->isDueForRefresh()) {
            Log::debug('Exchange rate auto refresh skipped.', [
                'enabled' => $config?->auto_refresh_enabled,
                'last_synced_at' => $config?->last_synced_at,
            ]);

            return false;
        }

        $this->refresh(manual: false);

        return true;
    }

    public function saveSettings(array $data): CurrencyExchangeRate
    {
        $validated = validator($data, [
            'exchange_rate' => ['required', 'numeric', 'gt:0'],
            'auto_refresh_enabled' => ['required', 'boolean'],
            'refresh_interval_hours' => ['required', 'integer', 'in:1,2,3'],
        ])->validate();

        return DB::transaction(function () use ($validated): CurrencyExchangeRate {
            $config = CurrencyExchangeRate::query()->firstOrCreate(
                [
                    'from_currency' => CurrencyExchangeRate::FROM_CURRENCY,
                    'to_currency' => CurrencyExchangeRate::TO_CURRENCY,
                ],
                [
                    'exchange_rate' => $validated['exchange_rate'],
                    'auto_refresh_enabled' => $validated['auto_refresh_enabled'],
                    'refresh_interval_hours' => $validated['refresh_interval_hours'],
                ]
            );

            $config->update([
                'exchange_rate' => $validated['exchange_rate'],
                'auto_refresh_enabled' => $validated['auto_refresh_enabled'],
                'refresh_interval_hours' => $validated['refresh_interval_hours'],
            ]);

            $this->clearCache();

            Log::info('Exchange rate settings saved.', [
                'exchange_rate' => $config->exchange_rate,
                'auto_refresh_enabled' => $config->auto_refresh_enabled,
                'refresh_interval_hours' => $config->refresh_interval_hours,
            ]);

            return $config->fresh();
        });
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected function persistRate(float $rate, string $trigger): CurrencyExchangeRate
    {
        return DB::transaction(function () use ($rate, $trigger): CurrencyExchangeRate {
            $config = CurrencyExchangeRate::query()->firstOrCreate(
                [
                    'from_currency' => CurrencyExchangeRate::FROM_CURRENCY,
                    'to_currency' => CurrencyExchangeRate::TO_CURRENCY,
                ],
                [
                    'exchange_rate' => $rate,
                    'auto_refresh_enabled' => false,
                    'refresh_interval_hours' => 1,
                ]
            );

            $config->update([
                'exchange_rate' => $rate,
                'last_synced_at' => now(),
            ]);

            $this->clearCache();

            Log::info('Exchange rate updated.', [
                'trigger' => $trigger,
                'exchange_rate' => $rate,
                'last_synced_at' => $config->last_synced_at,
            ]);

            return $config->fresh();
        });
    }
}
