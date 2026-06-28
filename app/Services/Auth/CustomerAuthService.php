<?php

namespace App\Services\Auth;

use App\Http\Resources\Api\V1\Auth\AuthTokenResource;
use App\Http\Resources\Api\V1\Auth\CustomerResource;
use App\Models\LoginLog;
use App\Models\User;
use App\Services\FileManager;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class CustomerAuthService
{
    public function __construct(
        protected OtpService $otpService,
        protected FileManager $fileManager,
    ) {}

    public function sendRegistrationOtp(string $phoneNumber): array
    {
        if (User::query()->where('phone', $phoneNumber)->exists()) {
            throw ValidationException::withMessages([
                'phone_number' => [__('api.phone_already_registered')],
            ]);
        }

        return $this->otpService->send($phoneNumber, 'register', [
            'preferred_language' => app()->getLocale(),
        ]);
    }

    public function verifyRegistrationOtp(string $phoneNumber, string $otp): array
    {
        if (User::query()->where('phone', $phoneNumber)->exists()) {
            throw ValidationException::withMessages([
                'phone_number' => [__('api.phone_already_registered')],
            ]);
        }

        $verification = $this->otpService->verify($phoneNumber, 'register', $otp);

        return [
            'phone_number' => $verification->phone,
            'verified_at' => $verification->verified_at,
            'profile_completion_required' => true,
        ];
    }

    public function completeRegistration(array $data, Request $request): AuthTokenResource
    {
        if (User::query()->where('phone', $data['phone_number'])->exists()) {
            throw ValidationException::withMessages([
                'phone_number' => [__('api.phone_already_registered')],
            ]);
        }

        $this->otpService->assertRecentlyVerified($data['phone_number'], 'register');

        return DB::transaction(function () use ($data, $request) {
            $user = User::create([
                'name' => $data['full_name'],
                'phone' => $data['phone_number'],
                'email' => $data['email'] ?? null,
                'profile_photo' => $this->storeProfilePhoto($data['profile_photo'] ?? null),
                'password' => $data['password'],
                'status' => User::STATUS_ACTIVE,
                'preferred_language' => app()->getLocale(),
                'device_token' => $data['device_token'] ?? null,
                'phone_verified_at' => now(),
                'email_verified_at' => isset($data['email']) ? now() : null,
                'last_login_at' => now(),
            ]);

            return $this->issueToken($user, $data['device_name'], $request, $user->phone, __('api.customer_registered'));
        });
    }

    public function sendLoginOtp(string $phoneNumber): array
    {
        $user = User::query()->where('phone', $phoneNumber)->first();

        if (! $user && ! $this->otpService->hasRecentlyVerifiedRegistration($phoneNumber)) {
            $user = User::create([
                'phone' => $phoneNumber,
            ]);
            // throw ValidationException::withMessages([
            //     'phone_number' => [__('api.customer_not_found')],
            // ]);
        }

        if ($user?->isBlocked()) {
            throw ValidationException::withMessages([
                'phone_number' => [__('api.customer_account_blocked')],
            ]);
        }

        return $this->otpService->send($phoneNumber, 'login', [
            'preferred_language' => app()->getLocale(),
        ]);
    }

    public function verifyLoginOtp(array $data, Request $request): array|AuthTokenResource
    {
        $this->otpService->verify($data['phone_number'], 'login', $data['otp']);

        $user = User::query()->where('phone', $data['phone_number'])->first();

        if (! $user) {
            if ($this->otpService->hasRecentlyVerifiedRegistration($data['phone_number'])) {
                return [
                    'profile_completion_required' => true,
                    'phone_number' => $data['phone_number'],
                    'message' => __('api.profile_completion_required'),
                ];
            }

            throw ValidationException::withMessages([
                'phone_number' => [__('api.customer_not_found')],
            ]);
        }

        $this->assertCustomerCanAuthenticate($user);

        $user->forceFill([
            'phone_verified_at' => $user->phone_verified_at ?? now(),
            'device_token' => $data['device_token'] ?? $user->device_token,
            'preferred_language' => app()->getLocale(),
            'last_login_at' => now(),
        ])->save();

        return $this->issueToken($user, $data['device_name'], $request, $user->phone, __('api.customer_logged_in_with_otp'));
    }

    public function resendOtp(string $phoneNumber, string $purpose): array
    {
        if ($purpose === 'register') {
            if (User::query()->where('phone', $phoneNumber)->exists()) {
                throw ValidationException::withMessages([
                    'phone_number' => [__('api.phone_already_registered')],
                ]);
            }
        }

        if ($purpose === 'login') {
            $user = User::query()->where('phone', $phoneNumber)->first();

            if (! $user && ! $this->otpService->hasRecentlyVerifiedRegistration($phoneNumber)) {
                throw ValidationException::withMessages([
                    'phone_number' => [__('api.customer_not_found')],
                ]);
            }

            if ($user?->isBlocked()) {
                throw ValidationException::withMessages([
                    'phone_number' => [__('api.customer_account_blocked')],
                ]);
            }
        }

        return $this->otpService->resend($phoneNumber, $purpose, [
            'preferred_language' => app()->getLocale(),
        ]);
    }

    public function updateProfile(User $user, array $data): User
    {
        $attributes = [];

        if (array_key_exists('full_name', $data)) {
            $attributes['name'] = $data['full_name'];
        }

        if (array_key_exists('email', $data)) {
            $attributes['email'] = $data['email'];
            $attributes['email_verified_at'] = filled($data['email']) ? now() : null;
        }

        if (array_key_exists('password', $data) && filled($data['password'])) {
            $attributes['password'] = $data['password'];
        }

        if (array_key_exists('device_token', $data)) {
            $attributes['device_token'] = $data['device_token'];
        }

        if (array_key_exists('profile_photo', $data)) {
            if ($data['profile_photo'] instanceof UploadedFile) {
                $this->fileManager->delete($user->profile_photo);
                $attributes['profile_photo'] = $this->storeProfilePhoto($data['profile_photo']);
            }
        }

        if ($attributes !== []) {
            $user->forceFill($attributes)->save();
        }

        return $user->fresh();
    }

    public function loginWithSocialProvider(
        string $provider,
        SocialiteUser $socialiteUser,
        Request $request,
        array $attributes = []
    ): AuthTokenResource {
        $providerIdColumn = $provider . '_id';

        $query = User::query()->where($providerIdColumn, $socialiteUser->getId());

        if ($socialiteUser->getEmail()) {
            $query->orWhere('email', $socialiteUser->getEmail());
        }

        $user = $query->first();

        $user = DB::transaction(function () use ($user, $providerIdColumn, $socialiteUser, $attributes) {
            if (! $user) {
                $user = new User;
            }

            $user->fill([
                'name' => $socialiteUser->getName() ?: $socialiteUser->getNickname() ?: __('api.customer_fallback_name'),
                'email' => $socialiteUser->getEmail() ?: $user->email,
                'profile_photo' => $socialiteUser->getAvatar() ?: $user->profile_photo,
                'preferred_language' => app()->getLocale(),
                'device_token' => $attributes['device_token'] ?? $user->device_token,
                'status' => $user->status ?? User::STATUS_ACTIVE,
                'last_login_at' => now(),
            ]);

            $user->{$providerIdColumn} = $socialiteUser->getId();

            if (! $user->password) {
                $user->password = Str::password(16);
            }

            $user->save();

            return $user;
        });

        $this->assertCustomerCanAuthenticate($user);

        return $this->issueToken(
            $user,
            $attributes['device_name'] ?? ucfirst($provider) . ' OAuth',
            $request,
            $socialiteUser->getEmail() ?: $socialiteUser->getId(),
            __('api.social_login_successful', ['provider' => ucfirst($provider)])
        );
    }

    public function logout(User $user, Request $request): void
    {
        $currentTokenId = $user->currentAccessToken()?->id;

        LoginLog::markCurrentSessionLoggedOut($user, 'customer', $request);

        $user->currentAccessToken()?->delete();

        Log::info('Customer logged out.', [
            'user_id' => $user->id,
            'phone' => $user->phone,
            'email' => $user->email,
            'token_id' => $currentTokenId,
            'ip' => $request->ip(),
        ]);
    }

    public function updateLanguage(User $user, string $language): User
    {
        $user->forceFill([
            'preferred_language' => $language,
        ])->save();

        return $user->fresh();
    }

    protected function assertCustomerCanAuthenticate(User $user): void
    {
        if ($user->isBlocked()) {
            throw ValidationException::withMessages([
                'phone_number' => [__('api.customer_account_blocked')],
            ]);
        }

        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                'phone_number' => [__('api.customer_account_inactive')],
            ]);
        }
    }

    protected function issueToken(
        User $user,
        string $deviceName,
        Request $request,
        string $loginIdentifier,
        string $message
    ): AuthTokenResource {
        $token = $user->createToken($deviceName);

        LoginLog::recordSuccess($user, 'customer', $loginIdentifier, $request, $token->accessToken->id);

        Log::info($message, [
            'user_id' => $user->id,
            'phone' => $user->phone,
            'email' => $user->email,
            'token_id' => $token->accessToken->id,
            'ip' => $request->ip(),
        ]);

        return new AuthTokenResource([
            'user' => $user->fresh(),
            'token' => $token->plainTextToken,
        ]);
    }

    protected function storeProfilePhoto(mixed $file): ?string
    {
        if ($file instanceof UploadedFile) {
            return $this->fileManager->store($file, 'customers/profile-photos');
        }

        return null;
    }
}
