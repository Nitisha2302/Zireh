<?php

use Livewire\Component;

new class extends Component {
    public function logout(){
        Auth::guard('seller')->logout();
        return redirect()->route('seller.login');
    }
};
?>

<nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
    data-bs-theme="dark">
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
            <i class="icon-base ti tabler-menu-2"></i>
        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
        <div class="navbar-nav align-items-center">
            <div class="nav-item navbar-search-wrapper mb-0">
                <a class="nav-item nav-link btn btn-text-primary btn-icon rounded-pill" href="javascript:void(0);">
                    <i class="icon-base ti tabler-search"></i>
                </a>
            </div>
        </div>

        <ul class="navbar-nav flex-row align-items-center ms-auto">
            {{-- Language Switch --}}
            <li class="nav-item me-2 me-xl-0">
                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <i class="icon-base ti tabler-language"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="javascript:void(0);" wire:click="changeLanguage('en')">
                            <span class="align-middle">{{ __('seller.english') }}</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="javascript:void(0);" wire:click="changeLanguage('ru')">
                            <span class="align-middle">{{ __('seller.russian') }}</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="javascript:void(0);" wire:click="changeLanguage('tg')">
                            <span class="align-middle">{{ __('seller.tajik') }}</span>
                        </a>
                    </li>
                </ul>
            </li>

            {{-- Profile Menu --}}
            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                        <img src="{{ Auth::guard('seller')->user()?->profile_photo_url ? Auth::guard('seller')->user()->profile_photo_url : asset('assets/img/avatars/1.png') }}"
                            alt class="w-px-40 h-px-40 rounded-circle" />
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="{{ route('seller.profile') }}">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar avatar-online">
                                        <img src="{{ Auth::guard('seller')->user()?->profile_photo ? Storage::url(Auth::guard('seller')->user()->profile_photo) : asset('assets/img/avatars/1.png') }}"
                                            alt class="w-px-40 h-px-40 rounded-circle" />
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <span class="fw-semibold d-block">{{ Auth::guard('seller')->user()?->full_name }}</span>
                                    <small class="text-muted">{{ Auth::guard('seller')->user()?->email }}</small>
                                </div>
                            </div>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider"></div>
                    </li>
                    {{-- <li>
                        <a class="dropdown-item" href="{{ route('seller.profile') }}">
                            <i class="icon-base ti tabler-user-check me-2"></i>
                            <span class="align-middle">{{ __('seller.my_profile') }}</span>
                        </a>
                    </li> --}}
                    <li>
                        <div class="dropdown-divider"></div>
                    </li>
                    <li>
                        <a class="dropdown-item" href="javascript:void(0);" wire:click="logout">
                            <i class="icon-base ti tabler-logout me-2"></i>
                            <span class="align-middle">{{ __('seller.logout') }}</span>
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</nav>
