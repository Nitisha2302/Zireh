<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Auth\CompleteRegistrationRequest;
use App\Http\Requests\Api\V1\Auth\ResendOtpRequest;
use App\Http\Requests\Api\V1\Auth\SendLoginOtpRequest;
use App\Http\Requests\Api\V1\Auth\SendRegistrationOtpRequest;
use App\Http\Requests\Api\V1\Auth\UpdateLanguageRequest;
use App\Http\Requests\Api\V1\Auth\UpdateProfileRequest;
use App\Http\Requests\Api\V1\Auth\VerifyLoginOtpRequest;
use App\Http\Requests\Api\V1\Auth\VerifyRegistrationOtpRequest;
use App\Http\Resources\Api\V1\Auth\AuthTokenResource;
use App\Http\Resources\Api\V1\Auth\CustomerResource;
use App\Services\Auth\AppleAuthService;
use App\Services\Auth\Contracts\SocialAuthServiceInterface;
use App\Services\Auth\CustomerAuthService;
use App\Services\Auth\GoogleAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CustomerAuthController extends ApiController
{
    public function __construct(
        protected CustomerAuthService $customerAuthService,
        protected GoogleAuthService $googleAuthService,
        protected AppleAuthService $appleAuthService,
    ) {}

    public function sendRegistrationOtp(SendRegistrationOtpRequest $request): JsonResponse
    {
        $payload = $this->customerAuthService->sendRegistrationOtp($request->validated('phone_number'));

        return $this->successResponse($payload, __('api.otp_sent'));
    }

    public function verifyRegistrationOtp(VerifyRegistrationOtpRequest $request): JsonResponse
    {
        $payload = $this->customerAuthService->verifyRegistrationOtp(
            $request->validated('phone_number'),
            $request->validated('otp')
        );

        return $this->successResponse($payload, __('api.otp_verified'));
    }

    public function completeRegistration(CompleteRegistrationRequest $request): JsonResponse
    {
        $resource = $this->customerAuthService->completeRegistration($request->validated(), $request);

        return $this->successResponse($resource->resolve(), __('api.customer_registered'), 201);
    }

    public function sendLoginOtp(SendLoginOtpRequest $request): JsonResponse
    {
        $payload = $this->customerAuthService->sendLoginOtp($request->validated('phone_number'));

        return $this->successResponse($payload, __('api.otp_sent'));
    }

    public function verifyLoginOtp(VerifyLoginOtpRequest $request): JsonResponse
    {
        $result = $this->customerAuthService->verifyLoginOtp($request->validated(), $request);

        if (is_array($result)) {
            return $this->successResponse($result, __('api.profile_completion_required'));
        }

        return $this->successResponse($result->resolve(), __('api.customer_logged_in_with_otp'));
    }

    public function resendOtp(ResendOtpRequest $request): JsonResponse
    {
        $payload = $this->customerAuthService->resendOtp(
            $request->validated('phone_number'),
            $request->validated('purpose')
        );

        return $this->successResponse($payload, __('api.otp_resent'));
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('warehouse');

        return $this->successResponse(new CustomerResource($user), __('api.customer_profile_fetched'));
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->customerAuthService->updateProfile($request->user(), $request->validated());

        return $this->successResponse(
            (new CustomerResource($user))->resolve(),
            __('api.customer_profile_updated')
        );
    }

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
        $user = $this->customerAuthService->updateLanguage(
            $request->user(),
            $request->validated('language')
        );

        return $this->successResponse(
            (new CustomerResource($user))->resolve(),
            __('api.language_updated')
        );
    }

    public function socialRedirect(Request $request, string $provider): JsonResponse
    {
        $url = $this->socialService($provider)->redirectUrl();

        return $this->successResponse([
            'provider' => $provider,
            'redirect_url' => $url,
        ], __('api.social_redirect_generated', ['provider' => ucfirst($provider)]));
    }

    public function socialCallback(Request $request, string $provider): JsonResponse
    {
        $validated = validator($request->all(), [
            'device_name' => ['nullable', 'string', 'max:255'],
            'device_token' => ['nullable', 'string'],
        ])->validate();

        $resource = $this->customerAuthService->loginWithSocialProvider(
            $provider,
            $this->socialService($provider)->userFromCallback(),
            $request,
            $validated
        );

        return $this->successResponse($resource->resolve(), __('api.social_login_successful', ['provider' => ucfirst($provider)]));
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
