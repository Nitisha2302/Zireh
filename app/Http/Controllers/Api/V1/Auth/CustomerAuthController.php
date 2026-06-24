<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Auth\PasswordLoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterCustomerRequest;
use App\Http\Requests\Api\V1\Auth\SendOtpRequest;
use App\Http\Requests\Api\V1\Auth\UpdateLanguageRequest;
use App\Http\Requests\Api\V1\Auth\VerifyOtpLoginRequest;
use App\Http\Resources\Api\V1\Auth\CustomerResource;
use App\Services\Auth\AppleAuthService;
use App\Services\Auth\Contracts\SocialAuthServiceInterface;
use App\Services\Auth\CustomerAuthService;
use App\Services\Auth\GoogleAuthService;
use App\Services\Auth\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CustomerAuthController extends ApiController
{
    public function __construct(
        protected CustomerAuthService $customerAuthService,
        protected OtpService $otpService,
        protected GoogleAuthService $googleAuthService,
        protected AppleAuthService $appleAuthService,
    ) {}

    /**
     * Register a new customer account using phone OTP verification.
     */
    public function register(RegisterCustomerRequest $request): JsonResponse
    {
        $resource = $this->customerAuthService->register($request->validated(), $request);

        return $this->successResponse($resource->resolve(), __('api.customer_registered'), 201);
    }

    /**
     * Authenticate a customer using email or phone number and issue a Sanctum token.
     */
    public function login(PasswordLoginRequest $request): JsonResponse
    {
        $resource = $this->customerAuthService->login($request->validated(), $request);

        return $this->successResponse($resource->resolve(), __('api.customer_logged_in'));
    }

    /**
     * Send an OTP for customer registration or login.
     */
    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        $payload = $this->otpService->send(
            $request->validated('phone_number'),
            $request->validated('purpose'),
            ['preferred_language' => app()->getLocale()]
        );

        return $this->successResponse($payload, __('api.otp_sent'));
    }

    /**
     * Verify an OTP for registration or log in a customer with OTP.
     */
    public function verifyOtp(VerifyOtpLoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if ($validated['purpose'] === 'register') {
            $verification = $this->otpService->verify($validated['phone_number'], 'register', $validated['otp']);

            return $this->successResponse([
                'phone_number' => $verification->phone,
                'verified_at' => $verification->verified_at,
            ], __('api.otp_verified'));
        }

        $resource = $this->customerAuthService->loginWithOtp($validated, $request);

        return $this->successResponse($resource->resolve(), __('api.customer_logged_in_with_otp'));
    }

    /**
     * Generate the redirect URL for Google or Apple login.
     */
    public function socialRedirect(Request $request, string $provider): JsonResponse
    {
        $url = $this->socialService($provider)->redirectUrl();

        return $this->successResponse([
            'provider' => $provider,
            'redirect_url' => $url,
        ], __('api.social_redirect_generated', ['provider' => ucfirst($provider)]));
    }

    /**
     * Handle the Google or Apple callback and return a customer access token.
     */
    public function socialCallback(Request $request, string $provider): JsonResponse
    {
        $validated = validator($request->all(), [
            'device_name' => ['nullable', 'string', 'max:255'],
            'device_token' => ['nullable', 'string'],
            'preferred_language' => ['nullable', 'string', 'max:10'],
            'location_permission' => ['nullable', 'boolean'],
            'referral_code' => ['nullable', 'string', 'max:30'],
        ])->validate();

        $resource = $this->customerAuthService->loginWithSocialProvider(
            $provider,
            $this->socialService($provider)->userFromCallback(),
            $request,
            $validated
        );

        return $this->successResponse($resource->resolve(), __('api.social_login_successful', ['provider' => ucfirst($provider)]));
    }

    /**
     * Fetch the currently authenticated customer profile.
     */
    public function me(Request $request): JsonResponse
    {
        return $this->successResponse(new CustomerResource($request->user()), __('api.customer_profile_fetched'));
    }

    /**
     * Revoke the current customer access token and log the logout event.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            throw ValidationException::withMessages([
                'auth' => [__('api.unauthenticated')],
            ]);
        }

        $this->customerAuthService->logout($user, $request);

        return $this->successResponse(null, __('api.customer_logged_out'));
    }

    public function updateLanguage(UpdateLanguageRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            throw ValidationException::withMessages([
                'auth' => [__('api.unauthenticated')],
            ]);
        }

        $user = $this->customerAuthService->updateLanguage($user, $request->validated('language'));

        return $this->successResponse(
            (new CustomerResource($user))->resolve(),
            __('api.language_updated')
        );
    }

    protected function socialService(string $provider): SocialAuthServiceInterface
    {
        return match ($provider) {
            'google' => $this->googleAuthService,
            'apple' => $this->appleAuthService,
            default => throw ValidationException::withMessages([
                'provider' => [__('api.unsupported_social_provider')],
            ]),
        };
    }
}
