<?php

namespace App\Services\Auth;

use App\Models\OtpVerification;
use App\Services\Sms\OsonSmsService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OtpService
{
    public function __construct(
        protected OsonSmsService $smsService,
    ) {}

    public function send(string $phone, string $purpose, array $context = []): array
    {
        $existing = $this->latestPending($phone, $purpose);

        if ($existing && $this->isWithinResendCooldown($existing)) {
            throw ValidationException::withMessages([
                'phone_number' => [__('api.otp_resend_cooldown', [
                    'seconds' => $this->resendCooldownRemaining($existing),
                ])],
            ]);
        }

        if ($existing && $this->hasExceededResendLimit($existing)) {
            throw ValidationException::withMessages([
                'phone_number' => [__('api.otp_resend_limit_reached')],
            ]);
        }

        $payload = $this->create($phone, $purpose, $context, $existing);

        if (! app()->isLocal() || ! config('app.debug')) {
            $this->smsService->sendOtp(
                $phone,
                $payload['otp'],
                $context['preferred_language'] ?? app()->getLocale()
            );
        }

        return [
            'expires_in_seconds' => $payload['expires_in_seconds'],
            'otp' => app()->isLocal() && config('app.debug') ? $payload['otp'] : null,
        ];
    }

    public function resend(string $phone, string $purpose, array $context = []): array
    {
        return $this->send($phone, $purpose, $context);
    }

    public function verify(string $phone, string $purpose, string $otp, bool $consume = true): OtpVerification
    {
        $verification = $this->latestPending($phone, $purpose);

        if (! $verification || $verification->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'otp' => [__('api.otp_invalid_or_expired')],
            ]);
        }

        if ($verification->attempts >= config('customer_auth.otp.max_verify_attempts', 5)) {
            throw ValidationException::withMessages([
                'otp' => [__('api.otp_attempts_exceeded')],
            ]);
        }

        $verification->increment('attempts');

        if (! Hash::check($otp, $verification->code)) {
            throw ValidationException::withMessages([
                'otp' => [__('api.otp_incorrect')],
            ]);
        }

        if ($consume) {
            $verification->forceFill([
                'verified_at' => now(),
            ])->save();
        }

        return $verification->fresh();
    }

    public function assertRecentlyVerified(string $phone, string $purpose): OtpVerification
    {
        $verification = OtpVerification::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNotNull('verified_at')
            ->latest('id')
            ->first();

        $completionMinutes = config('customer_auth.otp.registration_completion_minutes', 30);

        if (! $verification || $verification->verified_at->lt(now()->subMinutes($completionMinutes))) {
            throw ValidationException::withMessages([
                'phone_number' => [__('api.registration_otp_not_verified')],
            ]);
        }

        return $verification;
    }

    public function hasRecentlyVerifiedRegistration(string $phone): bool
    {
        try {
            $this->assertRecentlyVerified($phone, 'register');

            return true;
        } catch (ValidationException) {
            return false;
        }
    }

    protected function create(
        string $phone,
        string $purpose,
        array $context = [],
        ?OtpVerification $existing = null
    ): array {
        $otp = app()->isLocal() && config('app.debug')
            ? '000000'
            : (string) random_int(100000, 999999);

        $expiresMinutes = config('customer_auth.otp.expires_minutes', 5);

        if ($existing && ! $existing->verified_at) {
            $existing->forceFill([
                'context' => $context,
                'code' => Hash::make($otp),
                'expires_at' => now()->addMinutes($expiresMinutes),
                'last_sent_at' => now(),
                'resend_count' => $existing->resend_count + 1,
                'attempts' => 0,
            ])->save();

            Log::info('OTP regenerated.', ['phone' => $phone, 'purpose' => $purpose]);

            return [
                'otp' => $otp,
                'expires_in_seconds' => $expiresMinutes * 60,
            ];
        }

        OtpVerification::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->delete();

        OtpVerification::create([
            'phone' => $phone,
            'purpose' => $purpose,
            'context' => $context,
            'code' => Hash::make($otp),
            'expires_at' => now()->addMinutes($expiresMinutes),
            'last_sent_at' => now(),
        ]);

        Log::info('OTP generated.', ['phone' => $phone, 'purpose' => $purpose]);

        return [
            'otp' => $otp,
            'expires_in_seconds' => $expiresMinutes * 60,
        ];
    }

    protected function latestPending(string $phone, string $purpose): ?OtpVerification
    {
        return OtpVerification::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();
    }

    protected function isWithinResendCooldown(OtpVerification $verification): bool
    {
        if (! $verification->last_sent_at) {
            return false;
        }

        return $verification->last_sent_at->gt(
            now()->subSeconds(config('customer_auth.otp.resend_cooldown_seconds', 60))
        );
    }

    protected function resendCooldownRemaining(OtpVerification $verification): int
    {
        $elapsed = now()->diffInSeconds($verification->last_sent_at);

        return max(0, config('customer_auth.otp.resend_cooldown_seconds', 60) - $elapsed);
    }

    protected function hasExceededResendLimit(OtpVerification $verification): bool
    {
        if (! $verification->last_sent_at || $verification->last_sent_at->lt(now()->subHour())) {
            return false;
        }

        return $verification->resend_count >= config('customer_auth.otp.max_resends_per_hour', 5);
    }
}
