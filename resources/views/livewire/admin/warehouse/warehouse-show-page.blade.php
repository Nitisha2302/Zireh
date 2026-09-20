<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1">{{ $warehouse->warehouse_name }}</h4>
            <p class="mb-0 text-body-secondary">
                @if ($warehouse->city || $warehouse->country)
                    {{ trim(implode(', ', array_filter([$warehouse->city, $warehouse->country]))) }}
                @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.warehouses.index') }}" class="btn btn-label-secondary">{{ __('admin.back_to_list') }}</a>
            <a href="{{ route('admin.warehouses.edit', $warehouse) }}" class="btn btn-primary">{{ __('admin.edit') }}</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">{{ __('admin.warehouse_basic_info') }}</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <p class="text-body-secondary mb-1">{{ __('admin.warehouse_name') }}</p>
                            <p class="mb-0 fw-medium">{{ $warehouse->warehouse_name }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-body-secondary mb-1">{{ __('admin.email') }}</p>
                            <p class="mb-0">{{ $warehouse->email ?: '—' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-body-secondary mb-1">{{ __('admin.contact_person') }}</p>
                            <p class="mb-0">{{ $warehouse->contact_person ?: '—' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-body-secondary mb-1">{{ __('admin.contact_number') }}</p>
                            <p class="mb-0">{{ $warehouse->contact_number ?: '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">{{ __('admin.warehouse_location') }}</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <p class="text-body-secondary mb-1">{{ __('admin.country') }}</p>
                            <p class="mb-0">{{ $warehouse->country ?: '—' }}</p>
                        </div>
                        <div class="col-md-4">
                            <p class="text-body-secondary mb-1">{{ __('admin.state_region') }}</p>
                            <p class="mb-0">{{ $warehouse->state ?: '—' }}</p>
                        </div>
                        <div class="col-md-4">
                            <p class="text-body-secondary mb-1">{{ __('admin.city') }}</p>
                            <p class="mb-0">{{ $warehouse->city ?: '—' }}</p>
                        </div>
                        <div class="col-12">
                            <p class="text-body-secondary mb-1">{{ __('admin.full_address') }}</p>
                            <p class="mb-0">{{ $warehouse->address }}</p>
                        </div>
                        <div class="col-md-4">
                            <p class="text-body-secondary mb-1">{{ __('admin.postal_code') }}</p>
                            <p class="mb-0">{{ $warehouse->postal_code ?: '—' }}</p>
                        </div>
                        <div class="col-md-4">
                            <p class="text-body-secondary mb-1">{{ __('admin.latitude') }}</p>
                            <p class="mb-0">{{ $warehouse->latitude ?: '—' }}</p>
                        </div>
                        <div class="col-md-4">
                            <p class="text-body-secondary mb-1">{{ __('admin.longitude') }}</p>
                            <p class="mb-0">{{ $warehouse->longitude ?: '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            @include('livewire.admin.warehouse.partials.working-hours-display', ['warehouse' => $warehouse])

            @if ($warehouse->notes)
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">{{ __('admin.notes') }}</h5></div>
                    <div class="card-body">
                        <p class="mb-0">{{ $warehouse->notes }}</p>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">{{ __('admin.warehouse_image') }}</h5></div>
                <div class="card-body">
                    @if ($warehouse->image)
                        <img src="{{ app(\App\Services\FileManager::class)->url($warehouse->image) }}" alt="{{ $warehouse->warehouse_name }}" class="img-fluid rounded border w-100">
                    @else
                        <div class="border rounded d-flex align-items-center justify-content-center bg-label-secondary" style="height: 220px;">
                            <i class="icon-base ti tabler-building-warehouse" style="font-size: 3rem;"></i>
                        </div>
                    @endif
                </div>
            </div>

            @php $tajikistanLoginUrl = route('tajikistan.login'); @endphp
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">{{ __('admin.tajikistan_warehouse_login_url') }}</h5></div>
                <div class="card-body" x-data="{ copied: false }">
                    <p class="text-body-secondary small mb-2">{{ __('admin.tajikistan_warehouse_login_url_hint') }}</p>
                    <code class="d-block small mb-3 text-break">{{ $tajikistanLoginUrl }}</code>
                    <div class="d-grid gap-2">
                        <button
                            type="button"
                            class="btn btn-label-primary"
                            @click="navigator.clipboard.writeText(@js($tajikistanLoginUrl)).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                        >
                            <i class="icon-base ti tabler-copy me-1"></i>
                            <span x-text="copied ? @js(__('admin.copied')) : @js(__('admin.copy_login_url'))"></span>
                        </button>
                        <a href="{{ $tajikistanLoginUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-label-secondary">
                            <i class="icon-base ti tabler-external-link me-1"></i>
                            {{ __('admin.open_login_page') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ __('admin.status') }}</h5></div>
                <div class="card-body">
                    @if ($warehouse->isActive())
                        <span class="badge bg-label-success mb-3">{{ __('admin.active') }}</span>
                    @else
                        <span class="badge bg-label-danger mb-3">{{ __('admin.inactive') }}</span>
                    @endif
                    <p class="text-body-secondary mb-1">{{ __('admin.created_date') }}</p>
                    <p class="mb-2">{{ $warehouse->created_at->format('d M Y H:i') }}</p>
                    <p class="text-body-secondary mb-1">{{ __('admin.last_updated') }}</p>
                    <p class="mb-0">{{ $warehouse->updated_at->format('d M Y H:i') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
