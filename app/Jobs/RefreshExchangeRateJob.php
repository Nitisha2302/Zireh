<?php

namespace App\Jobs;

use App\Services\Currency\CurrencyExchangeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RefreshExchangeRateJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function handle(CurrencyExchangeService $currencyExchangeService): void
    {
        Log::info('Exchange rate scheduler job started.');

        try {
            $refreshed = $currencyExchangeService->refreshIfDue();

            Log::info('Exchange rate scheduler job finished.', [
                'refreshed' => $refreshed,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Exchange rate scheduler job failed.', [
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
