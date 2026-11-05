<?php

use Livewire\Component;

new class extends Component {};
?>

<div>
    <aside id="layout-menu" class="layout-menu menu-vertical menu">
        <div class="app-brand demo ">
            <x-company-brand :href="route('china.orders.index')" />
            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
                <i class="icon-base ti menu-toggle-icon d-none d-xl-block"></i>
                <i class="icon-base ti tabler-x d-block d-xl-none"></i>
            </a>
        </div>

        <div class="menu-inner-shadow"></div>

        <ul class="menu-inner py-1">
            <li class="menu-item {{ request()->routeIs('china.orders.*') ? 'active' : '' }}">
                <a href="{{ route('china.orders.index') }}" class="menu-link">
                    <i class="menu-icon icon-base ti tabler-building-warehouse"></i>
                    <div>{{ __('admin.china_warehouse_orders') }}</div>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('china.shipments.*') ? 'active' : '' }}">
                <a href="{{ route('china.shipments.index') }}" class="menu-link">
                    <i class="menu-icon icon-base ti tabler-truck-delivery"></i>
                    <div>{{ __('admin.shipment_management') }}</div>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('china.scanner') ? 'active' : '' }}">
                <a href="{{ route('china.scanner') }}" class="menu-link">
                    <i class="menu-icon icon-base ti tabler-barcode"></i>
                    <div>{{ __('admin.parcel_barcode_scanner') }}</div>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('china.exceptions.*') ? 'active' : '' }}">
                <a href="{{ route('china.exceptions.index') }}" class="menu-link">
                    <i class="menu-icon icon-base ti tabler-alert-triangle"></i>
                    <div>{{ __('admin.exception_management') }}</div>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('china.profile') ? 'active' : '' }}">
                <a href="{{ route('china.profile') }}" class="menu-link">
                    <i class="menu-icon icon-base ti tabler-user"></i>
                    <div>{{ __('admin.my_profile') }}</div>
                </a>
            </li>
        </ul>
    </aside>
</div>
