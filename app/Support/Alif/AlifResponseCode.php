<?php

namespace App\Support\Alif;

/**
 * The full result-code table from the Alif provider protocol.
 *
 * @see https://alifcapital.github.io/providers
 *
 * Fatal codes stop Alif from retrying. Non-fatal codes make Alif retry with a
 * growing interval until the payment succeeds, fails fatally, or its 24 hour
 * lifetime expires.
 */
enum AlifResponseCode: int
{
    /** Pay/status succeeded. */
    case SUCCESS = 200;

    /** Payment rejected. */
    case REJECTED = 203;

    /** Check succeeded: the subscriber exists and can be topped up. */
    case ACCOUNT_FOUND = 302;

    /** Wrong account identifier. */
    case ACCOUNT_NOT_FOUND = 404;

    /** Status asked about a payment we have never seen. */
    case TRANSACTION_NOT_FOUND = 104;

    /** Non-fatal: the payment is mid-flight elsewhere, retry later. */
    case TRANSACTION_EXISTS = 107;

    /** Already credited: a duplicate pay for an id we have settled. */
    case DUPLICATE_SUCCESS = 108;

    /** Non-fatal: accepted but not settled yet. */
    case PROCESSING = 201;

    case SERVICE_TEMPORARILY_UNAVAILABLE = 303;

    /** Non-fatal: insufficient funds. */
    case INSUFFICIENT_FUNDS = 305;

    /** Malformed request: bad data or format. */
    case BAD_REQUEST = 400;

    case AMOUNT_OUT_OF_RANGE = 405;

    case INTERNAL_ERROR = 500;

    case SERVICE_UNAVAILABLE = 503;

    /** Non-fatal catch-all, so Alif retries. */
    case UNKNOWN_ERROR = 520;

    case UNAUTHORIZED = 401;

    public function label(): string
    {
        return match ($this) {
            self::SUCCESS => 'Successful',
            self::REJECTED => 'Payment rejected',
            self::ACCOUNT_FOUND => 'Subscriber found',
            self::ACCOUNT_NOT_FOUND => 'Subscriber identifier not found',
            self::TRANSACTION_NOT_FOUND => 'Transaction does not exist',
            self::TRANSACTION_EXISTS => 'Transaction already exists',
            self::DUPLICATE_SUCCESS => 'Successful (duplicate)',
            self::PROCESSING => 'In processing',
            self::SERVICE_TEMPORARILY_UNAVAILABLE => 'Service temporarily unavailable',
            self::INSUFFICIENT_FUNDS => 'Insufficient funds on the account',
            self::BAD_REQUEST => 'Malformed request',
            self::AMOUNT_OUT_OF_RANGE => 'Amount out of range',
            self::INTERNAL_ERROR => 'Internal server error',
            self::SERVICE_UNAVAILABLE => 'Service unavailable',
            self::UNKNOWN_ERROR => 'Unknown error',
            self::UNAUTHORIZED => 'Authorization failed',
        };
    }

    /**
     * Whether Alif will stop retrying after receiving this code.
     */
    public function isFatal(): bool
    {
        return ! in_array($this, [
            self::TRANSACTION_EXISTS,
            self::PROCESSING,
            self::INSUFFICIENT_FUNDS,
            self::UNKNOWN_ERROR,
        ], true);
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::SUCCESS, self::ACCOUNT_FOUND, self::DUPLICATE_SUCCESS => 'success',
            self::PROCESSING, self::TRANSACTION_EXISTS => 'warning',
            self::ACCOUNT_NOT_FOUND, self::TRANSACTION_NOT_FOUND => 'info',
            default => 'danger',
        };
    }

    public static function tryLabel(?int $code): ?string
    {
        return $code === null ? null : self::tryFrom($code)?->label();
    }
}
