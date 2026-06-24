<?php

namespace App\Providers;

use App\Services\FileManager;
use Dedoc\Scramble\Scramble;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use SocialiteProviders\Apple\Provider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FileManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('viewApiDocs', fn (?Authenticatable $user = null): bool => true);

        Scramble::configure()
            ->expose(
                ui: '/docs/seller-api',
                document: '/docs/seller-api.json',
            )
            ->routes(fn (Route $route): bool => Str::startsWith($route->uri(), 'api/v1/seller'));

        Scramble::registerApi('user', [
            'api_path' => 'api/v1/auth',
            'export_path' => 'user-api.json',
            'info' => [
                'version' => env('API_VERSION', '1.0.0'),
                'description' => <<<'MARKDOWN'
Customer API documentation for the Restro user app.

This documentation includes the customer authentication and account flow:

- Customer registration
- Password login
- OTP send
- OTP verify and OTP login
- Customer language update
- Google login
- Apple login
- Customer profile
- Customer logout

All authenticated customer endpoints use Sanctum bearer tokens.
MARKDOWN,
            ],
            'ui' => [
                'title' => 'Restro User API',
                'theme' => 'light',
                'hide_try_it' => false,
                'hide_schemas' => false,
                'logo' => '',
                'try_it_credentials_policy' => 'include',
                'layout' => 'responsive',
            ],
            'middleware' => config('scramble.middleware'),
        ])
            ->expose(
                ui: '/docs/user-api',
                document: '/docs/user-api.json',
            )
            ->routes(fn (Route $route): bool => Str::startsWith($route->uri(), 'api/v1/auth'));

        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('apple', Provider::class);
        });
    }
}
