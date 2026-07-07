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
            return redirect()->guest(route($this->loginRouteFor($request)));
        }

        if (! in_array($admin->role, $roles, true)) {
            if ($admin->isChinaWarehouseStaff() && $request->routeIs('tajikistan.*')) {
                abort(403, __('admin.china_warehouse_panel_only'));
            }

            if ($admin->isTajikistanWarehouseStaff() && $request->routeIs('china.*')) {
                abort(403, __('admin.tajikistan_warehouse_panel_only'));
            }

            if ($admin->isWarehouseStaff()) {
                return redirect()->route($admin->warehouseHomeRoute());
            }

            abort(403, __('admin.access_denied'));
        }

        if ($admin->isTajikistanWarehouseStaff() && $admin->warehouse_id === null) {
            abort(403, __('admin.warehouse_staff_missing_assignment'));
        }

        return $next($request);
    }

    protected function loginRouteFor(Request $request): string
    {
        if ($request->routeIs('china.*')) {
            return 'china.login';
        }

        if ($request->routeIs('tajikistan.*')) {
            return 'tajikistan.login';
        }

        return 'login';
    }
}
