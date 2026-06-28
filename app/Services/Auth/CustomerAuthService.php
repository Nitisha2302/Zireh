<?php

namespace App\Services\Auth;

use App\Http\Resources\Api\V1\Auth\AuthTokenResource;
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

    public function register(array $data, Request $request): AuthTokenResource
    {
        $this->otpService->verify($data['phone_number'], 'register', $data['otp']);

        return DB::transaction(function () use ($data, $request) {
            $user = User::create([
                'name' => $data['full_name'],
                'phone' => $data['phone_number'],
                'email' => $data['email'] ?? null,
                'profile_photo' => $this->storeProfilePhoto($data['profile_photo'] ?? null),
                'password' => $data['password'] ?? null,
                'preferred_language' => app()->getLocale(),
                'device_token' => $data['device_token'] ?? null,
                'location_permission' => (bool) ($data['location_permission'] ?? false),
                'referral_code' => $this->generateReferralCode(),
                'referred_by_code' => $data['referral_code'] ?? null,
                'phone_verified_at' => now(),
                'email_verified_at' => isset($data['email']) ? now() : null,
                'last_login_at' => now(),
            ]);

            return $this->issueToken($user, $data['device_name'], $request, $user->phone, __('api.customer_registered'));
        });
    }

    public function login(array $data, Request $request): AuthTokenResource
    {
        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $user = User::query()->where($field, $data['login'])->first();

        if (! $user || ! $user->password || ! Hash::check($data['password'], $user->password)) {
            LoginLog::recordFailure('customer', $data['login'], $request, $user, 'Invalid credentials');

            throw ValidationException::withMessages([
                'login' => [__('api.invalid_credentials')],
            ]);
        }

        $user->forceFill([
            'device_token' => $data['device_token'] ?? $user->device_token,
            'preferred_language' => app()->getLocale(),
            'last_login_at' => now(),
        ])->save();

        return $this->issueToken($user, $data['device_name'], $request, $data['login'], __('api.customer_logged_in'));
    }

    public function loginWithOtp(array $data, Request $request): AuthTokenResource
    {
        $this->otpService->verify($data['phone_number'], 'login', $data['otp']);

        $user = User::query()->where('phone', $data['phone_number'])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'phone_number' => [__('api.customer_not_found')],
            ]);
        }

        $user->forceFill([
            'phone_verified_at' => $user->phone_verified_at ?? now(),
            'device_token' => $data['device_token'] ?? $user->device_token,
            'preferred_language' => app()->getLocale(),
            'last_login_at' => now(),
        ])->save();

        return $this->issueToken($user, $data['device_name'], $request, $user->phone, __('api.customer_logged_in_with_otp'));
    }

    public function loginWithSocialProvider(
        string $provider,
        SocialiteUser $socialiteUser,
        Request $request,
        array $attributes = []
    ): AuthTokenResource {
        $providerIdColumn = $provider.'_id';

        $query = User::query()->where($providerIdColumn, $socialiteUser->getId());

        if ($socialiteUser->getEmail()) {
            $query->orWhere('email', $socialiteUser->getEmail());
        }

        $user = $query->first();

        $user = DB::transaction(function () use ($user, $providerIdColumn, $socialiteUser, $attributes) {
            if (! $user) {
                $user = new User;
                $user->referral_code = $this->generateReferralCode();
            }

            $user->fill([
                'name' => $socialiteUser->getName() ?: $socialiteUser->getNickname() ?: __('api.customer_fallback_name'),
                'email' => $socialiteUser->getEmail() ?: $user->email,
                'profile_photo' => $socialiteUser->getAvatar() ?: $user->profile_photo,
                'preferred_language' => app()->getLocale(),
                'device_token' => $attributes['device_token'] ?? $user->device_token,
                'location_permission' => (bool) ($attributes['location_permission'] ?? $user->location_permission),
                'referred_by_code' => $attributes['referral_code'] ?? $user->referred_by_code,
                'last_login_at' => now(),
            ]);

            $user->{$providerIdColumn} = $socialiteUser->getId();

            if (! $user->password) {
                $user->password = Str::password(16);
            }

            $user->save();

            return $user;
        });

        return $this->issueToken(
            $user,
            $attributes['device_name'] ?? ucfirst($provider).' OAuth',
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

    public function updateLanguage(User $user, string $language): User
    {
        $user->forceFill([
            'preferred_language' => $language,
        ])->save();

        return $user->fresh();
    }

    protected function generateReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (User::query()->where('referral_code', $code)->exists());

        return $code;
    }

    protected function storeProfilePhoto(?UploadedFile $file): ?string
    {
        return $this->fileManager->store($file, 'customers/profile-photos');
    }
}
