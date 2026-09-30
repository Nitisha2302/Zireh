<?php

namespace App\Support\Alif;

use InvalidArgumentException;
use Stringable;

/**
 * A money amount held as a normalized decimal string.
 *
 * Every operation goes through bcmath at a fixed scale of 2, so no value in the
 * Alif credit path is ever converted to a float. Laravel's `decimal:2` casts
 * already hand us strings, which feed straight into this type.
 */
final readonly class Money implements Stringable
{
    public const SCALE = 2;

    private const PATTERN = '/^-?\d{1,12}(\.\d{1,2})?$/';

    private function __construct(public string $amount) {}

    /**
     * Parse an untrusted value, typically straight off an Alif JSON body.
     *
     * Returns null rather than throwing so callers can answer with protocol
     * code 400 instead of surfacing an exception.
     */
    public static function tryParse(mixed $value): ?self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (is_bool($value) || $value === null || is_array($value)) {
            return null;
        }

        $raw = match (true) {
            // json_encode honours serialize_precision, giving the shortest
            // representation that round-trips, e.g. 100.5 rather than 100.50000000001.
            is_float($value) => json_encode($value),
            is_int($value) => (string) $value,
            is_string($value) => trim($value),
            default => null,
        };

        if (! is_string($raw) || ! preg_match(self::PATTERN, $raw)) {
            return null;
        }

        return new self(self::normalize($raw));
    }

    public static function of(string|int|float $value): self
    {
        $money = self::tryParse($value);

        if (! $money) {
            throw new InvalidArgumentException('Value is not a valid decimal money amount.');
        }

        return $money;
    }

    public static function zero(): self
    {
        return new self(self::normalize('0'));
    }

    public function plus(self $other): self
    {
        return new self(bcadd($this->amount, $other->amount, self::SCALE));
    }

    public function minus(self $other): self
    {
        return new self(bcsub($this->amount, $other->amount, self::SCALE));
    }

    public function isLessThan(self $other): bool
    {
        return bccomp($this->amount, $other->amount, self::SCALE) < 0;
    }

    public function isGreaterThan(self $other): bool
    {
        return bccomp($this->amount, $other->amount, self::SCALE) > 0;
    }

    public function equals(self $other): bool
    {
        return bccomp($this->amount, $other->amount, self::SCALE) === 0;
    }

    public function isPositive(): bool
    {
        return bccomp($this->amount, '0', self::SCALE) > 0;
    }

    public function isWithin(self $min, self $max): bool
    {
        return ! $this->isLessThan($min) && ! $this->isGreaterThan($max);
    }

    public function value(): string
    {
        return $this->amount;
    }

    public function __toString(): string
    {
        return $this->amount;
    }

    /**
     * Pad or truncate to exactly two decimal places. Truncation is lossless
     * here because the pattern has already rejected anything with a third.
     */
    private static function normalize(string $raw): string
    {
        return bcadd($raw, '0', self::SCALE);
    }
}
