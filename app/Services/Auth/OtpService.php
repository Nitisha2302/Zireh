<?php

namespace App\Services\Auth;

use App\Models\OtpVerification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OtpService
{
    public function create(string $phone, string $purpose, array $context = []): array
    {
        $otp = (string) random_int(100000, 999999);
        $otp = '000000';

        OtpVerification::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->delete();

        OtpVerification::create([
            'phone' => $phone,
            'purpose' => $purpose,
            'context' => $context,
            'code' => Hash::make($otp),
            'expires_at' => now()->addMinutes(5),
            'last_sent_at' => now(),
        ]);

        Log::info('OTP generated.', [
            'phone' => $phone,
            'purpose' => $purpose,
            'otp' => $otp,
        ]);

        return [
            'otp' => $otp,
            'expires_in_seconds' => 300,
        ];
    }

    public function send(string $phone, string $purpose, array $context = []): array
    {
        $payload = $this->create($phone, $purpose, $context);

        return [
            'expires_in_seconds' => $payload['expires_in_seconds'],
            'otp' => app()->isLocal() || config('app.debug') ? $payload['otp'] : null,
        ];
    }

    public function verify(string $phone, string $purpose, string $otp): OtpVerification
    {
        $verification = OtpVerification::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if (! $verification || $verification->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'otp' => [__('api.otp_invalid_or_expired')],
            ]);
        }

        $verification->increment('attempts');

        if (! Hash::check($otp, $verification->code)) {
            throw ValidationException::withMessages([
                'otp' => [__('api.otp_incorrect')],
            ]);
        }

        $verification->forceFill([
            'verified_at' => now(),
        ])->save();

        return $verification;
    }
}
