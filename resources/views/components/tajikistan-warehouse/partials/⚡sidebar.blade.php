<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
    public function mount(): void
    {
        $this->assignedWarehouse = Auth::guard('admin')->user()?->warehouse;
    }

    public $assignedWarehouse = null;
};
?>

<div>
    <aside id="layout-menu" class="layout-menu menu-vertical menu">
        <div class="app-brand demo ">
            <a href="{{ route('tajikistan.orders.index') }}" class="app-brand-link">
                <span class="app-brand-text demo menu-text fw-bold ms-3">{{ __('admin.tajikistan_warehouse_panel') }}</span>
            </a>
            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
                <i class="icon-base ti menu-toggle-icon d-none d-xl-block"></i>
                <i class="icon-base ti tabler-x d-block d-xl-none"></i>
            </a>
        </div>

        <div class="menu-inner-shadow"></div>

        <ul class="menu-inner py-1">
            <li class="menu-item {{ request()->routeIs('tajikistan.orders.*') ? 'active' : '' }}">
                <a href="{{ route('tajikistan.orders.index') }}" class="menu-link">
                    <i class="menu-icon icon-base ti tabler-map-pin"></i>
                    <div>{{ __('admin.tajikistan_warehouse_orders') }}</div>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('tajikistan.pickup.*') ? 'active' : '' }}">
                <a href="{{ route('tajikistan.pickup.index') }}" class="menu-link">
                    <i class="menu-icon icon-base ti tabler-scan"></i>
                    <div>{{ __('admin.pickup_qr_scan') }}</div>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('tajikistan.profile') ? 'active' : '' }}">
                <a href="{{ route('tajikistan.profile') }}" class="menu-link">
                    <i class="menu-icon icon-base ti tabler-user"></i>
                    <div>{{ __('admin.my_profile') }}</div>
                </a>
            </li>
        </ul>

        @if ($assignedWarehouse)
            <div class="px-4 py-3 mt-auto border-top">
                <small class="text-body-secondary d-block">{{ __('admin.assigned_warehouse') }}</small>
                <span class="fw-medium">{{ $assignedWarehouse->warehouse_name }}</span>
            </div>
        @endif
    </aside>
</div>
