<?php

use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
    public string $panel = 'china';

    public function mount(string $panel = 'china'): void
    {
        $this->panel = $panel;
    }
};
?>

<div>
    <aside id="layout-menu" class="layout-menu menu-vertical menu">
        <div class="app-brand demo ">
            <a href="{{ Auth::guard('admin')->user()?->warehouseHomeRoute() ? route(Auth::guard('admin')->user()->warehouseHomeRoute()) : route('warehouse.login') }}" class="app-brand-link">
                <span class="app-brand-text demo menu-text fw-bold ms-3">{{ __('admin.warehouse_portal') }}</span>
            </a>
            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
                <i class="icon-base ti menu-toggle-icon d-none d-xl-block"></i>
                <i class="icon-base ti tabler-x d-block d-xl-none"></i>
            </a>
        </div>

        <div class="menu-inner-shadow"></div>

        <ul class="menu-inner py-1">
            @php $admin = Auth::guard('admin')->user(); @endphp

            @if ($admin?->canAccessChinaWarehousePanel())
                <li class="menu-item {{ request()->routeIs('warehouse.china.*') ? 'active' : '' }}">
                    <a href="{{ route('warehouse.china.orders.index') }}" class="menu-link">
                        <i class="menu-icon icon-base ti tabler-building-warehouse"></i>
                        <div>{{ __('admin.china_warehouse_orders') }}</div>
                    </a>
                </li>
            @endif

            @if ($admin?->canAccessTajikistanWarehousePanel())
                <li class="menu-item {{ request()->routeIs('warehouse.tajikistan.*') ? 'active' : '' }}">
                    <a href="{{ route('warehouse.tajikistan.orders.index') }}" class="menu-link">
                        <i class="menu-icon icon-base ti tabler-map-pin"></i>
                        <div>{{ __('admin.tajikistan_warehouse_orders') }}</div>
                    </a>
                </li>
            @endif

            <li class="menu-item {{ request()->routeIs('warehouse.profile') ? 'active' : '' }}">
                <a href="{{ route('warehouse.profile') }}" class="menu-link">
                    <i class="menu-icon icon-base ti tabler-user"></i>
                    <div>{{ __('admin.my_profile') }}</div>
                </a>
            </li>

            @if ($admin?->isSuperAdmin())
                <li class="menu-item">
                    <a href="{{ route('admin.dashboard') }}" class="menu-link">
                        <i class="menu-icon icon-base ti tabler-layout-dashboard"></i>
                        <div>{{ __('admin.main_admin_panel') }}</div>
                    </a>
                </li>
            @endif
        </ul>
    </aside>
</div>
