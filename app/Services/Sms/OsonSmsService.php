<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OsonSmsService
{
    public function sendOtp(string $phoneNumber, string $otp, string $locale = 'en'): array
    {
        $token = config('services.osonsms.token');
        $login = config('services.osonsms.login');
        $sender = config('services.osonsms.sender');

        if (! $token || ! $login || ! $sender) {
            throw ValidationException::withMessages([
                'sms' => [__('api.sms_configuration_missing')],
            ]);
        }

        $txnId = (string) Str::uuid();

        $response = Http::withToken($token)
            ->acceptJson()
            ->get(config('services.osonsms.base_url', 'https://api.osonsms.com') . '/sendsms_v1.php', [
                'from' => $sender,
                'phone_number' => $phoneNumber,
                'msg' => __('api.sms_otp_message', ['otp' => $otp], $locale),
                'login' => $login,
                'txn_id' => $txnId,
                'is_confidential' => 'true',
            ]);

        if (! $response->successful() && $response->status() !== 201) {
            throw ValidationException::withMessages([
                'sms' => [$response->json('error.msg') ?: __('api.unable_to_send_otp')],
            ]);
        }

        return [
            'txn_id' => $response->json('txn_id', $txnId),
            'msg_id' => $response->json('msg_id'),
            'status' => $response->json('status') ?: 'ok',
        ];
    }
}
