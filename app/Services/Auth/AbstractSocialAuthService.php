<?php

namespace App\Services\Auth;

use App\Services\Auth\Contracts\SocialAuthServiceInterface;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

abstract class AbstractSocialAuthService implements SocialAuthServiceInterface
{
    abstract protected function driver(): string;

    public function redirectUrl(): string
    {
        return Socialite::driver($this->driver())->stateless()->redirect()->getTargetUrl();
    }

    public function userFromCallback(): SocialiteUser
    {
        return Socialite::driver($this->driver())->stateless()->user();
    }
}
