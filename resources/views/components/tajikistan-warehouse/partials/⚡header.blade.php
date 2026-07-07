<?php

use App\Models\Admin;
use App\Models\LoginLog;
use App\Support\LocaleManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

new class extends Component {
    public $admin;
    public array $locales = [];
    public string $currentLocale = 'en';

    public function mount()
    {
        $this->admin = Auth::guard('admin')->user()?->load('warehouse');
        $manager = app(LocaleManager::class);
        $this->locales = $manager->supportedLocales();
        $this->currentLocale = $manager->resolve(session('locale'), app()->getLocale());
    }

    public function setLocale(string $locale)
    {
        $manager = app(LocaleManager::class);

        if (! $manager->isSupported($locale)) {
            return;
        }

        session(['locale' => $locale]);
        $this->currentLocale = $locale;
        app()->setLocale($locale);

        return $this->redirect(url()->previous() ?: route('tajikistan.orders.index'), navigate: false);
    }

    public function logout()
    {
        $admin = Auth::guard('admin')->user();

        if ($admin instanceof Admin) {
            LoginLog::markCurrentSessionLoggedOut($admin, 'tajikistan_warehouse', request());

            Log::info('Tajikistan warehouse staff logged out.', [
                'admin_id' => $admin->id,
                'warehouse_id' => $admin->warehouse_id,
            ]);
        }

        Auth::guard('admin')->logout();

        if (request()->hasSession()) {
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }

        return $this->redirectRoute('tajikistan.login', navigate: true);
    }
};
?>

<nav class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
            <i class="icon-base ti tabler-menu-2 icon-md"></i>
        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">
        <ul class="navbar-nav flex-row align-items-center ms-md-auto">
            @if ($admin?->warehouse)
                <li class="nav-item me-3 d-none d-md-block">
                    <span class="badge bg-label-primary">{{ $admin->warehouse->warehouse_name }}</span>
                </li>
            @endif
            <li class="nav-item dropdown me-3">
                <a class="nav-link dropdown-toggle hide-arrow px-2" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <i class="icon-base ti tabler-language icon-md"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li class="dropdown-header">{{ __('admin.language') }}</li>
                    @foreach ($locales as $code => $label)
                        <li>
                            <a class="dropdown-item {{ $currentLocale === $code ? 'active' : '' }}" href="#"
                                wire:click.prevent="setLocale('{{ $code }}')">
                                {{ __(
                                    'admin.' . match ($code) {
                                        'ru' => 'russian',
                                        'tg' => 'tajik',
                                        default => 'english',
                                    },
                                ) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </li>
            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                        <img src="{{ $admin->avatar ?? '' }}" alt="" class="rounded-circle" />
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <div class="dropdown-item">
                            <div class="fw-medium">{{ $admin->name ?? '' }}</div>
                            <small class="text-body-secondary">{{ Admin::roles()[$admin->role] ?? '' }}</small>
                            @if ($admin?->warehouse)
                                <small class="text-body-secondary d-block">{{ $admin->warehouse->warehouse_name }}</small>
                            @endif
                        </div>
                    </li>
                    <li><div class="dropdown-divider my-1 mx-n2"></div></li>
                    <li>
                        <a class="dropdown-item" href="{{ route('tajikistan.profile') }}">
                            <i class="icon-base ti tabler-user icon-md me-3"></i>{{ __('admin.my_profile') }}
                        </a>
                    </li>
                    <li><div class="dropdown-divider my-1 mx-n2"></div></li>
                    <li>
                        <a class="dropdown-item" href="#" wire:click.prevent="logout">
                            <i class="icon-base ti tabler-power icon-md me-3"></i>{{ __('admin.log_out') }}
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</nav>
