<?php

namespace App\Services\Currency;

use App\Exceptions\ExchangeRateFetchException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    public function fetchRate(string $fromCurrency = 'CNY', string $toCurrency = 'TJS'): float
    {
        $fromCurrency = strtoupper($fromCurrency);
        $toCurrency = strtoupper($toCurrency);
        $url = $this->buildUrl($fromCurrency, $toCurrency);
        $retries = (int) config('services.exchange_rate.retries', 2);
        $attempt = 0;
        $lastException = null;

        while ($attempt <= $retries) {
            $attempt++;

            try {
                Log::info('Exchange rate API request started.', [
                    'from' => $fromCurrency,
                    'to' => $toCurrency,
                    'attempt' => $attempt,
                    'url' => $this->maskUrl($url),
                ]);

                $response = Http::timeout((int) config('services.exchange_rate.timeout', 15))
                    ->acceptJson()
                    ->when(
                        filled(config('services.exchange_rate.api_key')),
                        fn ($request) => $request->withHeaders([
                            'Authorization' => 'Bearer '.config('services.exchange_rate.api_key'),
                        ])
                    )
                    ->get($url);

                if (! $response->successful()) {
                    throw new ExchangeRateFetchException(
                        'Exchange rate API returned a non-success response.',
                        context: [
                            'status' => $response->status(),
                            'body' => $response->json(),
                        ]
                    );
                }

                $rate = $this->extractRate($response->json(), $toCurrency);

                if ($rate <= 0) {
                    throw new ExchangeRateFetchException(
                        'Exchange rate API returned an invalid rate.',
                        context: ['rate' => $rate, 'body' => $response->json()]
                    );
                }

                Log::info('Exchange rate API request succeeded.', [
                    'from' => $fromCurrency,
                    'to' => $toCurrency,
                    'rate' => $rate,
                    'attempt' => $attempt,
                ]);

                return round($rate, 6);
            } catch (ExchangeRateFetchException $exception) {
                $lastException = $exception;
                Log::warning('Exchange rate API attempt failed.', [
                    'from' => $fromCurrency,
                    'to' => $toCurrency,
                    'attempt' => $attempt,
                    'message' => $exception->getMessage(),
                    'context' => $exception->context,
                ]);
            } catch (\Throwable $exception) {
                $lastException = new ExchangeRateFetchException(
                    $exception->getMessage(),
                    context: ['exception' => $exception::class]
                );

                Log::warning('Exchange rate API attempt failed.', [
                    'from' => $fromCurrency,
                    'to' => $toCurrency,
                    'attempt' => $attempt,
                    'message' => $exception->getMessage(),
                ]);
            }

            if ($attempt <= $retries) {
                usleep((int) config('services.exchange_rate.retry_sleep', 500) * 1000);
            }
        }

        throw $lastException ?? new ExchangeRateFetchException('Unable to fetch exchange rate.');
    }

    protected function buildUrl(string $fromCurrency, string $toCurrency): string
    {
        $url = (string) config('services.exchange_rate.api_url', 'https://open.er-api.com/v6/latest/{from}');
        $apiKey = (string) config('services.exchange_rate.api_key', '');

        $url = str_replace(['{from}', '{to}'], [$fromCurrency, $toCurrency], $url);

        if ($apiKey !== '' && ! str_contains($url, 'apikey=')) {
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator.'apikey='.urlencode($apiKey);
        }

        return $url;
    }

    protected function extractRate(mixed $payload, string $toCurrency): float
    {
        if (! is_array($payload)) {
            return 0;
        }

        $candidates = [
            data_get($payload, "rates.{$toCurrency}"),
            data_get($payload, "conversion_rates.{$toCurrency}"),
            data_get($payload, "data.rates.{$toCurrency}"),
            data_get($payload, 'result.rate'),
            data_get($payload, 'rate'),
        ];

        foreach ($candidates as $candidate) {
            if (is_numeric($candidate) && (float) $candidate > 0) {
                return (float) $candidate;
            }
        }

        return 0;
    }

    protected function maskUrl(string $url): string
    {
        return preg_replace('/apikey=[^&]+/', 'apikey=***', $url) ?? $url;
    }
}
