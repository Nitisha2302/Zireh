<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class=" layout-navbar-fixed layout-menu-fixed layout-compact " dir="ltr" data-skin="default" data-bs-theme="light"
    data-assets-path="{{ asset('assets/') }}/" data-template="vertical-menu-template-starter">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Permissions-Policy" content="camera=(self), microphone=()">

    @stack('styles')
    <title>{{ $title ?? __('admin.tajikistan_warehouse_panel') }}</title>
    @include('layouts.partials.admin-styles')

</head>

<body>
    <div class="layout-wrapper layout-content-navbar ">
        <div class="layout-container">
            <livewire:tajikistan-warehouse.partials.sidebar :key="'tajikistan-sidebar-' . app()->getLocale()" />
            <div class="layout-page">
                <livewire:tajikistan-warehouse.partials.header :key="'tajikistan-header-' . app()->getLocale()" />
                <div class="content-wrapper">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
    <div class="layout-overlay"></div>
    <div class="drag-target"></div>
    @include('layouts.partials.admin-scripts')
    @stack('scripts')
</body>

</html>
