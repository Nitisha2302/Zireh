<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-6">
        <div>
            <h4 class="mb-1">{{ $restaurant?->name ?: __('admin.restaurant_dashboard') }}</h4>
            <p class="mb-0 text-body-secondary">
                {{ $seller->full_name ?: __('admin.not_available') }} |
                {{ $restaurant?->city ?: __('admin.not_available') }} |
                {{ $restaurant?->status ?: __('admin.not_available') }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.sellers.index') }}" class="btn btn-label-secondary">
                <i class="icon-base ti tabler-arrow-left me-1"></i>
                {{ __('admin.back') }}
            </a>
            <a href="{{ route('admin.sellers.show', $seller) }}" class="btn btn-primary">
                {{ __('admin.review') }}
            </a>
        </div>
    </div>

    @if (! $restaurant)
        <div class="card">
            <div class="card-body text-center py-5">
                <h5 class="mb-2">{{ __('admin.no_restaurant_found') }}</h5>
                <p class="text-body-secondary mb-0">{{ __('admin.no_restaurant_found_description') }}</p>
            </div>
        </div>
    @else
        <div class="row g-4 mb-6">
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="mb-1 text-body-secondary">{{ __('admin.menus') }}</p>
                                <h3 class="mb-0">{{ $stats['menus_count'] }}</h3>
                            </div>
                            <span class="badge bg-label-primary p-2">
                                <i class="icon-base ti tabler-tools-kitchen-2"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="mb-1 text-body-secondary">{{ __('admin.products') }}</p>
                                <h3 class="mb-0">{{ $stats['products_count'] }}</h3>
                                <small class="text-success">{{ $stats['available_products_count'] }} {{ __('admin.available') }}</small>
                            </div>
                            <span class="badge bg-label-success p-2">
                                <i class="icon-base ti tabler-basket"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="mb-1 text-body-secondary">{{ __('admin.orders') }}</p>
                                <h3 class="mb-0">{{ $stats['orders_count'] }}</h3>
                                <small class="text-body-secondary">{{ $stats['completed_orders_count'] }} {{ __('admin.completed') }}</small>
                            </div>
                            <span class="badge bg-label-info p-2">
                                <i class="icon-base ti tabler-receipt"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="mb-1 text-body-secondary">{{ __('admin.revenue') }}</p>
                                <h3 class="mb-0">${{ number_format($stats['total_revenue'], 2) }}</h3>
                                <small class="text-body-secondary">${{ number_format($stats['monthly_revenue'], 2) }} {{ __('admin.this_month') }}</small>
                            </div>
                            <span class="badge bg-label-warning p-2">
                                <i class="icon-base ti tabler-cash"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-6 mb-6">
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('admin.restaurant_details') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            @if ($restaurant->logo)
                                <img src="{{ $restaurant->logo_url }}"
                                    alt="{{ __('admin.restaurant_logo') }}" class="rounded border object-fit-cover"
                                    style="width: 72px; height: 72px;">
                            @else
                                <div class="d-flex align-items-center justify-content-center bg-light rounded border"
                                    style="width: 72px; height: 72px;">
                                    <i class="icon-base ti tabler-building-store"></i>
                                </div>
                            @endif
                            <div>
                                <h5 class="mb-1">{{ $restaurant->name }}</h5>
                                <span class="badge {{ $restaurant->status === 'approved' ? 'bg-label-success' : 'bg-label-warning' }}">
                                    {{ str($restaurant->status)->replace('_', ' ')->title() }}
                                </span>
                            </div>
                        </div>

                        <p><strong>{{ __('admin.phone') }}:</strong> {{ $restaurant->phone }}</p>
                        <p><strong>{{ __('admin.email') }}:</strong> {{ $restaurant->email ?: __('admin.not_available') }}</p>
                        <p><strong>{{ __('admin.address') }}:</strong> {{ $restaurant->address }}</p>
                        <p><strong>{{ __('admin.city') }}:</strong> {{ $restaurant->city }}</p>
                        <p><strong>{{ __('admin.cuisine') }}:</strong> {{ $restaurant->cuisine_type ?: __('admin.not_available') }}</p>
                        <p><strong>{{ __('admin.food_type') }}:</strong> {{ $restaurant->food_type ?: __('admin.not_available') }}</p>
                        <p><strong>{{ __('admin.min_order') }}:</strong> ${{ number_format((float) $restaurant->minimum_order_amount, 2) }}</p>
                        <p><strong>{{ __('admin.prep_time') }}:</strong> {{ $restaurant->average_preparation_time ?: __('admin.not_available') }} {{ __('admin.minutes') }}</p>
                        <p><strong>{{ __('admin.delivery_radius') }}:</strong> {{ $restaurant->delivery_radius ?: __('admin.not_available') }}</p>
                        <p class="mb-0">
                            <strong>{{ __('admin.availability_status') }}:</strong>
                            <span class="badge {{ $this->isRestaurantOpenNow() ? 'bg-label-success' : 'bg-label-danger' }}">
                                {{ $this->isRestaurantOpenNow() ? __('admin.open_now') : __('admin.closed_now') }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('admin.business_hours') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-column gap-2">
                            @foreach ($businessHours as $businessHour)
                                <div class="d-flex align-items-center justify-content-between border-bottom pb-2">
                                    <span class="fw-semibold">{{ str($businessHour['day_name'])->title() }}</span>
                                    <small class="text-body-secondary">{{ str($this->displayBusinessHour($businessHour))->after(': ') }}</small>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('admin.restaurant_images') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @if ($restaurant->cover_image)
                                <div class="col-12">
                                    <a href="{{ $restaurant->cover_image_url }}" target="_blank">
                                        <img src="{{ $restaurant->cover_image_url }}"
                                            alt="{{ __('admin.restaurant_cover') }}" class="img-fluid rounded border object-fit-cover"
                                            style="height: 150px; width: 100%;">
                                    </a>
                                </div>
                            @endif
                            @if ($restaurant->logo)
                                <div class="col-6">
                                    <a href="{{ $restaurant->logo_url }}" target="_blank">
                                        <img src="{{ $restaurant->logo_url }}"
                                            alt="{{ __('admin.restaurant_logo') }}" class="img-fluid rounded border object-fit-cover"
                                            style="height: 120px; width: 100%;">
                                    </a>
                                </div>
                            @endif
                            @if (! $restaurant->cover_image && ! $restaurant->logo)
                                <div class="col-12 text-center text-body-secondary py-4">{{ __('admin.no_restaurant_images') }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-6">
            <div class="col-xl-3">
                <div class="card mb-6">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('admin.edit_business_hours') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-column gap-4">
                            @foreach ($businessHours as $dayOfWeek => $businessHour)
                                <div class="border rounded p-3">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                        <div class="fw-semibold">{{ str($businessHour['day_name'])->title() }}</div>
                                        <div class="d-flex gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                    id="closed-{{ $dayOfWeek }}"
                                                    wire:model.live="businessHours.{{ $dayOfWeek }}.is_closed">
                                                <label class="form-check-label" for="closed-{{ $dayOfWeek }}">
                                                    {{ __('admin.closed') }}
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                    id="hours-24-{{ $dayOfWeek }}"
                                                    wire:model.live="businessHours.{{ $dayOfWeek }}.is_24_hours"
                                                    @disabled($businessHour['is_closed'])>
                                                <label class="form-check-label" for="hours-24-{{ $dayOfWeek }}">
                                                    {{ __('admin.open_24_hours') }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label small">{{ __('admin.opening_time') }}</label>
                                            <input type="time"
                                                class="form-control @error('businessHours.' . $dayOfWeek . '.opening_time') is-invalid @enderror"
                                                wire:model="businessHours.{{ $dayOfWeek }}.opening_time"
                                                @disabled($businessHour['is_closed'] || $businessHour['is_24_hours'])>
                                            @error('businessHours.' . $dayOfWeek . '.opening_time')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">{{ __('admin.closing_time') }}</label>
                                            <input type="time"
                                                class="form-control @error('businessHours.' . $dayOfWeek . '.closing_time') is-invalid @enderror"
                                                wire:model="businessHours.{{ $dayOfWeek }}.closing_time"
                                                @disabled($businessHour['is_closed'] || $businessHour['is_24_hours'])>
                                            @error('businessHours.' . $dayOfWeek . '.closing_time')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-primary w-100 mt-4" wire:click="saveBusinessHours">
                            <i class="icon-base ti tabler-device-floppy me-1"></i>
                            {{ __('admin.save_business_hours') }}
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('admin.restaurant_management') }}</h5>
                    </div>
                    <div class="card-body p-2">
                        <div class="d-flex flex-xl-column gap-2 overflow-auto">
                            @foreach ($sections as $sectionKey => $section)
                                <button type="button"
                                    class="btn text-start flex-shrink-0 {{ $activeSection === $sectionKey ? 'btn-primary' : 'btn-label-secondary' }}"
                                    wire:click="showSection('{{ $sectionKey }}')">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="icon-base ti {{ $section['icon'] }}"></i>
                                        <span class="flex-grow-1">
                                            <span class="d-block fw-semibold">{{ $section['label'] }}</span>
                                            <small class="{{ $activeSection === $sectionKey ? 'text-white-50' : 'text-body-secondary' }}">
                                                {{ $section['count'] }} {{ __('admin.items') }}
                                            </small>
                                        </span>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="card mt-6">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('admin.orders_summary') }}</h5>
                    </div>
                    <div class="card-body">
                        @unless ($stats['orders_data_available'])
                            <div class="alert alert-secondary mb-4">{{ __('admin.orders_data_not_available') }}</div>
                        @endunless
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="border rounded p-3">
                                    <small class="text-body-secondary">{{ __('admin.pending') }}</small>
                                    <h5 class="mb-0">{{ $stats['pending_orders_count'] }}</h5>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-3">
                                    <small class="text-body-secondary">{{ __('admin.completed') }}</small>
                                    <h5 class="mb-0">{{ $stats['completed_orders_count'] }}</h5>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-3">
                                    <small class="text-body-secondary">{{ __('admin.cancelled') }}</small>
                                    <h5 class="mb-0">{{ $stats['cancelled_orders_count'] }}</h5>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-3">
                                    <small class="text-body-secondary">{{ __('admin.avg_price') }}</small>
                                    <h5 class="mb-0">${{ number_format($stats['average_product_price'], 2) }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-9">
                @if ($activeSection === 'products')
                    <livewire:admin.sellers.restaurant-products-section :seller="$seller" lazy wire:key="restaurant-products-{{ $seller->id }}" />
                @elseif ($activeSection === 'categories')
                    <livewire:admin.sellers.restaurant-categories-section :seller="$seller" lazy wire:key="restaurant-categories-{{ $seller->id }}" />
                @elseif ($activeSection === 'menus')
                    <livewire:admin.sellers.restaurant-menus-section :seller="$seller" lazy wire:key="restaurant-menus-{{ $seller->id }}" />
                @endif

                <div class="card mt-6">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('admin.documents_and_verification') }}</h5>
                    </div>
                    <div class="card-body">
                        {{-- <p><strong>{{ __('admin.documents') }}:</strong> {{ $seller->documents->count() }}</p> --}}
                        <p><strong>{{ __('admin.verification') }}:</strong> {{ $seller->verification_status }}</p>
                        <p><strong>{{ __('admin.approved_at') }}:</strong> {{ $seller->approved_at?->format('d M Y h:i A') ?: __('admin.not_available') }}</p>
                        <p><strong>{{ __('admin.last_login') }}:</strong> {{ $seller->last_login_at?->format('d M Y h:i A') ?: __('admin.not_available') }}</p>
                        @if ($seller->verifications)
                            <p class="mb-0"><strong>{{ __('admin.provider') }}:</strong> {{ $seller->verifications->provider }} | {{ $seller->verifications->status }}</p>
                        @else
                            <p class="text-body-secondary mb-0">{{ __('admin.no_didit_session') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
