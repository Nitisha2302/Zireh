<?php

namespace App\Services\Auth\Contracts;

use Laravel\Socialite\Contracts\User as SocialiteUser;

interface SocialAuthServiceInterface
{
    public function redirectUrl(): string;

    public function userFromCallback(): SocialiteUser;
}
