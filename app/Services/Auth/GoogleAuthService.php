<?php

namespace App\Services\Auth;

class GoogleAuthService extends AbstractSocialAuthService
{
    protected function driver(): string
    {
        return 'google';
    }
}
