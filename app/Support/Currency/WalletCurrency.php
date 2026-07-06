<?php

namespace App\Support\Currency;

class WalletCurrency
{
    public const TJS = 'TJS';

    public static function symbol(?string $currency = null): string
    {
        return match (strtoupper((string) $currency)) {
            self::TJS => 'сом.',
            default => (string) $currency,
        };
    }

    public static function format(float $amount, ?string $currency = self::TJS): string
    {
        return self::symbol($currency).' '.number_format($amount, 2);
    }
}
