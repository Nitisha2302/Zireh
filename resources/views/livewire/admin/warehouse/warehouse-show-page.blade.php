<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1">{{ $warehouse->warehouse_name }}</h4>
            <p class="mb-0 text-body-secondary">
                <code>{{ $warehouse->warehouse_code }}</code>
                · {{ $warehouse->city }}, {{ $warehouse->country }}
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
                            <p class="text-body-secondary mb-1">{{ __('admin.warehouse_code') }}</p>
                            <p class="mb-0"><code>{{ $warehouse->warehouse_code }}</code></p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-body-secondary mb-1">{{ __('admin.contact_person') }}</p>
                            <p class="mb-0">{{ $warehouse->contact_person }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-body-secondary mb-1">{{ __('admin.contact_number') }}</p>
                            <p class="mb-0">{{ $warehouse->contact_number }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-body-secondary mb-1">{{ __('admin.email') }}</p>
                            <p class="mb-0">{{ $warehouse->email ?: '—' }}</p>
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
                            <p class="mb-0">{{ $warehouse->country }}</p>
                        </div>
                        <div class="col-md-4">
                            <p class="text-body-secondary mb-1">{{ __('admin.state_region') }}</p>
                            <p class="mb-0">{{ $warehouse->state }}</p>
                        </div>
                        <div class="col-md-4">
                            <p class="text-body-secondary mb-1">{{ __('admin.city') }}</p>
                            <p class="mb-0">{{ $warehouse->city }}</p>
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
                            <p class="mb-0">{{ $warehouse->latitude }}</p>
                        </div>
                        <div class="col-md-4">
                            <p class="text-body-secondary mb-1">{{ __('admin.longitude') }}</p>
                            <p class="mb-0">{{ $warehouse->longitude }}</p>
                        </div>
                    </div>
                </div>
            </div>

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
