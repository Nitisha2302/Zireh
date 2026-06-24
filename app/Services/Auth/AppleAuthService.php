<?php

namespace App\Services\Auth;

class AppleAuthService extends AbstractSocialAuthService
{
    protected function driver(): string
    {
        return 'apple';
    }
}
