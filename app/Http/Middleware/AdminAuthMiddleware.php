<?php

namespace App\Http\Middleware;

use App\Models\LoginLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, $guard = null)
    {
        if (! Auth::guard($guard)->check()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthenticated.'], 401);
            }
            if ($guard === 'admin') {
                $loginRoute = $request->routeIs('warehouse.*') ? 'warehouse.login' : 'login';

                return redirect()->guest(route($loginRoute));
            }

        }
        // dd(auth());
        if ($guard === 'admin' && Auth::guard('admin')->check()) {
            LoginLog::touchCurrentSession(Auth::guard('admin')->user(), 'admin', $request);
        }

        return $next($request);
    }
}
