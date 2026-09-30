<?php

namespace App\Providers;

use App\Repositories\Alif\AlifPaymentRepository;
use App\Services\Alif\AlifPaymentService;
use App\Services\Alif\AlifWalletCreditor;
use App\Services\Alif\Contracts\AlifAccountResolverInterface;
use App\Services\Alif\Contracts\AlifPaymentRepositoryInterface;
use App\Services\Alif\Contracts\AlifPaymentServiceInterface;
use App\Services\Alif\Contracts\AlifWalletCreditorInterface;
use App\Services\Alif\PhoneAlifAccountResolver;
use App\Services\FileManager;
use App\Support\Alif\AlifRequestContext;
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

        $this->app->bind(AlifAccountResolverInterface::class, PhoneAlifAccountResolver::class);
        $this->app->bind(AlifPaymentRepositoryInterface::class, AlifPaymentRepository::class);
        $this->app->bind(AlifWalletCreditorInterface::class, AlifWalletCreditor::class);
        $this->app->bind(AlifPaymentServiceInterface::class, AlifPaymentService::class);

        // One instance per request, so the response builder and the request
        // logger are looking at the same result.
        $this->app->scoped(AlifRequestContext::class);
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
