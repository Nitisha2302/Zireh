<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        /** @var Admin|null $admin */
        $admin = Auth::guard('admin')->user();

        if (! $admin instanceof Admin) {
            $loginRoute = $request->routeIs('warehouse.*') ? 'warehouse.login' : 'login';

            return redirect()->guest(route($loginRoute));
        }

        if (! in_array($admin->role, $roles, true)) {
            abort(403, __('admin.access_denied'));
        }

        if ($admin->isTajikistanWarehouseStaff() && $admin->warehouse_id === null) {
            abort(403, __('admin.warehouse_staff_missing_assignment'));
        }

        return $next($request);
    }
}
