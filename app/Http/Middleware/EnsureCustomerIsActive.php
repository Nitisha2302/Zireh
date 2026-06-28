<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            throw ValidationException::withMessages([
                'auth' => [__('api.unauthenticated')],
            ]);
        }

        if ($user->isBlocked()) {
            throw ValidationException::withMessages([
                'auth' => [__('api.customer_account_blocked')],
            ]);
        }

        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                'auth' => [__('api.customer_account_inactive')],
            ]);
        }

        return $next($request);
    }
}
