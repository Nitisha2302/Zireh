<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Welcome Section -->
    <div class="row mb-6">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-2">{{ __('seller.welcome') }}, {{ Auth::guard('seller')->user()?->full_name }}! 👋</h5>
                    <p class="text-muted mb-0">{{ __('seller.welcome_description') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row">
        <!-- Restaurant Info Card -->
        <div class="col-md-6 col-lg-3 mb-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-1 small text-muted">{{ __('seller.restaurant') }}</p>
                            <h6 class="mb-0">{{ $restaurant?->name ?? __('seller.not_added') }}</h6>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-info">
                                <i class="icon-base ti tabler-building-store"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Card -->
        <div class="col-md-6 col-lg-3 mb-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-1 small text-muted">{{ __('seller.verification_status') }}</p>
                            <h6 class="mb-0">
                                @if (Auth::guard('seller')->user()?->verification_status === 'verified')
                                    <span class="badge bg-success">{{ __('seller.verified') }}</span>
                                @elseif (Auth::guard('seller')->user()?->verification_status === 'pending_verification')
                                    <span class="badge bg-warning">{{ __('seller.pending') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('seller.not_verified') }}</span>
                                @endif
                            </h6>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-primary">
                                <i class="icon-base ti tabler-shield-check"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Status Card -->
        <div class="col-md-6 col-lg-3 mb-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-1 small text-muted">{{ __('seller.account_status') }}</p>
                            <h6 class="mb-0">
                                @if (Auth::guard('seller')->user()?->status === 'active')
                                    <span class="badge bg-success">{{ __('seller.active') }}</span>
                                @elseif (Auth::guard('seller')->user()?->status === 'onboarding')
                                    <span class="badge bg-info">{{ __('seller.onboarding') }}</span>
                                @else
                                    <span class="badge bg-danger">{{ __('seller.inactive') }}</span>
                                @endif
                            </h6>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-warning">
                                <i class="icon-base ti tabler-activity"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Last Login Card -->
        <div class="col-md-6 col-lg-3 mb-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-1 small text-muted">{{ __('seller.last_login') }}</p>
                            <h6 class="mb-0">
                                {{ Auth::guard('seller')->user()?->last_login_at?->diffForHumans() ?? __('seller.never') }}
                            </h6>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-success">
                                <i class="icon-base ti tabler-clock"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-danger">
            <div class="card-body">
                <h5 class="card-title text-danger">
                    {{ __('seller.delete_account') }}
                </h5>

                <p class="text-muted">
                    {{ __('seller.delete_account_description') }}
                </p>

                <button class="btn btn-danger" wire:click="deleteAccount">
                    <i class="icon-base ti tabler-trash me-1"></i>
                    {{ __('seller.delete_account') }}
                </button>
            </div>
        </div>
    </div>
</div>

    <!-- Info Card -->
    <div class="row mt-6">
        <div class="col-12">
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <strong>💡 {{ __('seller.tip') }}</strong>
                {{ __('seller.dashboard_tip') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
</div>
    <!-- Quick Actions Row -->
    {{-- <div class="row mt-6">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('seller.quick_actions') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6 col-md-4 col-lg-3 mb-3">
                            <a href="{{ route('seller.profile') }}" class="btn btn-outline-primary w-100">
                                <i class="icon-base ti tabler-user me-2"></i>
                                {{ __('seller.edit_profile') }}
                            </a>
                        </div>
                        <div class="col-sm-6 col-md-4 col-lg-3 mb-3">
                            <a href="{{ route('seller.restaurant.index') }}" class="btn btn-outline-primary w-100">
                                <i class="icon-base ti tabler-building-store me-2"></i>
                                {{ __('seller.manage_restaurant') }}
                            </a>
                        </div>
                        <div class="col-sm-6 col-md-4 col-lg-3 mb-3">
                            <a href="{{ route('seller.products.index') }}" class="btn btn-outline-primary w-100">
                                <i class="icon-base ti tabler-basket me-2"></i>
                                {{ __('seller.manage_products') }}
                            </a>
                        </div>
                        <div class="col-sm-6 col-md-4 col-lg-3 mb-3">
                            <a href="{{ route('seller.orders.index') }}" class="btn btn-outline-primary w-100">
                                <i class="icon-base ti tabler-receipt me-2"></i>
                                {{ __('seller.view_orders') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> --}}
