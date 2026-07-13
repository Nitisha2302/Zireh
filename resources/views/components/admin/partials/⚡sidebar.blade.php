<?php

use Livewire\Component;

new class extends Component {
    //
};
?>

<div>
    <!-- Menu -->
    <aside id="layout-menu" class="layout-menu menu-vertical menu">
        <div class="app-brand demo ">
            <x-company-brand :href="route('admin.dashboard')" />

            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
                <i class="icon-base ti menu-toggle-icon d-none d-xl-block"></i>
                <i class="icon-base ti tabler-x d-block d-xl-none"></i>
            </a>
        </div>

        <div class="menu-inner-shadow"></div>

        <ul class="menu-inner py-1">
            <!-- Page -->
            <li class="menu-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <a href="{{ route('admin.dashboard') }}" class="menu-link">
                    <i class="menu-icon icon-base ti tabler-smart-home"></i>
                    <div>{{ __('admin.dashboard') }}</div>
                </a>
            </li>

            <li class="menu-item {{ request()->routeIs('admin.platforms.*') ? 'active' : '' }}">
                <a href="{{ route('admin.platforms.index') }}" class="menu-link">
                    <i class="menu-icon icon-base ti tabler-devices"></i>
                    <div>{{ __('admin.platforms') }}</div>
                </a>
            </li>

            <li class="menu-item {{ request()->routeIs('admin.platform-sliders.*') ? 'active' : '' }}">
                <a href="{{ route('admin.platform-sliders.index') }}" class="menu-link">
                    <i class="menu-icon icon-base ti tabler-photo"></i>
                    <div>{{ __('admin.platform_sliders') }}</div>
                </a>
            </li>

            <li class="menu-item {{ request()->routeIs('admin.platform-categories.*') ? 'active' : '' }}">
                <a href="{{ route('admin.platform-categories.index') }}" class="menu-link">
                    <i class="menu-icon icon-base ti tabler-category"></i>
                    <div>{{ __('admin.platform_categories') }}</div>
                </a>
            </li>

            <li class="menu-item {{ request()->routeIs('admin.lessons.*') ? 'active' : '' }}">
                <a href="{{ route('admin.lessons.index') }}" class="menu-link">
                    <i class="menu-icon icon-base ti tabler-book"></i>
                    <div>{{ __('admin.lessons') }}</div>
                </a>
            </li>

            <li class="menu-item {{ request()->routeIs('admin.news.*') ? 'active' : '' }}">
                <a href="{{ route('admin.news.index') }}" class="menu-link">
                    <i class="menu-icon icon-base ti tabler-news"></i>
                    <div>{{ __('admin.news') }}</div>
                </a>
            </li>

            <li class="menu-item {{ request()->routeIs('admin.wallet-transactions.*') ? 'active' : '' }}">
                <a href="{{ route('admin.wallet-transactions.index') }}" class="menu-link">
                    <i class="menu-icon icon-base ti tabler-receipt"></i>
                    <div>{{ __('admin.wallet_transactions') }}</div>
                </a>
            </li>

            <li class="menu-item {{ request()->routeIs('admin.orders.*') || request()->routeIs('admin.order-statuses.*') ? 'open active' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon icon-base ti tabler-shopping-cart"></i>
                    <div>{{ __('admin.orders') }}</div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.orders.index') }}" class="menu-link">
                            <div>{{ __('admin.order_list') }}</div>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('admin.order-statuses.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.order-statuses.index') }}" class="menu-link">
                            <div>{{ __('admin.order_statuses') }}</div>
                        </a>
                    </li>
                </ul>
            </li>

            <li class="menu-item {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">
                <a href="{{ route('admin.customers.index') }}" class="menu-link">
                    <i class="menu-icon icon-base ti tabler-users"></i>
                    <div>{{ __('admin.customers') }}</div>
                </a>
            </li>

            <li class="menu-item {{ request()->routeIs('admin.warehouses.*') ? 'open active' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon icon-base ti tabler-building-warehouse"></i>
                    <div>{{ __('admin.warehouse_management') }}</div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item {{ request()->routeIs('admin.warehouses.index') || request()->routeIs('admin.warehouses.show') || request()->routeIs('admin.warehouses.edit') ? 'active' : '' }}">
                        <a href="{{ route('admin.warehouses.index') }}" class="menu-link">
                            <div>{{ __('admin.warehouse_list') }}</div>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('admin.warehouses.create') ? 'active' : '' }}">
                        <a href="{{ route('admin.warehouses.create') }}" class="menu-link">
                            <div>{{ __('admin.add_warehouse') }}</div>
                        </a>
                    </li>
                </ul>
            </li>

            <li class="menu-item {{ request()->routeIs('admin.shipping-methods.*') || request()->routeIs('admin.shipping-rates.*') ? 'open active' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon icon-base ti tabler-truck-delivery"></i>
                    <div>{{ __('admin.shipping_management') }}</div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item {{ request()->routeIs('admin.shipping-methods.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.shipping-methods.index') }}" class="menu-link">
                            <div>{{ __('admin.shipping_methods') }}</div>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('admin.shipping-rates.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.shipping-rates.index') }}" class="menu-link">
                            <div>{{ __('admin.shipping_rates') }}</div>
                        </a>
                    </li>
                </ul>
            </li>

            <li class="menu-item {{ request()->routeIs('admin.profile') ? 'active' : '' }}">
                <a href="{{ route('admin.profile') }}" class="menu-link">
                    <i class="menu-icon icon-base ti tabler-user"></i>
                    <div>{{ __('admin.profile') }}</div>
                </a>
            </li>


            <li class="menu-item {{ request()->routeIs('admin.settings.*') ? 'open active' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon icon-base ti tabler-settings"></i>
                    <div>{{ __('admin.settings') }}</div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item {{ request()->routeIs('admin.settings.company') ? 'active' : '' }}">
                        <a href="{{ route('admin.settings.company') }}" class="menu-link">
                            <div>{{ __('admin.company_settings') }}</div>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('admin.settings.privacy-terms') ? 'active' : '' }}">
                        <a href="{{ route('admin.settings.privacy-terms') }}" class="menu-link">
                            <div>{{ __('admin.privacy_terms') }}</div>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('admin.settings.currency-exchange') ? 'active' : '' }}">
                        <a href="{{ route('admin.settings.currency-exchange') }}" class="menu-link">
                            <div>{{ __('admin.currency_exchange_settings') }}</div>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('admin.settings.elim-api') ? 'active' : '' }}">
                        <a href="{{ route('admin.settings.elim-api') }}" class="menu-link">
                            <div>{{ __('admin.elim_api_settings') }}</div>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('admin.settings.elim-api-logs.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.settings.elim-api-logs.index') }}" class="menu-link">
                            <div>{{ __('admin.elim_api_logs') }}</div>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('admin.settings.elim-warehouse') ? 'active' : '' }}">
                        <a href="{{ route('admin.settings.elim-warehouse') }}" class="menu-link">
                            <div>{{ __('admin.elim_warehouse') }}</div>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('admin.settings.china-warehouse-login') ? 'active' : '' }}">
                        <a href="{{ route('admin.settings.china-warehouse-login') }}" class="menu-link">
                            <div>{{ __('admin.china_warehouse_login_settings') }}</div>
                        </a>
                    </li>
                    {{-- <li class="menu-item {{ request()->routeIs('admin.settings.file-manager') ? 'active' : '' }}">
                        <a href="{{ route('admin.settings.file-manager') }}" class="menu-link">
                            <div>{{ __('admin.file_manager') }}</div>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('admin.settings.didit') ? 'active' : '' }}">
                        <a href="{{ route('admin.settings.didit') }}" class="menu-link">
                            <div>{{ __('admin.didit_settings') }}</div>
                        </a>
                    </li> --}}
                </ul>
            </li>
        </ul>
    </aside>

    <div class="menu-mobile-toggler d-xl-none rounded-1">
        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large text-bg-secondary p-2 rounded-1">
            <i class="ti tabler-menu icon-base"></i>
            <i class="ti tabler-chevron-right icon-base"></i>
        </a>
    </div>
    <!-- / Menu -->
</div>
