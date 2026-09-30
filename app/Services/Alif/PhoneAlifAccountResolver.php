<?php

namespace App\Services\Alif;

use App\Models\User;
use App\Services\Alif\Contracts\AlifAccountResolverInterface;

/**
 * Resolves the Alif `account` field against `users.phone`.
 *
 * Customers type their phone number at an Alif terminal, and they type it
 * inconsistently. Registration only ever stores digits with an optional
 * leading `+` (see the regex in SendRegistrationOtpRequest), so comparing a
 * digits-only form of the input against the handful of shapes that could have
 * been stored is enough to make `+992 90 123 45 67`, `992901234567` and
 * `901234567` all land on the same customer.
 */
class PhoneAlifAccountResolver implements AlifAccountResolverInterface
{
    private const COUNTRY_CODE = '992';

    private const NATIONAL_LENGTH = 9;

    public function resolve(string $account): ?User
    {
        $candidates = $this->candidates($account);

        if ($candidates === []) {
            return null;
        }

        $user = User::query()
            ->whereIn('phone', $candidates)
            ->first();

        if (! $user || ! $user->isActive()) {
            return null;
        }

        return $user;
    }

    /**
     * @return list<string>
     */
    protected function candidates(string $account): array
    {
        $digits = preg_replace('/\D+/', '', $account) ?? '';

        if ($digits === '') {
            return [];
        }

        $candidates = [$digits, '+'.$digits];
        $national = $this->nationalNumber($digits);

        if ($national !== null) {
            $candidates[] = $national;
            $candidates[] = self::COUNTRY_CODE.$national;
            $candidates[] = '+'.self::COUNTRY_CODE.$national;
        }

        return array_values(array_unique($candidates));
    }

    protected function nationalNumber(string $digits): ?string
    {
        if (strlen($digits) === self::NATIONAL_LENGTH) {
            return $digits;
        }

        if (str_starts_with($digits, self::COUNTRY_CODE)
            && strlen($digits) === strlen(self::COUNTRY_CODE) + self::NATIONAL_LENGTH) {
            return substr($digits, strlen(self::COUNTRY_CODE));
        }

        return null;
    }
}
